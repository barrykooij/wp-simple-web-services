<?php

class WPSWS_Settings {

	private static $instance = null;

	/**
	 * Get singleton instance of class
	 *
	 * @return null|WPSWS_Settings
	 */
	public static function get() {

		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->hooks();
	}

	/**
	 * Hooks
	 */
	private function hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wpw_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Add menu pages
	 */
	public function add_menu_pages() {
		add_menu_page( 'Overview', 'Web Services', 'manage_options', 'wpw', array( $this, 'screen_main' ) );
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		if ( ! SCRIPT_DEBUG ) {
			wp_enqueue_script( 'wpw-admin', plugin_dir_url( WPSWS_PLUGIN_FILE ) . '/assets/js/wpw-admin.js', array( 'jquery' ), '1.0.0' );
		}
		else {
			wp_enqueue_script( 'wpw-admin', plugin_dir_url( WPSWS_PLUGIN_FILE ) . '/assets/js/wpw-admin.orig.js', array( 'jquery' ), '1.0.0' );
		}
	}

	/**
	 * Save settings via AJAX
	 *
	 */
	public function save_settings() {

		// Security check
		check_ajax_referer( 'wpw-ajax-security', 'ajax_nonce' );

		// Permission check
		if ( ! current_user_can( 'manage_options' ) ) {
			echo '0';
			exit;
		}

		// Get options
		$options = WP_Simple_Web_Service::get()->get_options();

		// Setup variables
		$fields = explode( ',', $_POST['fields'] );
		$custom = explode( ',', $_POST['custom'] );

		// Update options
		$options['get_posts'][$_POST['post_type']] = array(
			'enabled' => $_POST['enabled'],
			'fields'  => $fields,
			'custom'  => $custom
		);

		// Save webservice
		WP_Simple_Web_Service::get()->save_options( $options );

		exit;
	}

	/**
	 * The main screen
	 */
	public function screen_main() {
		?>
		<div class="wrap" id="wpw-wrap">
			<h2>WordPress Simple Web Services</h2>

			<div>
				<p><?php _e( 'Welcome to WordPress Simple Web Services! You can enable post types by clicking the "Enable post type" checkbox below. If you encounter problems or find any bugs please report hem on <a href="https://github.com/barrykooij/wp-simple-web-services/issues" target="_blank">GitHub</a>.', 'wpsws' ); ?></p>
				<?php do_action( 'wpsws_general_settings' ); ?>
			</div>
		</div>
	<?php
	}

}