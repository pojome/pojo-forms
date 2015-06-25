<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Ajax {
	
	public function preview_shortcode() {
		global $pojo_forms;
		
		if ( empty( $_POST['id'] ) ) {
			echo 'No found.';
			die();
		}
		
		$embed = new Pojo_Embed_Template();
		echo $embed->get_header();
		echo do_shortcode( $pojo_forms->helpers->get_shortcode_text( $_POST['id'] ) );
		echo $embed->get_footer();
		
		die();
	}
	
	public function contact_form_submit() {
		$ajax_request = new ATMC_AJAX_Requests();
		if ( empty( $_POST['form_id'] ) || ! $form = get_post( absint( $_POST['form_id'] ) ) ) {
			$ajax_request->set_message( __( 'Invalid form.', 'forms' ) );
			$ajax_request->print_json( true );
		}
		
		if ( 'pojo_forms' !== $form->post_type || ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'contact-form-send-' . $form->ID ) ) {
			$ajax_request->set_message( __( 'Invalid form.', 'forms' ) );
			$ajax_request->print_json( true );
		}

		$repeater_fields = atmb_get_field_without_type( 'fields', 'form_',  $form->ID );
		if ( empty( $repeater_fields ) ) {
			$ajax_request->set_message( __( 'Invalid form.', 'forms' ) );
			$ajax_request->print_json( true );
		}

		foreach ( $repeater_fields as $field_index => $field ) {
			$field_name = 'form_field_' . $field_index;
			// TODO: Valid by field type
			if ( $field['required'] && empty( $_POST[ $field_name ] ) ) {
				$ajax_request->set_field_error( $field_name, __( 'This field is required', 'forms' ) );
			}
		}

		if ( ! $ajax_request->is_have_field_errors() ) {
			$email_to = trim( atmb_get_field( 'form_email_to', $form->ID ) );
			$email_subject = trim( atmb_get_field( 'form_email_subject', $form->ID ) );
			if ( ! is_email( $email_to ) || empty( $email_subject ) ) {
				$ajax_request->set_message( __( 'Problem with Form setting.', 'forms' ) );
				$ajax_request->print_json( true );
			}
			
			
			$email_html = '';
			foreach ( $repeater_fields as $field_index => $field ) {
				$field_name = 'form_field_' . $field_index;
				$email_html .= sprintf(
					'<div><strong>%s:</strong> %s</div>',
					$field['name'],
					isset( $_POST[ $field_name ] ) ? nl2br( $_POST[ $field_name ] ) : ''
				);
			}
			
			$email_html .= '<div><br /><br />' . sprintf( __( 'Sent on: %s', 'forms' ), date( 'H:i // d/m/Y', current_time( 'timestamp' ) ) ) . '</div>';
			$email_html .= '<div>' . sprintf( __( 'via <a href="%s">%s</a> / %s' ), home_url( '/' ), get_bloginfo( 'name' ), home_url( $_POST['_wp_http_referer'] ) ) . '</div>';
			
			$email_html .= '<div style="color:#999999;"><hr style="height: 1px; border: 0; border-top: 1px solid #eeeeee;">' .
				sprintf( __( 'Powered by <a style="color:#B40B51;" href="%s">Pojo.me</a>', 'forms' ), 'http://pojo.me/' ) .
			'</div>';

			$headers = sprintf( 'From: %s <%s>' . "\r\n".'content-type: text/html' . "\r\n", get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ) );
			
			wp_mail( $email_to, $email_subject, $email_html, $headers );
			
			$ajax_request->set_status( ATMC_AJAX_Requests::STATUS_SUCCESS );
			$ajax_request->set_message( __( 'Your details were sent successfully!', 'forms' ) );
			$redirect_to = atmb_get_field( 'form_redirect_to', $form->ID );
			if ( empty( $redirect_to ) || ! filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
				$redirect_to = '';
			}
			$ajax_request->set_link( $redirect_to );
			
		} else {
			$ajax_request->set_message( __( 'This form has an error, please fix it.', 'forms' ) );
		}
		$ajax_request->print_json( true );
		
		die();
	}
	
	public function __construct() {
		add_action( 'wp_ajax_form_preview_shortcode', array( &$this, 'preview_shortcode' ) );
		add_action( 'wp_ajax_contact_form_submit', array( &$this, 'contact_form_submit' ) );
		add_action( 'wp_ajax_nopriv_contact_form_submit', array( &$this, 'contact_form_submit' ) );
	}
	
}