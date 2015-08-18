<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Messages {

	const SUCCESS = 0;
	const ERROR = 1;
	const FIELD_REQUIRED = 2;
	const INVALID_FORM = 3;

	public static function get_default_messages() {
		return array(
			self::SUCCESS => __( 'Your details were sent successfully!', 'pojo-forms' ),
			self::ERROR => __( 'This form has an error, please fix it!', 'pojo-forms' ),
			self::FIELD_REQUIRED => __( 'This field is required', 'pojo-forms' ),
			self::INVALID_FORM => __( 'Invalid form.', 'pojo-forms' ),
		);
	}
	
	public static function get_default_message( $id ) {
		$default_messages = self::get_default_messages();
		return isset( $default_messages[ $id ] ) ? $default_messages[ $id ] : __( 'Unknown', 'pojo-forms' );
	}

	public static function get_message( $form_id, $id ) {
		$message_type = atmb_get_field( 'form_messages', $form_id );
		if ( empty( $message_type ) )
			return self::get_default_message( $id );
		
		$message = atmb_get_field( 'form_message_' . $id, $form_id );
		
		if ( empty( $message ) )
			return self::get_default_message( $id );
		
		return $message;
	}
	
}