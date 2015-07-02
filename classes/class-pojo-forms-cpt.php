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
				'' => __( 'Text', 'pojo-forms' ),
				'textarea' => __( 'Textarea', 'pojo-forms' ),
				'email' => __( 'Email', 'pojo-forms' ),
				'tel' => __( 'Telephone Number', 'pojo-forms' ),
				'url' => __( 'Url', 'pojo-forms' ),
			),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'name',
			'title' => __( 'Name', 'pojo-forms' ),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'placeholder',
			'title' => __( 'Placeholder', 'pojo-forms' ),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'required',
			'title' => __( 'Required', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_CHECKBOX,
			'std' => true,
		);

		$repeater_fields[] = array(
			'id'      => 'advanced',
			'title'   => __( 'Advanced', 'pojo-forms' ),
			'type'    => Pojo_MetaBox::FIELD_BUTTON_COLLAPSE,
		);

		$repeater_fields[] = array(
			'id' => 'size',
			'title' => __( 'Width', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'' => __( 'One Column', 'pojo-forms' ),
				'2' => __( '2 Columns', 'pojo-forms' ),
				'3' => __( '3 Columns', 'pojo-forms' ),
				'4' => __( '4 Columns', 'pojo-forms' ),
				'5' => __( '5 Columns', 'pojo-forms' ),
				'6' => __( '6 Columns', 'pojo-forms' ),
			),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'field_id',
			'title' => __( 'ID', 'pojo-forms' ),
			'std' => 'TEMP-ID',
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
			'title' => __( 'Email to', 'pojo-forms' ),
			'std' => get_option( 'admin_email' ),
		);

		$fields[] = array(
			'id' => 'email_subject',
			'title' => __( 'Email Subject', 'pojo-forms' ),
			'std' => sprintf( __( 'New massage from "%s"', 'pojo-forms' ), get_bloginfo( 'name' ) ),
		);

		$fields[] = array(
			'id' => 'email_form_name',
			'title' => __( 'Email form name', 'pojo-forms' ),
			'std' => get_bloginfo( 'name' ),
		);

		$fields[] = array(
			'id' => 'email_form',
			'title' => __( 'Email form', 'pojo-forms' ),
			'std' => get_option( 'admin_email' ),
		);

		$fields[] = array(
			'id' => 'email_reply_to',
			'title' => __( 'Email Reply-To', 'pojo-forms' ),
			'placeholder' => __( 'Optional', 'pojo-forms' ),
			'std' => '',
		);
		
		$fields[] = array(
			'id' => 'redirect_to',
			'title' => __( 'After submitting redirect to', 'pojo-forms' ),
			'placeholder' => __( '(Optional) http://pojo.me/', 'pojo-forms' ),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'metadata',
			'type' => Pojo_MetaBox::FIELD_CHECKBOX_LIST,
			'title' => __( 'Metadata', 'pojo-forms' ),
			'options' => array(
				'time' => __( 'Time', 'pojo-forms' ),
				'date' => __( 'Date', 'pojo-forms' ),
				'page_title' => __( 'Page Title', 'pojo-forms' ),
				'page_url' => __( 'Page URL', 'pojo-forms' ),
				'user_agent' => __( 'User Agent', 'pojo-forms' ),
				'remote_ip' => __( 'Remote IP', 'pojo-forms' ),
				'credit' => __( 'Credit', 'pojo-forms' ),
			),
			'std' => array( 'time', 'date', 'page_title', 'page_url', 'user_agent', 'remote_ip', 'credit' ),
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
			'title' => __( 'Align Text', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'top' => __( 'Top', 'pojo-forms' ),
				'inside' => __( 'Inside', 'pojo-forms' ),
				'right' => __( 'Side - Alignright', 'pojo-forms' ),
				'left' => __( 'Side - Alignleft', 'pojo-forms' ),
			),
			'std' => 'top',
		);

		$fields[] = array(
			'id' => 'fields_style',
			'title' => __( 'fields Style', 'pojo-forms' ),
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
			'id' => 'fields_text_size',
			'title' => __( 'Text Size', 'pojo-forms' ),
			'placeholder' => __( '13px', 'pojo-forms' ),
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '',
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
			'id' => 'fields_text_color',
			'title' => __( 'Text Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#ffffff',
			'show_on' => array( 'form_style_fields_style' => 'custom' ),
			'std' => '#ffffff',
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
			'id' => 'button_text_color',
			'title' => __( 'Text Color', 'pojo-forms' ),
			'type' => Pojo_MetaBox::FIELD_COLOR,
			'placeholder' => '#ffffff',
			'show_on' => array( 'form_style_button_style' => 'custom' ),
			'std' => '#ffffff',
		);
		// End button custom style
		
		$meta_boxes[] = array(
			'id'         => 'pojo-forms-style',
			'title'      => __( 'Form Style', 'pojo-forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context'   => 'side',
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