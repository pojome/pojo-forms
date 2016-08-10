<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_ReCAPTCHA {

	public function register_scripts() {
		wp_register_script( 'recaptcha-api', 'https://www.google.com/recaptcha/api.js?onload=pojoOnloadReCAPTCHACallback&render=explicit', array(), false, true );
	}

	public function register_form_recaptcha_metabox( $meta_boxes = array() ) {
		$fields = array();

        $fields[] = array(
            'id' => 'recaptcha_description',
            'type' => Pojo_MetaBox::FIELD_RAW_HTML,
            'title' => __( 'What is reCAPTCHA', 'pojo-forms' ),
            'raw' => sprintf( __( '<a href="%s">reCAPTCHA is a free service by Google</a> that protects your website from spam and abuse. It does this while letting your valid users pass through with ease.', 'pojo-forms' ), 'https://www.google.com/recaptcha/' ),
        );

        $fields[] = array(
			'id' => 'enable',
			'title' => __( 'Enable reCAPTCHA', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'classes' => array( 'select-show-or-hide-fields' ),
			'options' => array(
				'' => __( 'Disable', 'pojo-forms' ),
				'enable' => __( 'Enable', 'pojo-forms' ),
			),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'site_key',
			'title' => __( 'Site key', 'pojo-forms' ),
			'classes_field' => array( 'large-text' ),
			'show_on' => array( 'form_recaptcha_enable' => 'enable' ),
		);

		$fields[] = array(
			'id' => 'secret_key',
			'title' => __( 'Secret key', 'pojo-forms' ),
			'classes_field' => array( 'large-text' ),
			'show_on' => array( 'form_recaptcha_enable' => 'enable' ),
		);

		$fields[] = array(
			'id' => 'style',
			'title' => _x( 'Style', 'pojo-forms-recaptcha', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'' => __( 'Light', 'pojo-forms' ),
				'dark' => __( 'Dark', 'pojo-forms' ),
			),
			'show_on' => array( 'form_recaptcha_enable' => 'enable' ),
		);

		$fields[] = array(
			'id' => 'size',
			'title' => __( 'Size', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'' => __( 'Normal', 'pojo-forms' ),
				'compact' => __( 'Compact', 'pojo-forms' ),
			),
			'show_on' => array( 'form_recaptcha_enable' => 'enable' ),
		);

		$meta_boxes[] = array(
			'id' => 'pojo-forms-recaptcha',
			'title' => __( 'reCAPTCHA Options', 'pojo-forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context' => 'side',
			'prefix' => 'form_recaptcha_',
			'fields' => $fields,
		);

		return $meta_boxes;
	}

	public function mail_validation( $form_id ) {
		$recaptcha = atmb_get_field( 'form_recaptcha_enable', $form_id );
		if ( 'enable' === $recaptcha ) {
			if ( empty( $_POST['g-recaptcha-response'] ) ) {
				wp_send_json_error( array( 'message' => __( 'The Captcha field cannot be blank. Please enter a value.', 'pojo-forms' ) ) );
			}
			
			$recaptcha_errors = array(
				'missing-input-secret' => __( 'The secret parameter is missing.', 'pojo-forms' ),
				'invalid-input-secret' => __( 'The secret parameter is invalid or malformed.', 'pojo-forms' ),
				'missing-input-response' => __( 'The response parameter is missing.', 'pojo-forms' ),
				'invalid-input-response' => __( 'The response parameter is invalid or malformed.', 'pojo-forms' ),
			);

			$recaptcha_response = $_POST['g-recaptcha-response'];
			$recaptcha_secret = atmb_get_field( 'form_recaptcha_secret_key', $form_id );
			$client_ip = POJO_FORMS()->helpers->get_client_ip();

			$request = array(
				'body' => array(
					'secret' => $recaptcha_secret,
					'response' => $recaptcha_response,
					'remoteip' => $client_ip,
				),
			);

			$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', $request );

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				wp_send_json_error( array( 'message' => sprintf( __( 'Can not connect to the reCAPTCHA server (%d).', 'pojo-forms' ), $response_code ) ) );
			}

			$body = wp_remote_retrieve_body( $response );
			$result = json_decode( $body, true );

			if ( ! $result['success'] ) {
				$message = __( 'Invalid Form', 'pojo-forms' );

				$result_errors = array_flip( $result['error-codes'] );
				foreach ( $recaptcha_errors as $error_key => $error_desc ) {
					if ( isset( $result_errors[ $error_key ] ) ) {
						$message = $recaptcha_errors[ $error_key ];
						break;
					}
				}
				wp_send_json_error( array( 'message' => $message ) );
			}
		}
	}

	public function __construct() {
		// Enqueue Scripts
		add_action( 'pojo_forms_load_front_assets', array( &$this, 'register_scripts' ) );
		
		// Register Fields in the Metabox
		add_filter( 'pojo_meta_boxes', array( &$this, 'register_form_recaptcha_metabox' ), 45 );
		
		// Validation the form if necessary
		add_action( '__pojo_forms_mail_validation', array( &$this, 'mail_validation' ) );
	}
}