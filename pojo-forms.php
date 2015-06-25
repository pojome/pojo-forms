<?php
/*
Plugin Name: Pojo Forms
Description: ...
Plugin URI: http://pojo.me/
Author: Pojo Team
Version: 1.0.0
Author URI: http://pojo.me/
Text Domain: pojo-forms
Domain Path: /languages/
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'POJO_FORMS__FILE__', __FILE__ );
define( 'POJO_FORMS_PLUGIN_BASE', plugin_basename( POJO_FORMS__FILE__ ) );

final class Pojo_Forms {

	private static $_instance = null;

	/**
	 * @var Pojo_Forms_CPT
	 */
	public $cpt;

	/**
	 * @var Pojo_Forms_Shortcode
	 */
	public $shortcode;

	/**
	 * @var Pojo_Forms_Helpers
	 */
	public $helpers;

	/**
	 * @var Pojo_Forms_Ajax
	 */
	public $ajax;

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'pojo-forms' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'pojo-forms' ), '1.0.0' );
	}

	/**
	 * @return Pojo_Forms
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new Pojo_Forms();
		return self::$_instance;
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'pojo-forms', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}
	
	public function widgets_init() {
		if ( ! class_exists( 'Pojo_Widget_Base' ) )
			return;

		include( 'classes/class-pojo-forms-widget.php' );
		register_widget( 'Pojo_Forms_Widget' );
	}
	
	public function bootstrap() {
		// This plugin for Pojo Themes..
		if ( ! class_exists( 'Pojo_Maintenance' ) ) {
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
			return;
		}

		include( 'classes/class-pojo-forms-helpers.php' );
		include( 'classes/class-pojo-forms-cpt.php' );
		include( 'classes/class-pojo-forms-shortcode.php' );
		include( 'classes/class-pojo-forms-ajax.php' );

		$this->cpt       = new Pojo_Forms_CPT();
		$this->shortcode = new Pojo_Forms_Shortcode();
		$this->helpers   = new Pojo_Forms_Helpers();
		$this->ajax      = new Pojo_Forms_Ajax();
		
		add_action( 'widgets_init', array( &$this, 'widgets_init' ), 100 );
	}

	public function admin_notices() {
		echo '<div class="error"><p>' . __( 'You must install and activate Pojo theme.', 'pojo-forms' ) . '</p></div>';
	}

	protected function __construct() {
		add_action( 'after_setup_theme', array( &$this, 'bootstrap' ) );
		add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ) );
	}
	
}
global $pojo_forms;
$pojo_forms = Pojo_Forms::instance();

//Pojo_Forms::instance();

// EOF