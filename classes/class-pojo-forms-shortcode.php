<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Shortcode {
	
	protected $_form_index = 1;

	protected function _get_column_class( $width ) {
		if ( empty( $width ) )
			$width = 12;
		
		$column_class = 'column-' . $width;
		
		return $column_class;
	}
	
	protected function _get_field_html( $form_id, $field_index, $field ) {
		$field = wp_parse_args(
			$field,
			array(
				'name' => '',
				'width' => 12,
				'placeholder' => '',
				'class' => '',
				'choices' => '',
				'inline' => false,
				'default_value' => 'unchecked',
				'multiple' => false,
				'first_blank_item' => false,
				'textarea_rows' => 5,
				'number_min' => '',
				'number_max' => '',
			)
		);
		
		if ( empty( $field['type'] ) )
			$field['type'] = 'text';
		
		// Parse choices to array
		$choices = explode( "\n", $field['choices'] );
		$choices = array_map( 'trim', $choices );
		$choices = array_filter( $choices );
		
		$field_classes = array( 'field' );
		$field_name = 'form_field_' . $field_index;
		$field_id = sprintf( 'form-field-%d-%d', $this->_form_index, $field_index );
		if ( $field['required'] )
			$field_classes[] = 'required';
		
		$field_size = atmb_get_field( 'form_style_field_size', $form_id );
		$field_classes[] = 'size-' . $field_size;

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
			
			$input_color = atmb_get_field( 'form_style_fields_input_color', $form_id );
			if ( ! empty( $input_color ) ) {
				$field_style_inline[] = 'color:' . $input_color;
			}

			$border_width = atmb_get_field( 'form_style_fields_border_width', $form_id );
			if ( ! empty( $border_width ) ) {
				$field_style_inline[] = 'border-width:' . $border_width;
			}

			$border_radius = atmb_get_field( 'form_style_fields_border_radius', $form_id );
			if ( ! empty( $border_radius ) ) {
				$field_style_inline[] = 'border-radius:' . $border_radius;
			}
		}
		
		$container_classes = array(
			'field-group',
			$field['class'],
			$field_name,
			$column_class,
		);

		// Remove empty values
		$container_classes = array_filter( $container_classes );

		$field_html = '';
		
		switch ( $field['type'] ) {
			// Text field (default).
			case 'text' :
			case 'email' :
			case 'url' : 
			case 'tel' : 
			case 'number' :
			case 'file' :
				
				$field_attributes = array(
					'type' => $field['type'],
					'id' => $field_id,
					'name' => $field_name,
					'class' => implode( ' ', $field_classes ),
					'style' => implode( ';', $field_style_inline ),
					'placeholder' => esc_attr( $field['placeholder'] ),
				);

				if ( $field['required'] )
					$field_attributes['aria-required'] = 'true';
				
				if ( 'number' === $field['type'] ) {
					$field_attributes['min'] = $field['number_min'];
					$field_attributes['max'] = $field['number_max'];
				}
				
				if ( 'tel' === $field['type'] ) {
					$field_attributes['pattern'] = '[0-9\.\+\-\(\)\*#]+';
					$field_attributes['title'] = __( 'Only phone numbers allowed.', 'pojo-forms' );
				}

				// Remove empty values
				$field_attributes = array_filter( $field_attributes );
	
				$field_html = sprintf(
					'<div class="%2$s">
							<label for="%1$s" class="label-field">%3$s</label>
							<input %4$s />
						</div>',
					$field_id,
					implode( ' ', $container_classes ),
					$field['name'],
					pojo_array_to_attributes( $field_attributes )
				);	
				
				break;
			
			case 'dropdown' :
				
				if ( $field['multiple'] )
					$field_name .= '[]';

				$field_attributes = array(
					'id' => $field_id,
					'name' => $field_name,
					'class' => implode( ' ', $field_classes ),
					'style' => implode( ';', $field_style_inline ),
				);

				if ( $field['required'] )
					$field_attributes['aria-required'] = 'true';
				
				if ( $field['multiple'] )
					$field_attributes['multiple'] = 'multiple';

				// Remove empty values
				$field_attributes = array_filter( $field_attributes );
				
				$options = array();
				
				foreach ( $choices as $choice_index => $choice ) {
					if ( 0 === $choice_index && $field['first_unselectable_item'] )
						$options[] = sprintf( '<option value="">%1$s</option>', $choice );
					else
						$options[] = sprintf( '<option value="%1$s">%1$s</option>', $choice );
				}

				$field_html = sprintf(
					'<div class="%2$s">
							<label for="%1$s" class="label-field">%3$s</label>
							<select %4$s>
							%5$s
							</select>
						</div>',
					$field_id,
					implode( ' ', $container_classes ),
					$field['name'],
					pojo_array_to_attributes( $field_attributes ),
					implode( '', $options )
				);

				break;
			
			case 'radio' :
			case 'checkbox' :
				
				if ( 'checkbox' === $field['type'] )
					$field_name .= '[]';

				$field_attributes = array(
					'type' => $field['type'],
					'id' => $field_id,
					'name' => $field_name,
					'class' => implode( ' ', $field_classes ),
					'style' => implode( ';', $field_style_inline ),
				);

				if ( $field['required'] )
					$field_attributes['aria-required'] = 'true';

				// Remove empty values
				$field_attributes = array_filter( $field_attributes );
				
				if ( $field['inline'] ) {
					$container_classes[] = 'field-list-inline';
				}

				$options = array();
				foreach ( $choices as $choice_index => $choice ) {
					$field_attributes['id'] = $field_id . '-' . ( $choice_index + 1 );

					$checked = false;
					if ( 'radio' === $field['type'] && 0 === $choice_index )
						$checked = true;

					if ( 'checkbox' === $field['type'] && 'checked' === $field['default_value'] )
						$checked = true;
					
					$options[] = sprintf(
						'<div class="field-list-item">
							<input %4$s value="%2$s"%5$s />
							<label for="%1$s">%3$s</label>
						</div>',
						$field_attributes['id'],
						esc_attr( $choice ),
						$choice,
						pojo_array_to_attributes( $field_attributes ), 
						( $checked ) ? ' checked' : ''
					);
				}

				$field_html = sprintf(
					'<div class="%1$s">
						<label class="label-field">%2$s</label>
						<div class="field-list-items">%3$s</div>
					</div>',
					implode( ' ', $container_classes ),
					$field['name'],
					implode( '', $options )
				);

				break;
			
			case 'textarea' :

				$field_attributes = array(
					'id' => $field_id,
					'name' => $field_name,
					'class' => implode( ' ', $field_classes ),
					'style' => implode( ';', $field_style_inline ),
					'rows' => '3',
					'placeholder' =>  esc_attr( $field['placeholder'] ),
				);

				if ( $field['required'] )
					$field_attributes['aria-required'] = 'true';

				if ( ! empty( $field['textarea_rows'] ) )
					$field_attributes['rows'] = $field['textarea_rows'];

				// Remove empty values
				$field_attributes = array_filter( $field_attributes );

				$field_html = sprintf(
					'<div class="%2$s">
						<label for="%1$s" class="label-field">%3$s</label>
						<textarea %4$s></textarea>
					</div>',
					$field_id,
					implode( ' ', $container_classes ),
					$field['name'],
					pojo_array_to_attributes( $field_attributes )
				);
				
				break;
		}

		return apply_filters( 'pojo_forms_field_' . $field['type'] . '_html_output', $field_html );
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

			$border_width = atmb_get_field( 'form_style_button_border_width', $form_id );
			if ( ! empty( $border_width ) ) {
				$style_inline[] = 'border-width:' . $border_width;
			}

			$border_radius = atmb_get_field( 'form_style_button_border_radius', $form_id );
			if ( ! empty( $border_radius ) ) {
				$style_inline[] = 'border-radius:' . $border_radius;
			}
			
			if ( ! empty( $style_inline ) )
				$button_attributes['style'] = implode( ';', $style_inline );
		}

		$forms_html = sprintf(
			'<div class="form-actions %1$s">
				<div class="pojo-button-wrap pojo-button-%2$s">
					<button %3$s>%4$s</button>
				</div>
			</div>',
			$this->_get_column_class( atmb_get_field( 'form_style_button_width', $form_id ) ),
			atmb_get_field( 'form_style_button_align', $form_id ),
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

		$recaptcha_html = '';
		$recaptcha = atmb_get_field( 'form_recaptcha_enable', $form->ID );
		if ( 'enable' === $recaptcha ) {
			$recaptcha_html .= '<div class="field-group column-12">';
			
			$recaptcha_site_key = atmb_get_field( 'form_recaptcha_site_key', $form->ID );
			$recaptcha_secret_key = atmb_get_field( 'form_recaptcha_secret_key', $form->ID );
			
			
			if ( empty( $recaptcha_site_key ) ) {
				$recaptcha_html .= __( 'ERROR for site owner: Invalid site key', 'pojo-forms' );
			} elseif ( empty( $recaptcha_secret_key ) ) {
				$recaptcha_html .= __( 'ERROR for site owner: Invalid secret key', 'pojo-forms' );
			} else {
				wp_enqueue_script( 'recaptcha-api' );
				
				$recaptcha_attributes = array(
					'class' => 'pojo-g-recaptcha',
					'data-sitekey' => $recaptcha_site_key,
				);
				
				$recaptcha_style = atmb_get_field( 'form_recaptcha_style', $form->ID );
				if ( ! empty( $recaptcha_style ) ) {
					$recaptcha_attributes['data-theme'] = $recaptcha_style;
				}
				
				$recaptcha_size = atmb_get_field( 'form_recaptcha_size', $form->ID );
				if ( ! empty( $recaptcha_size ) ) {
					$recaptcha_attributes['data-size'] = $recaptcha_size;
				}
				
				$recaptcha_html .= '<div ' . pojo_array_to_attributes( $recaptcha_attributes ) . '></div>';
			}

			$recaptcha_html .= '</div>';
		}

		$forms_html = '<div class="columns">';
		$forms_html .= implode( "\n", $rows );
		$forms_html .= $recaptcha_html;
		$forms_html .= $this->_get_button_html( $form->ID );
		$forms_html .= '</div>';
		
		$form_align_text = atmb_get_field( 'form_style_align_text', $form->ID );
		if ( empty( $form_align_text ) || ! in_array( $form_align_text, array( 'top', 'inside', 'right', 'left' ) ) )
			$form_align_text = 'top';
		
		$form_style_inline = array();
		$fields_style = atmb_get_field( 'form_style_fields_style', $form->ID );
		if ( 'custom' === $fields_style ) {
			$label_size = atmb_get_field( 'form_style_fields_lbl_size', $form->ID );
			if ( ! empty( $text_size ) ) {
				$form_style_inline[] = 'font-size:' . $label_size;
			}
			
			$label_color = atmb_get_field( 'form_style_fields_lbl_color', $form->ID );
			if ( ! empty( $label_color ) ) {
				$form_style_inline[] = 'color:' . $label_color;
			}
		}

		$edit_form_link = '';
		if ( current_user_can( 'publish_posts' ) && ! is_admin() ) {
			$edit_form_link = sprintf( '<a href="%s" class="button size-small edit-form edit-link"><i class="fa fa-pencil"></i> %s</a>', get_edit_post_link( $form->ID ), __( 'Edit Form', 'pojo-forms' ) );
		}
		
		$forms_html = sprintf(
			'<form class="pojo-form pojo-form-%3$d pojo-form-ajax form-align-%1$s"%2$s action="" method="post">
			<input type="hidden" name="action" value="pojo_form_contact_submit" />
			<input type="hidden" name="form_id" value="%3$d" />
			%4$s
			%5$s
			%6$s
			</form>',
			$form_align_text,
			! empty( $form_style_inline ) ? ' style="' . implode( ';', $form_style_inline ) . '"' : '',
			$form->ID,
			wp_referer_field( false ),
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