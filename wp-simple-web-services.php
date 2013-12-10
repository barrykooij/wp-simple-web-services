<?php
/*
Plugin Name: WP Simple Web Services
Plugin URI: http://www.barrykooij.com/
Description: Simple WordPress Rest Web Services. Add JSON REST web services to your WordPress website with a few clicks.
Version: 1.1.0
Author: Barry Kooij
Author URI: http://www.barrykooij.com/
License: GPL v3

WP Simple Web Services
Copyright (C) 2013, Barry Kooij - barry@cageworks.nl

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'WPSWS_PLUGIN_DIR' ) ) {
	define( 'WPSWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPSWS_PLUGIN_FILE' ) ) {
	define( 'WPSWS_PLUGIN_FILE', __FILE__ );
}

/*
 * @todo
 * - Make it easy for webservice developers to create custom settings
 */
class WP_Simple_Web_Service {

	const WEBSERVICE_REWRITE = 'webservice/([a-zA-Z0-9_-]+)$';
	const OPTION_KEY         = 'wpw_options';

	private static $instance = null;

	/**
	 * Get singleton instance of class
	 *
	 * @return null|WP_Simple_Web_Service
	 */
	public static function get() {

		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Function that runs on install
	 */
	public static function install() {

		// Clear the permalinks
		flush_rewrite_rules();

	}

	/**
	 * Constructor
	 */
	private function __construct() {

		// Load files
		$this->includes();

		// Init
		$this->init();

	}

	/**
	 * Load required files
	 */
	private function includes() {

		require_once( WPSWS_PLUGIN_DIR . 'classes/class-wpsws_rewrite_rules.php' );
		require_once( WPSWS_PLUGIN_DIR . 'classes/class-wpsws-webservice-get-posts.php' );

		if ( is_admin() ) {
			// Backend

			require_once( WPSWS_PLUGIN_DIR . 'classes/class-wpsws-settings.php' );

		}
		else {
			// Frondend

			require_once( WPSWS_PLUGIN_DIR . 'classes/class-wpsws-catch-request.php' );
			require_once( WPSWS_PLUGIN_DIR . 'classes/class-wpsws-output.php' );
		}

	}

	/**
	 * Initialize class
	 */
	private function init() {

		// Setup Rewrite Rules
		WPSWS_Rewrite_Rules::get();

		// Default webservice
		WPSWS_Webservice_get_posts::get();

		if ( is_admin() ) {
			// Backend

			// Setup settings
			WPSWS_Settings::get();

		}
		else {
			// Frondend

			// Catch request
			WPSWS_Catch_Request::get();
		}

	}

	/**
	 * The correct way to throw an error in a webservice
	 *
	 * @param $error_string
	 */
	public function throw_error( $error_string ) {
		wp_die( '<b>Webservice error:</b> ' . $error_string );
	}

	/**
	 * Function to get the plugin options
	 *
	 * @return array
	 */
	public function get_options() {
		return get_option( self::OPTION_KEY, array() );
	}

	/**
	 * Function to save the plugin options
	 *
	 * @param $options
	 */
	public function save_options( $options ) {
		update_option( self::OPTION_KEY, $options );
	}

}

/**
 * Function that returns singleton instance of WP_Simple_Web_Service class
 *
 * @return null|WP_Simple_Web_Service
 */
function WP_Simple_Web_Service() {
	return WP_Simple_Web_Service::get();
}

// Load plugin
add_action( 'plugins_loaded', create_function( '', 'WP_Simple_Web_Service::get();' ) );

// Install hook
register_activation_hook( WPSWS_PLUGIN_FILE, array( 'WP_Simple_Web_Service', 'install' ) );