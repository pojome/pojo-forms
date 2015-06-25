<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Helpers {
	
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
	
}