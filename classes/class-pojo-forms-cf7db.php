<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_CF7DB {

	public function store_submit_form( $form_id, $field_values, $files = array() ) {
		$posted_data = array();

		foreach ( $field_values as $field ) {
			if ( empty( $field['title'] ) ) {
				$field['title'] = '&nbsp;';
			}
			
			// Skip that (Will be add from `uploaded_files` later)
			//if ( isset( $field[ $field['title'] ] ) )
			//	continue;

			$posted_data[ $field['title'] ] = $field['value'];
		}
		
		$data = (object) array(
			'title' => get_the_title( $form_id ),
			'posted_data' => $posted_data,
			//'uploaded_files' => $files,
			'uploaded_files' => array(),
		);

		// Call hook to submit data
		do_action_ref_array( 'cfdb_submit', array( &$data ) );
	}

	public function __construct() {
		add_action( 'pojo_forms_mail_sent', array( &$this, 'store_submit_form' ), 20, 3 );
	}
}