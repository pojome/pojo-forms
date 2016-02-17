<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Ajax {

	private $files = array();
	
	public function preview_shortcode() {
		if ( empty( $_POST['id'] ) ) {
			echo 'No found.';
			die();
		}
		
		$embed = new Pojo_Embed_Template();
		echo $embed->get_header();
		echo do_shortcode( POJO_FORMS()->helpers->get_shortcode_text( $_POST['id'] ) );
		echo $embed->get_footer();
		
		die();
	}
	
	public function form_contact_submit() {
		$return_array = array(
			'fields' => array(),
			'link' => '',
		);
		
		if ( empty( $_POST['form_id'] ) ) {
			$return_array['message'] = Pojo_Forms_Messages::get_default_message( Pojo_Forms_Messages::INVALID_FORM );
			wp_send_json_error( $return_array );
		}

		$form = get_post( absint( $_POST['form_id'] ) );
		
		if ( ! $form || 'pojo_forms' !== $form->post_type || ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'contact-form-send-' . $form->ID ) ) {
			$return_array['message'] = Pojo_Forms_Messages::get_default_message( Pojo_Forms_Messages::INVALID_FORM );
			wp_send_json_error( $return_array );
		}

		$repeater_fields = atmb_get_field_without_type( 'fields', 'form_',  $form->ID );
		if ( empty( $repeater_fields ) ) {
			$return_array['message'] = Pojo_Forms_Messages::get_message( $form->ID, Pojo_Forms_Messages::INVALID_FORM );
			wp_send_json_error( $return_array );
		}

		$this->files = array();

		foreach ( $repeater_fields as $field_index => $field ) {
			
			$field_name = 'form_field_' . ( $field_index + 1 );
			$field_label = $field['name'];
			// TODO: Valid by field type
			if ( $field['required'] && empty( $_POST[ $field_name ] ) && $field['type'] != 'file' ) {
				$return_array['fields'][ $field_name ] = Pojo_Forms_Messages::get_message( $form->ID, Pojo_Forms_Messages::FIELD_REQUIRED );
			}

			if ( $field['type'] == 'file' ) {
				$file_upload_error = array(
					0 => __( 'There is no error, the file uploaded with success.', 'pojo-forms' ),
					1 => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'pojo-forms' ),
					2 => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'pojo-forms' ),
					3 => __( 'The uploaded file was only partially uploaded.', 'pojo-forms' ),
					4 => __( 'No file was uploaded.', 'pojo-forms' ),
					6 => __( 'Missing a temporary folder.', 'pojo-forms' ),
					7 => __( 'Failed to write file to disk.', 'pojo-forms' ),
					8 => __( 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.', 'pojo-forms' )
				);

				if ( $field['required'] && $_FILES[$field_name]['error'] == 4 ) {
					$return_array['fields'][ $field_name ] = Pojo_Forms_Messages::get_message( $form->ID, Pojo_Forms_Messages::FIELD_REQUIRED );
				} 

				if ( $_FILES[$field_name]['error'] > 0 && $_FILES[$field_name]['error'] != 4 && empty( $return_array['fields'] ) ) {
					$error_code = $_FILES[$field_name]['error'];
					$return_array['fields'][ $field_name ] = $file_upload_error[$error_code];
				}
				
				$file_types_meta = explode(',' ,$field['file_types'] );
				$ext = @pathinfo( $_FILES[$field_name]["name"], PATHINFO_EXTENSION);

				if ( !in_array( $ext, $file_types_meta ) && empty( $return_array['fields'] ) )
					$return_array['fields'][ $field_name ] = __('This file type is not allowed.', 'pojo-forms' );	

				$file_size_meta = $field['file_sizes'] * pow( 1024,2 );
				$upload_file_size = @$_FILES[$field_name]["size"];

				if ( $upload_file_size > $file_size_meta && empty( $return_array['fields'] ) ) 
					$return_array['fields'][ $field_name ] = __('This file size is to big, try smaller one.', 'pojo-forms' );	
										
			}

			if ( $field['type'] == 'file' && empty( $return_array['fields'] ) ) {

				$uploads_dir = $this->upload_tmp_dir();
				$uploads_dir = $this->maybe_add_random_dir( $uploads_dir );

				$filename = uniqid() . ".{$ext}";
				$filename = wp_unique_filename( $uploads_dir, $filename );

				$new_file = trailingslashit( $uploads_dir ) . $filename;

				if ( is_dir( $uploads_dir ) && is_writable( $uploads_dir ) ) {

				    if (@move_uploaded_file($_FILES[$field_name]["tmp_name"], $new_file)) {
				    	@chmod( $new_file, 0400 );
				        $this->files[$field_label] = $new_file;
				    } else {
				        $return_array['fields'][ $field_name ] = __('There was an error while trying uploading your file.', 'pojo-forms');
				    }	
				} else {
					$return_array['fields'][ $field_name ] = __('Upload directory is not writable, or does not exist.', 'pojo-forms');
				}	
			}
			
		}

		if ( empty( $return_array['fields'] ) ) {
			$email_to = trim( atmb_get_field( 'form_email_to', $form->ID ) );
			$email_subject = trim( atmb_get_field( 'form_email_subject', $form->ID ) );
			if ( empty( $email_subject ) ) {
				$email_subject = sprintf( __( 'New message from "%s"', 'pojo-forms' ), get_bloginfo( 'name' ) );
			}
			
			$email_html = '';
			$inline_shortcodes = $field_values = array();
			
			foreach ( $repeater_fields as $field_index => $field ) {
				$field_name = 'form_field_' . ( $field_index + 1 );
				$field_label = $field['name'];
				$field_value = '';
				
				if ( isset( $_POST[ $field_name ] ) ) {
					$field_value = stripslashes_deep( $_POST[ $field_name ] );
					
					if ( is_array( $field_value ) ) {
						$field_value = implode( ', ', $field_value );
					}
				}

				if ( isset( $this->files[$field_label] ) ) {
					$field_value = $this->get_file_url( $this->files[$field_label] );
				}

				$inline_shortcodes[ $field['shortcode'] ] = $field_value;
				
				$field_values[] = array(
					'title' => $field['name'],
					'value' => $field_value,
				);
				
				$email_html .= sprintf(
					'%s: %s' . PHP_EOL,
					$field['name'],
					$field_value
				);
			}
			
			$metadata_types = (array) atmb_get_field( 'form_metadata', $form->ID, Pojo_MetaBox::FIELD_CHECKBOX_LIST );
			if ( ! empty( $metadata_types ) ) {
				$email_html .= PHP_EOL . '---' . PHP_EOL . PHP_EOL;
				
				$tmpl_line_html = '%s: %s' . PHP_EOL;
				foreach ( $metadata_types as $metadata_type ) {
					switch ( $metadata_type ) {
						case 'time' :
							$email_html .= sprintf( $tmpl_line_html, __( 'Time', 'pojo-forms' ), date( 'H:i', current_time( 'timestamp' ) ) );
							break;

						case 'date' :
							$email_html .= sprintf( $tmpl_line_html, __( 'Date', 'pojo-forms' ), date( 'd/m/Y', current_time( 'timestamp' ) ) );
							break;

						case 'page_url' :
							$title = __( 'Page URL', 'pojo-forms' );
							$value = home_url( $_POST['_wp_http_referer'] );
							
							$field_values[] = array(
								'title' => $title,
								'value' => $value,
							);
							
							$email_html .= sprintf( $tmpl_line_html, $title, $value );
							break;

						case 'user_agent' :
							$title = __( 'User Agent', 'pojo-forms' );
							$value = $_SERVER['HTTP_USER_AGENT'];

							$field_values[] = array(
								'title' => $title,
								'value' => $value,
							);

							$email_html .= sprintf( $tmpl_line_html, $title, $value );
							break;

						case 'remote_ip' :
							$email_html .= sprintf( $tmpl_line_html, __( 'Remote IP', 'pojo-forms' ), POJO_FORMS()->helpers->get_client_ip() );
							break;

						case 'credit' :
							$email_html .= apply_filters( 'pojo_forms_email_credit', __( 'Powered by http://pojo.me/', 'pojo-forms' ) ) . PHP_EOL;
							break;
					}
				}
			}
			
			$skip = apply_filters( 'pojo_forms_skip_contact', false, $form->ID, $inline_shortcodes );
			if ( ! $skip ) {
				$email_from_name = atmb_get_field( 'form_email_form_name', $form->ID );
				if ( empty( $email_from_name ) )
					$email_from_name = get_bloginfo( 'name' );

				$email_from = atmb_get_field( 'form_email_form', $form->ID );
				if ( empty( $email_from ) )
					$email_from = get_bloginfo( 'admin_email' );

				$email_reply_to = atmb_get_field( 'form_email_reply_to', $form->ID );
				if ( empty( $email_reply_to ) )
					$email_reply_to = $email_from;

				$email_subject       = strtr( $email_subject, $inline_shortcodes );
				$email_from_name     = strtr( $email_from_name, $inline_shortcodes );
				$email_from          = strtr( $email_from, $inline_shortcodes );
				$email_reply_to      = strtr( $email_reply_to, $inline_shortcodes );
				
				$headers = sprintf( 'From: %s <%s>' . "\r\n", $email_from_name, $email_from );
				$headers .= sprintf( 'Reply-To: %s' . "\r\n", $email_reply_to );

				$headers = apply_filters( 'pojo_forms_wp_mail_headers', $headers ); // Temp filter
				$email_html = apply_filters( 'pojo_forms_wp_mail_message', $email_html );
				
				wp_mail( $email_to, $email_subject, $email_html, $headers );
				
				do_action( 'pojo_forms_mail_sent', $form->ID, $field_values, $this->files );
			} else {
				do_action( 'pojo_forms_mail_blocked', $form->ID );
			}
			
			$redirect_to = atmb_get_field( 'form_redirect_to', $form->ID );
			if ( empty( $redirect_to ) || ! filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
				$redirect_to = '';
			}
			
			$return_array['link'] = $redirect_to;
			$return_array['message'] = Pojo_Forms_Messages::get_message( $form->ID, Pojo_Forms_Messages::SUCCESS );
			wp_send_json_success( $return_array );
		} else {
			$return_array['message'] = Pojo_Forms_Messages::get_message( $form->ID, Pojo_Forms_Messages::ERROR );
			wp_send_json_error( $return_array );
		}
		
		wp_send_json_error( $return_array );
		die();
	}

	function upload_tmp_dir() {
		$upload_dir = wp_upload_dir();
		$upload_dir = wp_normalize_path($upload_dir['basedir']).'/pojo_forms_uploads';		

		return apply_filters( 'pojo_forms_upload_folder', $upload_dir );
	}	

	function maybe_add_random_dir( $dir ) {
		do {
			$rand_max = mt_getrandmax();
			$rand = zeroise( mt_rand( 0, $rand_max ), strlen( $rand_max ) );
			$dir_new = path_join( $dir, $rand );
		} while ( file_exists( $dir_new ) );

		if ( wp_mkdir_p( $dir_new ) ) {
			return $dir_new;
		}

		return $dir;
	}	


	function get_file_url( $file ) {

		// Get correct URL and path to wp-content
		$content_url = untrailingslashit( dirname( dirname( get_stylesheet_directory_uri() ) ) );
		$content_dir = untrailingslashit( WP_CONTENT_DIR );

		// Fix path on Windows
		$file = wp_normalize_path( $file );
		$content_dir = wp_normalize_path( $content_dir );

		return str_replace( $content_dir, $content_url, $file );		
	}


	public function __construct() {
		add_action( 'wp_ajax_form_preview_shortcode', array( &$this, 'preview_shortcode' ) );
		add_action( 'wp_ajax_pojo_form_contact_submit', array( &$this, 'form_contact_submit' ) );
		add_action( 'wp_ajax_nopriv_pojo_form_contact_submit', array( &$this, 'form_contact_submit' ) );

		do_action('pojo_forms_ajax_handler');
	}
	
}