<?php
/*
Plugin Name: Pojo Forms
Description: Pojo Forms allows you to create any form you want with a simple drag and drop interface.
Plugin URI: http://pojo.me/
Author: Pojo Team
Version: 1.4.7
Author URI: http://pojo.me/
Text Domain: pojo-forms
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'POJO_FORMS__FILE__', __FILE__ );
define( 'POJO_FORMS_PLUGIN_BASE', plugin_basename( POJO_FORMS__FILE__ ) );
define( 'POJO_FORMS_URL', plugins_url( '/', POJO_FORMS__FILE__ ) );
define( 'POJO_FORMS_ASSETS_URL', POJO_FORMS_URL . 'assets/' );

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
	 * @var Pojo_Forms_Maintenance
	 */
	public $maintenance;

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
		load_plugin_textdomain( 'pojo-forms' );
	}
	
	public function register_widget() {
		if ( ! class_exists( 'Pojo_Widget_Base' ) )
			return;

		include( 'classes/class-pojo-forms-widget.php' );
		register_widget( 'Pojo_Forms_Widget' );
	}

	public function register_widget_builder( $widgets ) {
		$widgets[] = 'Pojo_Forms_Widget';
		return $widgets;
	}

	public function enqueue_scripts() {
		wp_register_script( 'pojo-forms', POJO_FORMS_ASSETS_URL . 'js/app.min.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'pojo-forms' );

		do_action('pojo_forms_load_front_assets');
	}

	public function admin_enqueue_scripts() {
		if ( 'pojo_forms' !== get_current_screen()->post_type ) 
			return;
		
		wp_register_script( 'pojo-admin-forms', POJO_FORMS_ASSETS_URL . 'js/admin.min.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'pojo-admin-forms' );

	}
	
	public function bootstrap() {
		// This plugin for Pojo Themes..
		if ( ! class_exists( 'Pojo_Maintenance' ) ) {
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
			return;
		}

		if ( version_compare( '1.5.1', Pojo_Core::instance()->get_version(), '>' ) ) {
			add_action( 'admin_notices', array( &$this, 'print_update_error' ) );
			return;
		}
		
		include( 'classes/class-pojo-forms-messages.php' );
		include( 'classes/class-pojo-forms-cpt.php' );
		include( 'classes/class-pojo-forms-shortcode.php' );
		include( 'classes/class-pojo-forms-ajax.php' );
		include( 'classes/class-pojo-forms-recaptcha.php' );
		
		$this->cpt = new Pojo_Forms_CPT();
		$this->shortcode = new Pojo_Forms_Shortcode();
		$this->ajax = new Pojo_Forms_Ajax();
		
		$recaptcha = new Pojo_Forms_ReCAPTCHA();

		add_action( 'pojo_widgets_registered', array( &$this, 'register_widget' ) );
		add_action( 'pojo_builder_widgets', array( &$this, 'register_widget_builder' ) );

		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
		
		// Integrations
		if ( is_callable( array( 'Akismet', 'get_api_key' ) ) ) {
			include( 'classes/class-pojo-forms-akismet.php' );
			$akismet = new Pojo_Forms_Akismet();
		}
		
		if ( class_exists( 'AAL_Main' ) ) {
			include( 'classes/class-pojo-forms-aal.php' );
			$aal = new Pojo_Forms_Aal();
		}
		
		if ( function_exists( 'CF7DBPlugin_i18n_init' ) ) {
			include( 'classes/class-pojo-forms-cf7db.php' );
			$cf7db = new Pojo_Forms_CF7DB();
		}
	}

	public function admin_notices() {
		echo '<div class="error"><p>' . sprintf( __( '<a href="%s" target="_blank">Pojo Theme</a> is not active. Please activate any theme by Pojo.me before you are using "Pojo Forms" plugin.', 'pojo-forms' ), 'http://pojo.me/' ) . '</p></div>';
	}

	public function print_update_error() {
		echo '<div class="error"><p>' . sprintf( __( 'The Pojo Forms is not supported by this version of %s. Please <a href="%s">upgrade the theme to its latest version</a>.', 'pojo-forms' ), Pojo_Core::instance()->licenses->updater->theme_name, admin_url( 'update-core.php' ) ) . '</p></div>';
	}

	protected function __construct() {
		include( 'classes/class-pojo-forms-helpers.php' );
		include( 'classes/class-pojo-forms-maintenance.php' );

		$this->helpers = new Pojo_Forms_Helpers();
		$this->maintenance = new Pojo_Forms_Maintenance();
		
		add_action( 'after_setup_theme', array( &$this, 'bootstrap' ), 100 );
		add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ) );
	}
}

/**
 * @return Pojo_Forms
 */
function POJO_FORMS() {
	return Pojo_Forms::instance();
}

POJO_FORMS();
