<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Widget extends Pojo_Widget_Base {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		global $pojo_forms;
		
		$this->_form_fields = array();

		$this->_form_fields[] = array(
			'id' => 'title',
			'title' => __( 'Title:', 'forms' ),
			'std' => '',
			'filter' => 'sanitize_text_field',
		);
		
		$options = $pojo_forms->helpers->get_all_forms();
		if ( ! empty( $options ) ) {
			$std = array_keys( $options );
			$std = $std[0];
			$this->_form_fields[] = array(
				'id' => 'form',
				'title' => __( 'Choose Form:', 'forms' ),
				'type' => 'select',
				'std' => $std,
				'options' => $options,
				'filter' => array( &$this, '_valid_by_options' ),
			);
		} else {
			$this->_form_fields[] = array(
				'id' => 'lbl_no_found',
				'title' => sprintf( '<a href="%s">%s</a>', admin_url( 'post-new.php?post_type=pojo_forms' ), __( 'Create a Form', 'forms' ) ),
				'type' => 'label',
			);
		}

		$this->_form_fields[] = array(
			'id' => 'lbl_no_found',
			'title' => sprintf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=pojo_forms' ), __( 'All Forms', 'forms' ) ),
			'type' => 'label',
		);
		
		parent::__construct(
			'pojo_form_widget',
			__( 'Forms', 'forms' ),
			array( 'description' => __( 'Forms', 'forms' ), )
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$instance['title'] = apply_filters( 'widget_title', $instance['title'] );
		
		if ( empty( $instance['form'] ) )
			return;
		
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) )
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		
		echo do_shortcode( sprintf( '[form id="%d"]', $instance['form'] ) );

		echo $args['after_widget'];
	}

}

// Register this widget in Page Builder
function pojo_forms_page_builder_register_widget( $widgets ) {
	$widgets[] = 'Pojo_Forms_Widget';
	return $widgets;
}
add_action( 'pb_page_builder_widgets', 'pojo_forms_page_builder_register_widget' );