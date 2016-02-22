<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Maintenance {

	/**
	 * Creates blank index.php and .htaccess files
	 *
	 * This function runs approximately once per month in order to ensure all folders
	 * have their necessary protection files
	 *
	 * @since 1.2.3
	 *
	 * @param bool $force
	 * 
	 * @return void
	 */
	public function _create_protection_files( $force = false ) {
		if ( false === get_transient( 'pojo_forms_check_protection_files' ) || $force ) {
			$upload_path = POJO_FORMS()->helpers->get_upload_dir();

			$files = array(
				array(
					//'base' => $upload_dir['basedir'] . '/pojo_forms_uploads',
					'file' => 'index.php',
					'content' => '<?php' . PHP_EOL . '// Silence is golden.',
				),
				array(
					//'base' => $upload_dir['basedir'] . '/pojo_forms_uploads',
					'file' => '.htaccess',
					'content' => 'Options -Indexes' . PHP_EOL,
				),
			);

			foreach ( $files as $file ) {
				if ( ! file_exists( trailingslashit( $upload_path ) . $file['file'] ) ) {
					@ file_put_contents( trailingslashit( $upload_path ) . $file['file'], $file['content'] );
				}
			}
			// Check for the files once per day
			set_transient( 'pojo_forms_check_protection_files', true, DAY_IN_SECONDS );
		}
	}

	public function activate() {
		// Create wp-content/uploads/pojo_forms/ folder and the .htaccess file
		$this->_create_protection_files( true );
	}

	public function __construct() {
		register_activation_hook( POJO_FORMS__FILE__, array( &$this, 'activate' ) );

		add_action( 'admin_init', array( &$this, '_create_protection_files' ) );
	}
}