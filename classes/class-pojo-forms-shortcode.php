<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Shortcode {
	
	protected $_form_id = 1;
	
	public function do_shortcode( $atts = array() ) {
		$atts = wp_parse_args( $atts, array(
			'id' => 0,
		) );
		
		if ( empty( $atts['id'] ) || ! $form = get_post( $atts['id'] ) )
			return '';
		
		if ( 'pojo_forms' !== $form->post_type )
			return '';

		$repeater_fields = atmb_get_field_without_type( 'fields', 'form_',  $form->ID );
		
		if ( empty( $repeater_fields ) )
			return '';
		
		$rows = array();
		foreach ( $repeater_fields as $field_index => $field ) {
			$field_classes = array( 'field' );
			$field_name = 'form_field_' . $field_index;
			$field_id = sprintf( 'form-field-%d-%d', $this->_form_id, $field_index ); 
			$placeholder = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
			if ( $field['required'] )
				$field_classes[] = 'required';
			
			if ( empty( $field['type'] ) )
				$field['type'] = 'text';
			
			$field_html = '';
			if ( in_array( $field['type'], array( 'text', 'email', 'url', 'tel' ) ) ) { // Text field (default).
				$field_html = sprintf(
					'<div class="field-group %3$s">
						<label for="%4$s">%1$s%7$s</label>
						<input type="%2$s" id="%4$s" name="%3$s" class="%5$s"%6$s />
					</div>',
					$field['name'],
					$field['type'],
					$field_name,
					$field_id,
					implode( ' ', $field_classes ),
					$placeholder,
					$field['required'] ? ' (<span class="span-required">*</span>)' : ''
				);
			} elseif ( 'textarea' === $field['type'] ) { // Textarea
				$field_html = sprintf(
					'<div class="field-group %2$s">
						<label for="%3$s">%1$s%6$s</label>
						<textarea rows="3" name="%2$s" id="%3$s" class="%4$s"%5$s></textarea>
					</div>',
					$field['name'],
					$field_name,
					$field_id,
					implode( ' ', $field_classes ),
					$placeholder,
					$field['required'] ? ' <span class="span-required">*</span>' : ''
				);
			}
			if ( ! empty( $field_html ) )
				$rows[] = $field_html;
		}
		
		if ( empty( $rows ) )
			return '';
		
		$forms_html = implode( "\n", $rows );
		
		$button_color = atmb_get_field( 'form_send_button_color', $form->ID );
		if ( empty( $button_color ) )
			$button_color = 'ffffff';
		$forms_html .= sprintf(
			'<div class="form-actions">
				<button class="button submit color-%1$s" type="submit">%2$s</button>
			</div>',
			$button_color,
			atmb_get_field( 'form_send_button_text', $form->ID )
		);
		
		$form_align_text = atmb_get_field( 'form_frm_align_text', $form->ID );
		if ( empty( $form_align_text ) || ! in_array( $form_align_text, array( 'top', 'inside', 'right', 'left' ) ) )
			$form_align_text = 'top';

		$edit_form_link = '';
		if ( current_user_can( 'publish_posts' ) && ! is_admin() )
			$edit_form_link = sprintf( '<a href="%s" class="button size-small edit-form edit-link"><i class="fa fa-pencil"></i> %s</a>', admin_url( 'post.php?post=' . $form->ID . '&action=edit' ), __( 'Edit Form', 'forms' ) );
		
		$forms_html = sprintf(
			'<form class="form form-ajax form-align-%s" action="" method="post" role="form">
			<input type="hidden" name="action" value="contact_form_submit" />
			<input type="hidden" name="form_id" value="%d" />
			%s
			%s
			%s
			</form>',
			$form_align_text,
			$form->ID,
			wp_nonce_field( 'contact-form-send-' . $form->ID, '_nonce', true, false ),
			$forms_html,
			$edit_form_link
		);
		
		$this->_form_id++;
		
		return $forms_html;
	}

	public function __construct() {
		add_shortcode( 'pojo-form', array( &$this, 'do_shortcode' ) );
	}
	
}