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
			$return_array['message'] = __( 'Invalid form.', 'pojo-forms' );
			wp_send_json_error( $return_array );
		}

		$form = get_post( absint( $_POST['form_id'] ) );
		
		if ( ! $form || 'pojo_forms' !== $form->post_type || ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'contact-form-send-' . $form->ID ) ) {
			$return_array['message'] = __( 'Invalid form.', 'pojo-forms' );
			wp_send_json_error( $return_array );
		}

		$repeater_fields = atmb_get_field_without_type( 'fields', 'form_',  $form->ID );
		if ( empty( $repeater_fields ) ) {
			$return_array['message'] = __( 'Invalid form.', 'pojo-forms' );
			wp_send_json_error( $return_array );
		}

		foreach ( $repeater_fields as $field_index => $field ) {
			$field_name = 'form_field_' . ( $field_index + 1 );
			// TODO: Valid by field type
			if ( $field['required'] && empty( $_POST[ $field_name ] ) ) {
				$return_array['fields'][ $field_name ] = __( 'This field is required', 'pojo-forms' );
			}
		}

		if ( empty( $return_array['fields'] ) ) {
			$email_to = trim( atmb_get_field( 'form_email_to', $form->ID ) );
			$email_subject = trim( atmb_get_field( 'form_email_subject', $form->ID ) );
			if ( ! is_email( $email_to ) || empty( $email_subject ) ) {
				$return_array['message'] = __( 'Problem with Form setting.', 'pojo-forms' );
				wp_send_json_error( $return_array );
			}
			
			$email_html = '';
			$inline_shortcodes = array();
			foreach ( $repeater_fields as $field_index => $field ) {
				$field_name = 'form_field_' . ( $field_index + 1 );
				$field_value = '';
				
				if ( isset( $_POST[ $field_name ] ) ) {
					$field_value = $_POST[ $field_name ];
					
					if ( is_array( $field_value ) ) {
						$field_value = implode( ', ', $field_value );
					} else {
						$field_value = nl2br( $field_value );
					}
				}

				$inline_shortcodes[ $field['shortcode'] ] = $field_value;
				
				$email_html .= sprintf(
					'%s: %s' . PHP_EOL,
					$field['name'],
					$field_value
				);
			}
			
			$metadata_types = atmb_get_field( 'form_metadata', $form->ID, Pojo_MetaBox::FIELD_CHECKBOX_LIST );
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
						$email_html .= sprintf( $tmpl_line_html, __( 'Page URL', 'pojo-forms' ), home_url( $_POST['_wp_http_referer'] ) );
						break;
					
					case 'user_agent' :
						$email_html .= sprintf( $tmpl_line_html, __( 'User Agent', 'pojo-forms' ), $_SERVER['HTTP_USER_AGENT'] );
						break;
					
					case 'remote_ip' :
						$email_html .= sprintf( $tmpl_line_html, __( 'Remote IP', 'pojo-forms' ), $_SERVER['REMOTE_ADDR'] );
						break;
					
					case 'credit' :
						$email_html .= __( 'Powered by http://pojo.me/', 'pojo-forms' ) . PHP_EOL;
						break;
				}
			}
			
			$email_from_name = atmb_get_field( 'form_email_form_name', $form->ID );
			if ( empty( $email_from_name ) )
				$email_from_name = get_bloginfo( 'name' );
			
			$email_from = atmb_get_field( 'form_email_form', $form->ID );
			if ( empty( $email_from ) )
				$email_from = get_bloginfo( 'admin_email' );
			
			$email_reply_to = atmb_get_field( 'form_email_reply_to', $form->ID );
			if ( empty( $email_reply_to ) )
				$email_reply_to = $email_from;

			$email_subject = strtr( $email_subject, $inline_shortcodes );
			$email_from_name = strtr( $email_from_name, $inline_shortcodes );
			$email_from = strtr( $email_from, $inline_shortcodes );
			$email_reply_to = strtr( $email_reply_to, $inline_shortcodes );

			//$headers = sprintf( 'From: %s <%s>;' . "\r\n" . 'content-type: text/html;' . "\r\n", $email_from_name, $email_from );
			$headers = sprintf( 'From: %s <%s>;' . "\r\n", $email_from_name, $email_from );
			$headers .= sprintf( 'Reply-To: %s <%s>;' . "\r\n", $email_from_name, $email_reply_to );
			
			wp_mail( $email_to, $email_subject, $email_html, $headers );
			
			$redirect_to = atmb_get_field( 'form_redirect_to', $form->ID );
			if ( empty( $redirect_to ) || ! filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
				$redirect_to = '';
			}
			
			$return_array['link'] = $redirect_to;
			$return_array['message'] = __( 'Your details were sent successfully!', 'pojo-forms' );
			wp_send_json_success( $return_array );
		} else {
			$return_array['message'] = __( 'This form has an error, please fix it.', 'pojo-forms' );
			wp_send_json_error( $return_array );
		}
		
		wp_send_json_error( $return_array );
		die();
	}
	
	public function __construct() {
		add_action( 'wp_ajax_form_preview_shortcode', array( &$this, 'preview_shortcode' ) );
		add_action( 'wp_ajax_pojo_form_contact_submit', array( &$this, 'form_contact_submit' ) );
		add_action( 'wp_ajax_nopriv_pojo_form_contact_submit', array( &$this, 'form_contact_submit' ) );
	}
	
}