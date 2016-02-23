<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_CPT {

	public function init() {
		// CPT: pojo_forms.
		$labels = array(
			'name'               => __( 'Forms', 'pojo-forms' ),
			'singular_name'      => __( 'Form', 'pojo-forms' ),
			'add_new'            => __( 'Add New', 'pojo-forms' ),
			'add_new_item'       => __( 'Add New Form', 'pojo-forms' ),
			'edit_item'          => __( 'Edit Form', 'pojo-forms' ),
			'new_item'           => __( 'New Form', 'pojo-forms' ),
			'all_items'          => __( 'All Forms', 'pojo-forms' ),
			'view_item'          => __( 'View Form', 'pojo-forms' ),
			'search_items'       => __( 'Search Form', 'pojo-forms' ),
			'not_found'          => __( 'No forms found', 'pojo-forms' ),
			'not_found_in_trash' => __( 'No forms found in Trash', 'pojo-forms' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Forms', 'pojo-forms' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 50,
			'supports'           => array( 'title' ),
		);
		register_post_type( 'pojo_forms',
			apply_filters( 'pojo_register_post_type_forms', $args )
		);
		
		remove_post_type_support( 'pojo_forms', 'editor' );
	}

	public function post_updated_messages( $messages ) {
		global $post;

		$messages['pojo_forms'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Form updated.', 'pojo-forms' ),
			2  => __( 'Custom field updated.', 'pojo-forms' ),
			3  => __( 'Custom field deleted.', 'pojo-forms' ),
			4  => __( 'Form updated.', 'pojo-forms' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Form restored to revision from %s', 'pojo-forms' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Form published.', 'pojo-forms' ),
			7  => __( 'Form saved.', 'pojo-forms' ),
			8  => __( 'Form submitted.', 'pojo-forms' ),
			9  => sprintf( __( 'Post scheduled for: <strong>%1$s</strong>.', 'pojo-forms' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'pojo-forms' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Form draft updated.', 'pojo-forms' ),
		);

		return $messages;
	}

	public function admin_cpt_columns( $columns ) {
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title Form', 'pojo-forms' ),
			'form_preview' => __( 'Preview Form', 'pojo-forms' ),
			'form_count' => __( 'Fields', 'pojo-forms' ),
			'form_shortcode' => __( 'Shortcode', 'pojo-forms' ),
			'date' => __( 'Date', 'pojo-forms' ),
		);
	}

	public function custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case 'form_preview' :
				printf( '<a href="javascript:void(0);" class="btn-admin-preview-shortcode" data-action="form_preview_shortcode" data-id="%d">%s</a>', $post->ID, __( 'Preview', 'pojo-forms' ) );
				break;

			case 'form_count' :
				echo sizeof( atmb_get_field_without_type( 'fields', 'form_',  $post->ID ) );
				break;

			case 'form_shortcode' :
				echo POJO_FORMS()->helpers->get_shortcode_text( $post->ID );
				break;
		}
	}
	
	public function dashboard_glance_items( $elements ) {
		$post_type = 'pojo_forms';
		$num_posts = wp_count_posts( $post_type );
		if ( $num_posts && $num_posts->publish ) {
			$text = _n( '%s Form', '%s Forms', $num_posts->publish, 'pojo-forms' );
			$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );
			printf( '<li class="%1$s-count"><a href="edit.php?post_type=%1$s">%2$s</a></li>', $post_type, $text );
		}
	}

	public function register_form_fields_metabox( $meta_boxes = array() ) {
		$repeater_fields = $fields = array();

		$repeater_fields[] = array(
			'id' => 'type',
			'title' => __( 'Field Type', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'text' => __( 'Text', 'pojo-forms' ),
				'email' => __( 'Email', 'pojo-forms' ),
				'textarea' => __( 'Textarea', 'pojo-forms' ),
				'tel' => __( 'Tel', 'pojo-forms' ),
				'checkbox' => __( 'Checkbox', 'pojo-forms' ),
				'radio' => __( 'Radio', 'pojo-forms' ),
				'dropdown' => __( 'Drop-down', 'pojo-forms' ),
				'number' => __( 'Number', 'pojo-forms' ),
				'url' => __( 'URL', 'pojo-forms' ),
				'file' => __( 'File Upload', 'pojo-forms' ),
			),
			'std' => 'text',
		);

		$repeater_fields[] = array(
			'id' => 'name',
			'title' => __( 'Field Label (Required)', 'pojo-forms' ),
			'std' => '',
		);
		
		// Custom elements per field type
		$repeater_fields[] = array(
			'id' => 'choices',
			'title' => __( 'Options', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_TEXTAREA,
			'classes_field' => array( 'large-text' ),
			'desc' => __( 'One option per line', 'pojo-forms' ),
			'std' => '',
		);
		
		$repeater_fields[] = array(
			'id' => 'inline',
			'title' => __( 'List Inline', 'pojo-forms' ) . ':',
			'type' => Pojo_MetaBox::FIELD_CHECKBOX,
			'std' => false,
		);
		
		$repeater_fields[] = array(
			'id' => 'default_value',
			'title' => __( 'Default Value', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'unchecked' => __( 'Unchecked', 'pojo-forms' ),
				'checked' => __( 'Checked', 'pojo-forms' ),
			),
			'std' => 'unchecked',
		);
		
		$repeater_fields[] = array(
			'id' => 'multiple',
			'title' => __( 'Allow Multiple Selections', 'pojo-forms' ) . ':',
			'type' => Pojo_MetaBox::FIELD_CHECKBOX,
			'std' => false,
		);
		
		$repeater_fields[] = array(
			'id' => 'first_unselectable_item',
			'title' => __( 'Set the first option as unselectable', 'pojo-forms' ) . ':',
			'type' => Pojo_MetaBox::FIELD_CHECKBOX,
			'std' => false,
		);

		$repeater_fields[] = array(
			'id' => 'textarea_rows',
			'title' => __( 'Rows', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_NUMBER,
			'placeholder' => '5',
			'min' => '1',
			'max' => '100',
			'std' => '5',
		);

		$repeater_fields[] = array(
			'id' => 'number_min',
			'title' => __( 'Min', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_NUMBER,
			'placeholder' => '5',
			'min' => '-99999',
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'number_max',
			'title' => __( 'Max', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_NUMBER,
			'placeholder' => '100',
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'file_types',
			'title' => __( 'Allowed file types', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_TEXT,
			'desc' => __( 'Write here the allowed file types, comma seperated: jpg, gif, pdf', 'pojo-forms' ),
			'std' => '',
		);		

		$repeater_fields[] = array(
			'id' => 'file_sizes',
			'title' => __( 'Maximum upload file size', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => $this->_get_upload_file_size_options(),
			'desc' => __( 'The file sizes listed here, are the one allowed by your hosting. for bigger upload size contact them.', 'pojo-forms' ),
			'std' => '1',
		);
		// End custom elements per field type

		$repeater_fields[] = array(
			'id'      => 'advanced',
			'title'   => __( 'Settings', 'pojo-forms' ),
			'type'    => Pojo_MetaBox::FIELD_BUTTON_COLLAPSE,
		);

		$repeater_fields[] = array(
			'id' => 'placeholder',
			'title' => __( 'Placeholder', 'pojo-forms' ),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'required',
			'title' => __( 'Required', 'pojo-forms' ) . ':',
			'type' => Pojo_MetaBox::FIELD_CHECKBOX,
			'std' => false,
		);

		$repeater_fields[] = array(
			'id' => 'width',
			'title' => __( 'Width', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'12' => __( '100%', 'pojo-forms' ),
				'10' => __( '83%', 'pojo-forms' ),
				'9' => __( '75%', 'pojo-forms' ),
				'8' => __( '66%', 'pojo-forms' ),
				'6' => __( '50%', 'pojo-forms' ),
				'4' => __( '33%', 'pojo-forms' ),
				'3' => __( '25%', 'pojo-forms' ),
				'2-5' => __( '20%', 'pojo-forms' ),
				'2' => __( '16%', 'pojo-forms' ),
			),
			'std' => '12',
		);

		$repeater_fields[] = array(
			'id' => 'field_id',
			'title' => __( 'ID', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_HIDDEN,
			'std' => '',
			'readonly' => 'readonly',
		);

		$repeater_fields[] = array(
			'id' => 'shortcode',
			'title' => __( 'Shortcode ID', 'pojo-forms' ),
			'std' => '',
			'readonly' => 'readonly',
		);

		$repeater_fields[] = array(
			'id' => 'class',
			'title' => __( 'CSS Classes', 'pojo-forms' ),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id'      => 'advanced',
			'title'   => __( 'Advanced', 'pojo-forms' ),
			'type'    => Pojo_MetaBox::FIELD_BUTTON_COLLAPSE,
			'mode'    => 'end',
		);

		$fields[] = array(
			'id' => 'fields',
			'type' => Pojo_MetaBox::FIELD_REPEATER,
			'add_row_text' => __( '+ Add Field', 'pojo-forms' ),
			'fields' => $repeater_fields,
		);

		$meta_boxes[] = array(
			'id'         => 'pojo-forms-form',
			'title'      => __( 'Form', 'pojo-forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context'    => 'normal',
			'priority'   => 'core',
			'prefix'     => 'form_',
			'fields'     => $fields,
		);

		return $meta_boxes;
	}

	public function register_form_options_metabox( $meta_boxes = array() ) {
		$fields = array();
		
		$fields[] = array(
			'id' => 'email_to',
			'title' => __( 'Email To', 'pojo-forms' ),
			'std' => get_option( 'admin_email' ),
		);

		$fields[] = array(
			'id' => 'email_subject',
			'title' => __( 'Email Subject', 'pojo-forms' ),
			'std' => sprintf( __( 'New message from "%s"', 'pojo-forms' ), get_bloginfo( 'name' ) ),
		);

		$fields[] = array(
			'id' => 'email_form_name',
			'title' => __( 'From Name', 'pojo-forms' ),
			'std' => mb_substr( get_bloginfo( 'name' ), 0, 30 ),
		);

		$fields[] = array(
			'id' => 'email_form',
			'title' => __( 'From Email', 'pojo-forms' ),
			'std' => 'noreply@' . parse_url( home_url(), PHP_URL_HOST ),
		);
		
		$fields[] = array(
			'id' => 'email_reply_to',
			'title' => __( 'Email Reply-To (Optional)', 'pojo-forms' ),
			'placeholder' => __( 'Insert Shortcode ID', 'pojo-forms' ),
			'std' => '',
		);
		
		$fields[] = array(
			'id' => 'redirect_to',
			'title' => __( 'Redirect To (Optional)', 'pojo-forms' ),
			'placeholder' => __( 'http://pojo.me/thankyou/', 'pojo-forms' ),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'metadata',
			'type' => Pojo_MetaBox::FIELD_CHECKBOX_LIST,
			'title' => __( 'Form data', 'pojo-forms' ),
			'options' => array(
				'date' => __( 'Date', 'pojo-forms' ),
				'time' => __( 'Time', 'pojo-forms' ),
				'page_url' => __( 'Page URL', 'pojo-forms' ),
				'user_agent' => __( 'User Agent', 'pojo-forms' ),
				'remote_ip' => __( 'Remote IP', 'pojo-forms' ),
				'credit' => __( 'Credit', 'pojo-forms' ),
			),
			'std' => array( 'time', 'date', 'page_url', 'user_agent', 'remote_ip', 'credit' ),
		);

		$fields[] = array(
			'id' => 'messages',
			'title' => __( 'Messages', 'pojo-forms' ),
			'classes' => array( 'select-show-or-hide-fields' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'' => __( 'Default', 'pojo-forms' ),
				'custom' => __( 'Custom', 'pojo-forms' ),
			),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'message_' . Pojo_Forms_Messages::SUCCESS,
			'title' => __( 'Success Message', 'pojo-forms' ),
			'show_on' => array( 'form_messages' => 'custom' ),
			'std' => Pojo_Forms_Messages::get_default_message( Pojo_Forms_Messages::SUCCESS ),
		);

		$fields[] = array(
			'id' => 'message_' . Pojo_Forms_Messages::ERROR,
			'title' => __( 'Error Message', 'pojo-forms' ),
			'show_on' => array( 'form_messages' => 'custom' ),
			'std' => Pojo_Forms_Messages::get_default_message( Pojo_Forms_Messages::ERROR ),
		);

		$fields[] = array(
			'id' => 'message_' . Pojo_Forms_Messages::FIELD_REQUIRED,
			'title' => __( 'Required field Message', 'pojo-forms' ),
			'show_on' => array( 'form_messages' => 'custom' ),
			'std' => Pojo_Forms_Messages::get_default_message( Pojo_Forms_Messages::FIELD_REQUIRED ),
		);

		$fields[] = array(
			'id' => 'message_' . Pojo_Forms_Messages::INVALID_FORM,
			'title' => __( 'Invalid Message', 'pojo-forms' ),
			'show_on' => array( 'form_messages' => 'custom' ),
			'std' => Pojo_Forms_Messages::get_default_message( Pojo_Forms_Messages::INVALID_FORM ),
		);
		
		$meta_boxes[] = array(
			'id'         => 'pojo-forms-options',
			'title'      => __( 'Form Options', 'pojo-forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context'   => 'side',
			'prefix'     => 'form_',
			'fields'     => $fields,
		);
		
		return $meta_boxes;
	}

	public function register_form_style_metabox( $meta_boxes = array() ) {
		$fields = array();
		
		$fields[] = array(
			'id'    => 'heading_fields_settings',
			'title' => __( 'Field Settings', 'pojo-forms' ),
			'type'  => Pojo_MetaBox::FIELD_HEADING,
		);

		$fields[] = array(
			'id' => 'align_text',
			'title' => __( 'Label', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'top' => __( 'Top', 'pojo-forms' ),
				'inside' => __( 'Hide Label', 'pojo-forms' ),
				'right' => __( 'Side - Align Right', 'pojo-forms' ),
				'left' => __( 'Side - Align Left', 'pojo-forms' ),
			),
			'std' => 'top',
		);

		$fields[] = array(
			'id' => 'field_size',
			'title' => __( 'Field Size', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'small' => __( 'Small', 'pojo-forms' ),
				'medium' => __( 'Medium', 'pojo-forms' ),
				'large' => __( 'Large', 'pojo-forms' ),
				'xl' => __( 'XL', 'pojo-forms' ),
				'xxl' => __( 'XXL', 'pojo-forms' ),
			),
			'std' => 'medium',
		);

		$fields[] = array(
			'id' => 'fields_style',
			'title' => __( 'Field Style', 'pojo-forms' ),
			'classes' => array( 'select-show-or-hide-fields' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'' => __( 'Default', 'pojo-forms' ),
				'custom' => __( 'Custom Style', 'pojo-forms' ),
			),
			'std' => '',
		);

		// Fields custom style
		$fields[] = array(
			'id' => 'fields_lbl_size',
			'title' => __( 'Label Size', 'pojo-forms' ),
			'placeholder' => __( '13px', 'pojo-forms' ),
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'fields_lbl_color',
			'title' => __( 'Label Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#ffffff',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '#ffffff',
		);
		
		$fields[] = array(
			'id' => 'fields_input_color',
			'title' => __( 'Input Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#ffffff',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '#ffffff',
		);

		$fields[] = array(
			'id' => 'fields_bg_color',
			'title' => __( 'Background Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#ffffff',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '#ffffff',
		);

		$fields[] = array(
			'id' => 'fields_bg_opacity',
			'title' => __( 'Background Opacity', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_NUMBER,
			'placeholder' => '100',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'min' => '0',
			'max' => '100',
			'std' => '100',
		);

		$fields[] = array(
			'id' => 'fields_border_color',
			'title' => __( 'Border Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#cccccc',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '#cccccc',
		);

		$fields[] = array(
			'id' => 'fields_border_width',
			'title' => __( 'Border Width', 'pojo-forms' ),
			'placeholder' => '1px',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '1px',
		);

		$fields[] = array(
			'id' => 'fields_border_radius',
			'title' => __( 'Border Radius', 'pojo-forms' ),
			'placeholder' => '5px',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '5px',
		);
		// End fields custom style
		
		$fields[] = array(
			'id'    => 'heading_button_settings',
			'title' => __( 'Button Settings', 'pojo-forms' ),
			'type'  => Pojo_MetaBox::FIELD_HEADING,
		);

		$fields[] = array(
			'id' => 'button_text',
			'title' => __( 'Button Text', 'pojo-forms' ),
			'placeholder' => __( 'Send', 'pojo-forms' ),
			'std' => __( 'Send', 'pojo-forms' ),
		);

		$fields[] = array(
			'id' => 'button_size',
			'title' => __( 'Button Size', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'small' => __( 'Small', 'pojo-forms' ),
				'medium' => __( 'Medium', 'pojo-forms' ),
				'large' => __( 'Large', 'pojo-forms' ),
				'xl' => __( 'XL', 'pojo-forms' ),
				'xxl' => __( 'XXL', 'pojo-forms' ),
			),
			'std' => 'medium',
		);

		$fields[] = array(
			'id' => 'button_width',
			'title' => __( 'Button Area Width', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'12' => __( '100%', 'pojo-forms' ),
				'10' => __( '83%', 'pojo-forms' ),
				'9' => __( '75%', 'pojo-forms' ),
				'8' => __( '66%', 'pojo-forms' ),
				'6' => __( '50%', 'pojo-forms' ),
				'4' => __( '33%', 'pojo-forms' ),
				'3' => __( '25%', 'pojo-forms' ),
				'2-5' => __( '20%', 'pojo-forms' ),
				'2' => __( '16%', 'pojo-forms' ),
			),
			'std' => '12',
		);

		$fields[] = array(
			'id' => 'button_align',
			'title' => __( 'Button Align', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'none' => _x( 'None', 'button-align', 'pojo-forms' ),
				'center' => _x( 'Center', 'button-align', 'pojo-forms' ),
				'right' => _x( 'Right', 'button-align', 'pojo-forms' ),
				'left' => _x( 'Left', 'button-align', 'pojo-forms' ),
				'block' => _x( 'Block', 'button-align', 'pojo-forms' ),
			),
			'std' => 'none',
		);

		$fields[] = array(
			'id' => 'button_style',
			'title' => __( 'Button Style', 'pojo-forms' ),
			'classes' => array( 'select-show-or-hide-fields' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'' => __( 'Default', 'pojo-forms' ),
				'custom' => __( 'Custom Style', 'pojo-forms' ),
			),
			'std' => '',
		);
		
		// Button custom style
		$fields[] = array(
			'id' => 'button_text_color',
			'title' => __( 'Text Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#ffffff',
			'show_on' => array( 'form_style_button_style' => 'custom' ),
			'std' => '#ffffff',
		);

		$fields[] = array(
			'id' => 'button_bg_color',
			'title' => __( 'Background Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#ffffff',
			'show_on' => array( 'form_style_button_style' => 'custom' ),
			'std' => '#ffffff',
		);
		
		$fields[] = array(
			'id' => 'button_bg_opacity',
			'title' => __( 'Background Opacity', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_NUMBER,
			'placeholder' => '100',
			'show_on' => array( 'form_style_button_style' => 'custom' ),
			'min' => '0',
			'max' => '100',
			'std' => '100',
		);
		
		$fields[] = array(
			'id' => 'button_border_color',
			'title' => __( 'Border Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#cccccc',
			'show_on' => array( 'form_style_button_style' => 'custom' ),
			'std' => '#cccccc',
		);

		$fields[] = array(
			'id' => 'button_border_width',
			'title' => __( 'Border Width', 'pojo-forms' ),
			'placeholder' => '1px',
			'show_on' => array( 'form_style_button_style' => 'custom' ),
			'std' => '1px',
		);

		$fields[] = array(
			'id' => 'button_border_radius',
			'title' => __( 'Border Radius', 'pojo-forms' ),
			'placeholder' => '5px',
			'show_on' => array( 'form_style_button_style' => 'custom' ),
			'std' => '5px',
		);
		// End button custom style
		
		$meta_boxes[] = array(
			'id'         => 'pojo-forms-style',
			'title'      => __( 'Form Style', 'pojo-forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context'    => 'normal',
			'prefix'     => 'form_style_',
			'fields'     => $fields,
		);

		return $meta_boxes;
	}
	
	public function post_row_actions( $actions, $post ) {
		/** @var $post WP_Post */
		if ( 'pojo_forms' === $post->post_type ) {
			// Remove quick edit
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	public function post_submitbox_misc_actions() {
		global $post;

		if ( 'pojo_forms' !== $post->post_type )
			return;
		?>
		<div class="misc-pub-section" id="form-preview-shortcode">
			<input type="text" class="copy-paste-shortcode" value="<?php echo esc_attr( POJO_FORMS()->helpers->get_shortcode_text( $post->ID ) ); ?>" readonly />
			<span><?php _e( 'Copy and paste this shortcode into your Text editor or use with Form Widget.', 'pojo-forms' ); ?></span>
		</div>
		
		<div class="misc-pub-section">
			<?php printf( '<a href="javascript:void(0);" class="btn-admin-preview-shortcode button" data-action="form_preview_shortcode" data-id="%d">%s</a>', $post->ID, __( 'Preview', 'pojo-forms' ) ); ?>
		</div>
	<?php
	}

	private function _get_upload_file_size_options() {
		$max_file_size = wp_max_upload_size() / pow( 1024, 2 ); //MB
		
		$sizes = array();
		for ( $file_size = 1; $file_size <= $max_file_size; $file_size++ ) {
			$sizes[ $file_size ] = $file_size . 'MB';
		}
		return $sizes;
	}

	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );

		add_filter( 'manage_edit-pojo_forms_columns', array( &$this, 'admin_cpt_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'custom_columns' ) );

		add_action( 'dashboard_glance_items', array( &$this, 'dashboard_glance_items' ), 60 );

		add_action('post_submitbox_misc_actions', array(&$this, 'post_submitbox_misc_actions'));

		add_filter( 'pojo_meta_boxes', array( &$this, 'register_form_fields_metabox' ), 30 );
		add_filter( 'pojo_meta_boxes', array( &$this, 'register_form_options_metabox' ), 40 );
		add_filter( 'pojo_meta_boxes', array( &$this, 'register_form_style_metabox' ), 50 );
		add_filter( 'post_row_actions', array( &$this, 'post_row_actions' ), 10, 2 );
		
	}

}