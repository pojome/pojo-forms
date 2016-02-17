<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Helpers {

	public function get_client_ip() {
		$server_ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $server_ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				return $_SERVER[ $key ];
			}
		}

		// Fallback local ip.
		return '127.0.0.1';
	}
	
	public function get_all_forms() {
		$forms = new WP_Query( array(
			'post_type' => 'pojo_forms',
			'posts_per_page' => -1,
		) );
		
		$return = array();
		if ( $forms->have_posts() ) {
			$forms = $forms->get_posts();
			
			foreach ( $forms as $form ) {
				$return[ $form->ID ] = $form->post_title;
			}
		}
		
		return $return;
	}

	public function get_shortcode_text( $id ) {
		return '[pojo-form id="' . $id . '"]';
	}

	public function get_upload_dir() {
		$wp_upload_dir = wp_upload_dir();
		$path = $wp_upload_dir['basedir'] . '/pojo_forms';

		// Make sure the /pojo_forms folder is created
		wp_mkdir_p( $path );

		return apply_filters( 'pojo_forms_upload_folder', $path );
	}
}