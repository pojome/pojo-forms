<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Shortcode {
	
	protected $_form_index = 1;

	protected function _get_column_class( $width ) {
		switch ( $width ) {
			case '5' :
				$column_class = 'column-2-5';
				break;

			default :
				$column_class = 'column-' . ( 12 / $width );
				break;
		}
		
		return $column_class;
	}
	
	protected function _get_field_html( $form_id, $field_index, $field ) {
		$field_classes = array( 'field' );
		$field_name = 'form_field_' . $field_index;
		$field_id = sprintf( 'form-field-%d-%d', $this->_form_index, $field_index );
		if ( $field['required'] )
			$field_classes[] = 'required';
		
		if ( empty( $field['type'] ) )
			$field['type'] = 'text';
		
		if ( empty( $field['width'] ) )
			$field['width'] = 1;

		$column_class = $this->_get_column_class( $field['width'] );

		$field_style_inline = array();

		$fields_style = atmb_get_field( 'form_style_fields_style', $form_id );
		if ( 'custom' === $fields_style ) {
			$bg_color = atmb_get_field( 'form_style_fields_bg_color', $form_id );
			if ( ! empty( $bg_color ) ) {
				$bg_opacity = atmb_get_field( 'form_style_fields_bg_opacity', $form_id );
				if ( empty( $bg_opacity ) && '0' !== $bg_opacity )
					$bg_opacity = 100;

				$rgb_color = pojo_hex2rgb( $bg_color );
				$color_value = sprintf( 'rgba(%d,%d,%d,%s)', $rgb_color[0], $rgb_color[1], $rgb_color[2], ( absint( $bg_opacity ) / 100 ) );
				$field_style_inline[] = 'background-color:' . $color_value;
			}

			$border_color = atmb_get_field( 'form_style_fields_border_color', $form_id );
			if ( ! empty( $border_color ) ) {
				$field_style_inline[] = 'border-color:' . $border_color;
			}
		}

		$field_html = '';
		if ( in_array( $field['type'], array( 'text', 'email', 'url', 'tel' ) ) ) { // Text field (default).
			$field_attributes = array(
				'type' => $field['type'],
				'id' => $field_id,
				'name' => $field_name,
				'class' => implode( ' ', $field_classes ),
				'style' => implode( ';', $field_style_inline ),
				'placeholder' => ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
			);
			
			// Remove empty values
			$field_attributes = array_filter( $field_attributes );
			
			$field_html = sprintf(
				'<div class="field-group %2$s %3$s">
						<label for="%1$s">%4$s</label>
						<input %5$s />
					</div>',
				$field_id,
				$field_name,
				$column_class,
				$field['name'],
				pojo_array_to_attributes( $field_attributes )
			);
		} elseif ( 'textarea' === $field['type'] ) { // Textarea
			$field_attributes = array(
				'id' => $field_id,
				'name' => $field_name,
				'class' => implode( ' ', $field_classes ),
				'style' => implode( ';', $field_style_inline ),
				'rows' => '3',
				'placeholder' => ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '',
			);

			// Remove empty values
			$field_attributes = array_filter( $field_attributes );

			$field_html = sprintf(
				'<div class="field-group %2$s %3$s">
						<label for="%1$s">%4$s</label>
						<textarea %5$s></textarea>
					</div>',
				$field_id,
				$field_name,
				$column_class,
				$field['name'],
				pojo_array_to_attributes( $field_attributes )
			);
		}
		
		return $field_html;
	}

	public function _get_button_html( $form_id ) {
		$button_size = atmb_get_field( 'form_style_button_size', $form_id );
		$button_classes = array(
			'button',
			'submit',
			'size-' . $button_size,
		);
		
		$button_attributes = array(
			'class' => implode( ' ', $button_classes ),
			'type' => 'submit',
		);
		
		$button_style = atmb_get_field( 'form_style_button_style', $form_id );
		if ( 'custom' === $button_style ) {
			$style_inline = array();
			
			$bg_color = atmb_get_field( 'form_style_button_bg_color', $form_id );
			if ( ! empty( $bg_color ) ) {
				$bg_opacity = atmb_get_field( 'form_style_button_bg_opacity', $form_id );
				if ( empty( $bg_opacity ) && '0' !== $bg_opacity )
					$bg_opacity = 100;

				$rgb_color = pojo_hex2rgb( $bg_color );
				$color_value = sprintf( 'rgba(%d,%d,%d,%s)', $rgb_color[0], $rgb_color[1], $rgb_color[2], ( absint( $bg_opacity ) / 100 ) );
				$style_inline[] = 'background-color:' . $color_value;
			}

			$border_color = atmb_get_field( 'form_style_button_border_color', $form_id );
			if ( ! empty( $border_color ) ) {
				$style_inline[] = 'border-color:' . $border_color;
			}

			$text_color = atmb_get_field( 'form_style_button_text_color', $form_id );
			if ( ! empty( $text_color ) ) {
				$style_inline[] = 'color:' . $text_color;
			}
			
			if ( ! empty( $style_inline ) )
				$button_attributes['style'] = implode( ';', $style_inline );
		}

		$forms_html = sprintf(
			'<div class="form-actions pojo-button-%1$s %2%s">
				<button %3$s>%4$s</button>
			</div>',
			atmb_get_field( 'form_style_button_align', $form_id ),
			$this->_get_column_class( atmb_get_field( 'form_style_button_width', $form_id ) ),
			pojo_array_to_attributes( $button_attributes ),
			atmb_get_field( 'form_style_button_text', $form_id )
		);
		
		return $forms_html;
	}
	
	public function do_shortcode( $atts = array() ) {
		$atts = wp_parse_args( $atts, array( 'id' => 0 ) );
		
		if ( empty( $atts['id'] ) )
			return '';

		$form = get_post( $atts['id'] );
			
		if ( ! $form || 'pojo_forms' !== $form->post_type )
			return '';

		$repeater_fields = atmb_get_field_without_type( 'fields', 'form_',  $form->ID );
		
		if ( empty( $repeater_fields ) )
			return '';
		
		$rows = array();
		foreach ( $repeater_fields as $field_index => $field ) {
			$field_html = $this->_get_field_html( $form->ID, $field_index + 1, $field );
			if ( ! empty( $field_html ) )
				$rows[] = $field_html;
		}
		
		// No found any fields, so return empty string
		if ( empty( $rows ) )
			return '';

		$forms_html = '<div class="columns">';
		$forms_html .= implode( "\n", $rows );
		$forms_html .= $this->_get_button_html( $form->ID );
		$forms_html .= '</div>';
		
		$form_align_text = atmb_get_field( 'form_style_align_text', $form->ID );
		if ( empty( $form_align_text ) || ! in_array( $form_align_text, array( 'top', 'inside', 'right', 'left' ) ) )
			$form_align_text = 'top';
		
		$form_style_inline = array();
		$fields_style = atmb_get_field( 'form_style_fields_style', $form->ID );
		if ( 'custom' === $fields_style ) {
			$text_color = atmb_get_field( 'form_style_fields_text_color', $form->ID );
			if ( ! empty( $text_color ) ) {
				$form_style_inline[] = 'color:' . $text_color;
			}
			
			$text_size = atmb_get_field( 'form_style_fields_text_size', $form->ID );
			if ( ! empty( $text_size ) ) {
				$form_style_inline[] = 'font-size:' . $text_size;
			}
		}

		$edit_form_link = '';
		if ( current_user_can( 'publish_posts' ) && ! is_admin() ) {
			$edit_form_link = sprintf( '<a href="%s" class="button size-small edit-form edit-link"><i class="fa fa-pencil"></i> %s</a>', get_edit_post_link( $form->ID ), __( 'Edit Form', 'pojo-forms' ) );
		}
		
		$forms_html = sprintf(
			'<form class="pojo-form pojo-form-ajax form-align-%s"%s action="" method="post" role="form">
			<input type="hidden" name="action" value="pojo_form_contact_submit" />
			<input type="hidden" name="form_id" value="%d" />
			%s
			%s
			%s
			</form>',
			$form_align_text,
			! empty( $form_style_inline ) ? ' style="' . implode( ';', $form_style_inline ) . '"' : '',
			$form->ID,
			wp_nonce_field( 'contact-form-send-' . $form->ID, '_nonce', true, false ),
			$forms_html,
			$edit_form_link
		);
		
		$this->_form_index++;
		
		return $forms_html;
	}

	public function __construct() {
		add_shortcode( 'pojo-form', array( &$this, 'do_shortcode' ) );
	}
	
}