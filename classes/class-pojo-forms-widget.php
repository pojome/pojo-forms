<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Widget extends Pojo_Widget_Base {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		$this->_form_fields = array();

		$this->_form_fields[] = array(
			'id' => 'title',
			'title' => __( 'Title:', 'pojo-forms' ),
			'std' => '',
			'filter' => 'sanitize_text_field',
		);
		
		$all_forms = POJO_FORMS()->helpers->get_all_forms();
		if ( ! empty( $all_forms ) ) {
			$options = array(
				'' => __( '- Select Form -', 'pojo-forms' ),
			);
			$options += $all_forms;
			
			$this->_form_fields[] = array(
				'id' => 'form',
				'title' => __( 'Choose Form:', 'pojo-forms' ),
				'type' => 'select',
				'std' => '',
				'options' => $options,
				'filter' => array( &$this, '_valid_by_options' ),
			);
		} else {
			$this->_form_fields[] = array(
				'id' => 'lbl_no_found',
				'title' => sprintf( '<a href="%s">%s</a>', admin_url( 'post-new.php?post_type=pojo_forms' ), __( 'Create a Form', 'pojo-forms' ) ),
				'type' => 'label',
			);
		}

		$this->_form_fields[] = array(
			'id' => 'lbl_link_all_forms',
			'title' => sprintf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=pojo_forms' ), __( 'All Forms', 'pojo-forms' ) ),
			'type' => 'label',
		);
		
		parent::__construct(
			'pojo_form_widget',
			__( 'Forms', 'pojo-forms' ),
			array( 'description' => __( 'Forms', 'pojo-forms' ), )
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
		
		echo do_shortcode( POJO_FORMS()->helpers->get_shortcode_text( $instance['form'] ) );

		echo $args['after_widget'];
	}

}