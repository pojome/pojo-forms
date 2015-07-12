<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Pojo_Forms_Aal
 * 
 * Integration with Activity Log
 */
class Pojo_Forms_Aal {

	public function aal_init_roles( $roles ) {
		$roles['manage_options'][] = 'Forms';
		
		return $roles;
	}

	public function hook_mail_sent_or_blocked( $form_id ) {
		$is_blocked = 'pojo_forms_mail_blocked' === current_filter();

		aal_insert_log(
			array(
				'action' => $is_blocked ? 'blocked' : 'sent',
				'object_type' => 'Forms',
				'object_id' => $form_id,
				'object_name' => get_the_title( $form_id ),
			)
		);
	}

	public function __construct() {
		add_filter( 'aal_init_roles', array( &$this, 'aal_init_roles' ) );
		
		add_action( 'pojo_forms_mail_sent', array( &$this, 'hook_mail_sent_or_blocked' ) );
		add_action( 'pojo_forms_mail_blocked', array( &$this, 'hook_mail_sent_or_blocked' ) );
		
	}
	
}