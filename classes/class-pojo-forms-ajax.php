<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Ajax {
	
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

		$files = array();

		foreach ( $repeater_fields as $field_index => $field ) {
			$field_name = 'form_field_' . ( $field_index + 1 );
			// TODO: Valid by field type
			if ( $field['required'] && empty( $_POST[ $field_name ] ) && $field['type'] != 'file' ) {
				$return_array['fields'][ $field_name ] = Pojo_Forms_Messages::get_message( $form->ID, Pojo_Forms_Messages::FIELD_REQUIRED );
			}

			if ( $field['required'] && $_FILES[$field_name]['error'] == 4 && $field['type'] == 'file' ) {
				$return_array['fields'][ $field_name ] = Pojo_Forms_Messages::get_message( $form->ID, Pojo_Forms_Messages::FIELD_REQUIRED );
			} 

			if ( $field['type'] == 'file' && empty( $return_array['fields'] ) ) {

				//TODO: is there a proper way to change wp folder on ajax request ?
				$upload_dir = wp_upload_dir();
				$target_dir = wp_normalize_path($upload_dir['basedir']).'/pojo_forms_uploads';

				if ( is_dir( $target_dir ) && is_writable( $target_dir ) ) {

					$target_file = $target_dir . '/' . basename($_FILES[$field_name]["name"]);
				    if (move_uploaded_file($_FILES[$field_name]["tmp_name"], $target_file)) {
				        $files[$field_name] = $this->get_file_url( $target_file );
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
				$field_value = '';
				
				if ( isset( $_POST[ $field_name ] ) ) {
					$field_value = stripslashes_deep( $_POST[ $field_name ] );
					
					if ( is_array( $field_value ) ) {
						$field_value = implode( ', ', $field_value );
					}
				}

				if ( isset( $files[$field_name] ) )
					$field_value = $files[$field_name];

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
				
				do_action( 'pojo_forms_mail_sent', $form->ID, $field_values );
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