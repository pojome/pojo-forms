<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_CPT {

	public function init() {
		// CPT: pojo_forms.
		$labels = array(
			'name'               => __( 'Forms', 'forms' ),
			'singular_name'      => __( 'Form', 'forms' ),
			'add_new'            => __( 'Add New', 'forms' ),
			'add_new_item'       => __( 'Add New Form', 'forms' ),
			'edit_item'          => __( 'Edit Form', 'forms' ),
			'new_item'           => __( 'New Form', 'forms' ),
			'all_items'          => __( 'All Forms', 'forms' ),
			'view_item'          => __( 'View Form', 'forms' ),
			'search_items'       => __( 'Search Form', 'forms' ),
			'not_found'          => __( 'No forms found', 'forms' ),
			'not_found_in_trash' => __( 'No forms found in Trash', 'forms' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Forms', 'forms' ),
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
			1  => __( 'Form updated.', 'forms' ),
			2  => __( 'Custom field updated.', 'forms' ),
			3  => __( 'Custom field deleted.', 'forms' ),
			4  => __( 'Form updated.', 'forms' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Form restored to revision from %s', 'forms' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Form published.', 'forms' ),
			7  => __( 'Form saved.', 'forms' ),
			8  => __( 'Form submitted.', 'forms' ),
			9  => sprintf( __( 'Post scheduled for: <strong>%1$s</strong>.', 'forms' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'forms' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Form draft updated.', 'forms' ),
		);

		return $messages;
	}

	public function admin_cpt_columns( $columns ) {
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title Form', 'forms' ),
			'form_preview' => __( 'Preview Form', 'forms' ),
			'form_count' => __( 'Fields', 'forms' ),
			'form_shortcode' => __( 'Shortcode', 'forms' ),
			'date' => __( 'Date', 'forms' ),
		);
	}

	public function custom_columns( $column ) {
		global $post, $pojo_forms;

		switch ( $column ) {
			case 'form_preview' :
				printf( '<a href="javascript:void(0);" class="btn-admin-preview-shortcode" data-action="form_preview_shortcode" data-id="%d">%s</a>', $post->ID, __( 'Preview', 'forms' ) );
				break;

			case 'form_count' :
				echo sizeof( atmb_get_field_without_type( 'fields', 'form_',  $post->ID ) );
				break;

			case 'form_shortcode' :
				echo $pojo_forms->helpers->get_shortcode_text( $post->ID );
				break;
		}
	}
	
	public function dashboard_glance_items( $elements ) {
		$post_type = 'pojo_forms';
		$num_posts = wp_count_posts( $post_type );
		if ( $num_posts && $num_posts->publish ) {
			$text = _n( '%s Form', '%s Forms', $num_posts->publish, 'forms' );
			$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );
			printf( '<li class="%1$s-count"><a href="edit.php?post_type=%1$s">%2$s</a></li>', $post_type, $text );
		}
	}

	public function register_settings_metabox( $meta_boxes = array() ) {
		$fields = array();
		$base_radio_image_url = get_template_directory_uri() . '/core/admin-ui/images/buttons_colors';
		
		$fields[] = array(
			'id' => 'email_to',
			'title' => __( 'Email to', 'forms' ),
			'std' => get_option( 'admin_email' ),
		);
		
		$fields[] = array(
			'id' => 'frm_align_text',
			'title' => __( 'Align Text', 'forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'top' => __( 'Top', 'forms' ),
				'inside' => __( 'Inside', 'forms' ),
				'right' => __( 'Side - Alignright', 'forms' ),
				'left' => __( 'Side - Alignleft‬', 'forms' ),
			),
			'std' => 'top',
		);
		
		$fields[] = array(
			'id' => 'send_button_text',
			'title' => __( 'Send Button Text', 'forms' ),
			'std' => __( 'Send', 'forms' ),
		);
		
		$colors_list = array(
			'ffffff' => '#ffffff',
			'000000' => '#000000',
			'16a085' => '#16a085',
			'2778ae' => '#2778ae',
			'27ae60' => '#27ae60',
			'34495e' => '#34495e',
			'35e763' => '#35e763',
			'37a0e7' => '#37a0e7',
			'454545' => '#454545',
			'859595' => '#859595',
			'8e43ac' => '#8e43ac',
			'bdc3c7' => '#bdc3c7',
			'c0392b' => '#c0392b',
			'd35400' => '#d35400',
			'e67e22' => '#e67e22',
			'e74c3b' => '#e74c3b',
			'ecf0f0' => '#ecf0f0',
			'f1c40e' => '#f1c40e',
		);
		$send_button_colors_radios = array();
		foreach ( $colors_list as $key => $value ) {
			$send_button_colors_radios[] = array(
				'id' => $key,
				'title' => '',
				'image' => sprintf( '%s/%s.png', $base_radio_image_url, $key ),
			);
		}
		
		$fields[] = array(
			'id' => 'send_button_color',
			'title' => __( 'Send Button Color', 'forms' ),
			'type' => Pojo_MetaBox::FIELD_RADIO_IMAGE,
			'options' => $send_button_colors_radios,
			'std' => 'ffffff',
		);
		
		$fields[] = array(
			'id' => 'email_subject',
			'title' => __( 'Email Subject', 'forms' ),
			'std' => sprintf( __( 'New massage from "%s"', 'forms' ), get_bloginfo( 'name' ) ),
		);
		
		$fields[] = array(
			'id' => 'redirect_to',
			'title' => __( 'After submitting redirect to', 'forms' ),
			'placeholder' => __( '(Optional) http://www.pojo.me/', 'forms' ),
			'std' => '',
		);
		
		$meta_boxes[] = array(
			'id'         => 'pojo-forms-settings',
			'title'      => __( 'Form Options', 'forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context'   => 'side',
			'prefix'     => 'form_',
			'fields'     => $fields,
		);
		
		return $meta_boxes;
	}

	public function register_forms_metabox( $meta_boxes = array() ) {
		$repeater_fields = $fields = array();
		
		$repeater_fields[] = array(
			'id' => 'name',
			'title' => __( 'Name', 'forms' ),
			'std' => '',
		);
		
		$repeater_fields[] = array(
			'id' => 'type',
			'title' => __( 'Field Type', 'forms' ),
			'type' => Pojo_MetaBox::FIELD_SELECT,
			'options' => array(
				'' => __( 'Text', 'forms' ),
				'textarea' => __( 'Textarea', 'forms' ),
				'email' => __( 'Email', 'forms' ),
				'tel' => __( 'Telephone Number', 'forms' ),
				'url' => __( 'Url', 'forms' ),
			),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'placeholder',
			'title' => __( 'Placeholder', 'forms' ),
			'std' => '',
		);

		$repeater_fields[] = array(
			'id' => 'required',
			'title' => __( 'Required', 'forms' ),
			'type' => Pojo_MetaBox::FIELD_CHECKBOX,
			'std' => true,
		);

		$fields[] = array(
			'id' => 'fields',
			'type' => Pojo_MetaBox::FIELD_REPEATER,
			'add_row_text' => __( '+ Add Field', 'forms' ),
			'fields' => $repeater_fields,
		);
		
		$meta_boxes[] = array(
			'id'         => 'pojo-forms-form',
			'title'      => __( 'Form', 'forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context'    => 'normal',
			'priority'   => 'core',
			'prefix'     => 'form_',
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
		global $post, $pojo_forms;

		if ( 'pojo_forms' !== $post->post_type )
			return;
		?>
		<div class="misc-pub-section" id="form-preview-shortcode">
			<input type="text" class="copy-paste-shortcode" value="<?php echo esc_attr( $pojo_forms->helpers->get_shortcode_text( $post->ID ) ); ?>" readonly />
			<span><?php _e( 'Copy and paste this shortcode into your Text editor or use with Form Widget.', 'forms' ); ?></span>
		</div>
		
		<div class="misc-pub-section">
			<?php printf( '<a href="javascript:void(0);" class="btn-admin-preview-shortcode button" data-action="form_preview_shortcode" data-id="%d">%s</a>', $post->ID, __( 'Preview', 'forms' ) ); ?>
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
		
		add_filter( 'pojo_meta_boxes', array( &$this, 'register_settings_metabox' ) );
		add_filter( 'pojo_meta_boxes', array( &$this, 'register_forms_metabox' ) );
		add_filter( 'post_row_actions', array( &$this, 'post_row_actions' ), 10, 2 );
		
	}

}