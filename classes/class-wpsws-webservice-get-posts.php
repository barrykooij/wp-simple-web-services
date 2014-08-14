<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPSWS_Webservice_get_posts {

	private static $instance = null;

	/**
	 * Get singleton instance of class
	 *
	 * @return null|WPSWS_Webservice_get_posts
	 */
	public static function get() {

		if ( null === self::$instance ) {
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
	 * Setup hooks
	 */
	private function hooks() {
		add_action( 'wpsws_webservice_get_posts', array( $this, 'get_posts' ) );
		add_action( 'wpsws_register_settings', array( $this, 'register_settings' ), 2 );
	}

	/**
	 * Register all get_posts settings
	 *
	 * @param WPSWS_Settings $settings
	 *
	 */
	public function register_settings( $settings ) {
		$settings->add_tab( 'get_posts', __( 'Post Types', 'wpw' ), __( 'You can enable post types by clicking the "Enable post type" checkbox below.', 'wpw' ) );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		$nice_urls = false;
		$permalink_structure = get_option('permalink_structure');
		if( '/%postname%/' === $permalink_structure ) {
			$nice_urls = true;
		}

		if ( count( $post_types ) > 0 ) {

			foreach ( $post_types as $key => $post_type ) {
				$settings->add_section( $key, $post_type->labels->name, 'get_posts' );

				$settings->add_setting( 'enabled', 'Enable post type', 'checkbox', 'get_posts', $key );


				if( $nice_urls ) {
					$webservice_url = get_site_url() . '/webservice/get_posts/?post_type=' . $key;
				} else {
					$webservice_url = get_site_url() . '?webservice=1&service=get_posts&post_type=' . $key;
				}

				$settings->add_setting( 'webservice_url', 'Webservice URL', 'text', 'get_posts', $key, '', array( 'disabled' => true, 'value' => $webservice_url ) );


				// Default post type fields
				$post_type_supports = array(
					'ID'            => 'ID',
					'post_date'     => __( 'Post Date', 'wpw' ),
					'post_status'   => __( 'Post Status', 'wpw' ),
					'post_modified' => __( 'Post Modified', 'wpw' ),
					'post_parent'   => __( 'Post Parent', 'wpw' ),
					'menu_order'    => __( 'Menu Order', 'wpw' ),
					'post_type'     => __( 'Post Type', 'wpw' )
				);

				// Default post type fields that might be supported
				$post_type_maybe_supports = array(
					'title'     => array( array( 'key' => 'post_title', 'value' => __( 'Post Title', 'wpw' ) ) ),
					'editor'    => array( array( 'key' => 'post_content', 'value' => __( 'Post Content', 'wpw' ) ) ),
					'author'    => array( array( 'key' => 'post_author', 'value' => __( 'Post Author', 'wpw' ) ) ),
					'thumbnail' => array( array( 'key' => 'thumbnail', 'value' => __( 'Thumbnail', 'wpw' ) ) ),
					'excerpt'   => array( array( 'key' => 'post_excerpt', 'value' => __( 'Post Excerpt', 'wpw' ) ) ),
					'comments'  => array( array( 'key' => 'comment_status', 'value' => __( 'Comment Status', 'wpw' ) ), array( 'key' => 'comment_count', 'value' => __( 'Comment Count', 'wpw' ) ) ),
				);

				// Check if the current post type supports the optional fields
				foreach ( $post_type_maybe_supports as $supports_key => $post_type_maybe_support_fields ) {
					if ( post_type_supports( $key, $supports_key ) ) {
						foreach ( $post_type_maybe_support_fields as $post_type_maybe_support_field ) {
							$post_type_supports[$post_type_maybe_support_field['key']] = $post_type_maybe_support_field['value'];
						}
					}
				}
				$settings->add_setting( 'fields', 'Enable Fields', 'checkbox_array', 'get_posts', $key, '', array( 'fields' => $post_type_supports ) );


				$custom_fields = array();
				global $wpdb;
				$query = "SELECT DISTINCT({$wpdb->postmeta}.meta_key)
        						FROM {$wpdb->posts}
        						INNER JOIN {$wpdb->postmeta}
        						ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
        						WHERE {$wpdb->posts}.post_type = '%s'
        						AND {$wpdb->postmeta}.meta_key != ''
        						ORDER BY {$wpdb->postmeta}.meta_key ASC";
				$db_meta_keys = $wpdb->get_col($wpdb->prepare($query, $key));
				if ( is_array( $db_meta_keys ) && count( $db_meta_keys ) > 0 ) {
					foreach ( $db_meta_keys as $custom_field ) {
						if ( substr( $custom_field, 0, 1 ) != '_' ) {
							$custom_fields[$custom_field] = $custom_field;
						}
					}
				}
				$settings->add_setting( 'custom', 'Custom Fields', 'checkbox_array', 'get_posts', $key, '', array( 'fields' => $custom_fields ) );

			}

		}
	}

	/**
	 * Function to get the default settings
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array( 'enabled' => 'false', 'fields' => array(), 'custom' => array() );
	}

	/**
	 * This is the default included 'get_posts' webservice
	 * This webservice will fetch all posts of set post type
	 *
	 * @todo
	 * - All sorts of security checks
	 * - Allow custom query variables in webservice (e.g. custom sorting, posts_per_page, etc.)
	 */
	public function get_posts() {

		// Check if post type is set
		if ( ! isset( $_GET['post_type'] ) ) {
			WP_Simple_Web_Service::get()->throw_error( 'No post type set.' );
		}

		// Set post type
		$post_type = esc_sql( $_GET['post_type'] );

		// Global options
		$options = WP_Simple_Web_Service::get()->get_options();

		// Get 'get_posts' options
		$gp_options = array();
		if ( isset( $options['get_posts'] ) ) {
			$gp_options = $options['get_posts'];
		}

		// Fix scenario where there are no settings for given post type
		if ( ! isset( $gp_options[$post_type] ) ) {
			$gp_options[$post_type] = array();
		}

		// Setup options
		$pt_options = wp_parse_args( $gp_options[$post_type], $this->get_default_settings() );

		// Check if post type is enabled
		if ( 'false' == $pt_options['enabled'] ) {
			WP_Simple_Web_Service::get()->throw_error( 'Post Type not supported.' );
		}

		// Setup default query vars
		$default_query_arguments = array(
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'title',
		);

		// Get query vars
		$query_vars = array();
		if ( isset( $_GET['qv'] ) ) {
			$query_vars = $_GET['qv'];
		}

		// Merge query vars
		$query_vars = wp_parse_args( $query_vars, $default_query_arguments );

		// Set post type
		$query_vars['post_type'] = $post_type;

		// Get posts
		$posts = get_posts( $query_vars );

		// Post data to show - this will be manageble at some point
		$show_post_data_fields = array( 'ID', 'post_title', 'post_content', 'post_date' );

		// Post meta data to show - this will be manageble at some point
		$show_post_meta_data_fields = array( 'ssm_supermarkt', 'ssm_adres' );

		// Data array
		$return_data = array();

		// Loop through posts
		foreach ( $posts as $post ) {

			// Get post customs
			$post_custom = get_post_custom( $post->ID );

			$data = array();

			// Add regular post fields data array
			foreach ( $pt_options['fields'] as $show_post_data_field ) {

				$post_field_value = $post->$show_post_data_field;

				// Fetch thumbnail
				if ( 'thumbnail' == $show_post_data_field ) {
					$post_field_value = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
				}

				// Set post field value
				$data[ $show_post_data_field ] = $post_field_value;
			}

			// Add post meta fields to data array
			foreach ( $pt_options['custom'] as $show_post_meta_data_field ) {

				$meta_field_value = get_post_meta( $post->ID, $show_post_meta_data_field, true );

				if ( $meta_field_value != '' ) {
					$data[ $show_post_meta_data_field ] = $meta_field_value;
				}

			}

			$return_data[] = $data;

		}

		$return_data = apply_filters( 'wpsws_get_posts_data', $return_data, $post_type );

		WPSWS_Output::get()->output( $return_data );
	}

}