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
	 * Setup hooks
	 */
	private function hooks() {
		add_action( 'wpsws_webservice_get_posts', array( $this, 'get_posts' ) );
		add_action( 'wpsws_general_settings', array( $this, 'settings' ), 1 );
	}

	/**
	 * get_posts settings screen
	 */
	public function settings() {
		?>
		<script type="text/javascript">
			(function($) {
				$('body').ready(function() {
					var allPanels = $('.accordion > dd').hide();

					$('.accordion > dt > a').click(function() {
						allPanels.slideUp();
						$(this).parent().next().slideDown();
						return false;
					});
				});
			})(jQuery);
		</script>
		<style type="text/css">
			.accordion dt, .accordion dd {
				padding: 10px;
				border: 1px solid black;
				border-bottom: 0;
				margin: 0;
			}

			.accordion dt:last-of-type, .accordion dd:last-of-type {
				border-bottom: 1px solid black;
			}

			.accordion dt a, .accordion dd a {
				display: block;
				color: black;
				font-weight: bold;
			}

			.accordion dd {
				border-top: 0;
				font-size: 12px;
			}

			.accordion dd:last-of-type {
				border-top: 1px solid white;
				position: relative;
				top: -1px;
			}
		</style>
		<h2>Post Types</h2>
		<dl class="accordion">

			<?php

			// Global options
			$options = WP_Simple_Web_Service::get()->get_options();

			// Get 'get_posts' options
			$gp_options = array();
			if ( isset( $options['get_posts'] ) ) {
				$gp_options = $options['get_posts'];
			}

			// Get post types
			$post_types = get_post_types( array( 'public' => true ), 'objects' );

			if ( count( $post_types ) > 0 ) {

				foreach ( $post_types as $key => $post_type ) {

					// Post type options
					$pt_options = WPSWS_Webservice_get_posts::get()->get_default_settings();
					if ( isset( $gp_options[$key] ) ) {
						$pt_options = wp_parse_args( $gp_options[$key], $pt_options );
					}

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

					// Custom fields
					$custom_fields = array();
					$dummy_post    = get_posts( array( 'post_type' => $key, 'posts_per_page' => 1 ) );
					if ( is_array( $dummy_post ) && count( $dummy_post ) > 0 ) {
						$dummy_post = array_shift( $dummy_post );

						$post_custom_fields = get_post_custom( $dummy_post->ID );

						if ( is_array( $post_custom_fields ) && count( $post_custom_fields ) > 0 ) {
							foreach ( $post_custom_fields as $custom_field => $custom_value ) {
								if ( substr( $custom_field, 0, 1 ) != '_' ) {
									$custom_fields[$custom_field] = $custom_field;
								}
							}
						}

					}

					echo "<dt><a href=''>{$post_type->labels->name}</a></dt>\n";
					echo "<dd id='wpw_pt_{$key}'>";

					echo "<input type='hidden' class='ajax_nonce' value='" . wp_create_nonce( 'wpw-ajax-security' ) . "' />\n";
					echo "<input type='hidden' class='post_type' value='" . $key . "' />\n";

					echo "<label for='enable_{$key}'><input type='checkbox' name='enabled' class='wpw_enabled' id='enable_{$key}' " . ( ( 'true' == $pt_options['enabled'] ) ? "checked='checked' " : "" ) . "/> " . __( 'Enable post type', 'wpw' ) . "</label><br/><br/>\n";

					echo "<b>Web Service URL:</b><br/>";
					echo '<input type="text" name="webservice_url" value="' . get_site_url() . '/webservice/get_posts/?post_type=' . $key . '" disabled="disabled" style="width:100%;" />';
					echo "<br/><br/>";

					// Default fields
					echo "<b>" . __( 'Enable fields', 'wpw' ) . ":</b><br/>\n";
					foreach ( $post_type_supports as $post_type_field => $post_type_label ) {
						echo "<label for='post_type_{$key}_field_{$post_type_field}'><input type='checkbox' name='field[]' value='{$post_type_field}' class='wpw_fields' id='post_type_{$key}_field_{$post_type_field}' " . ( ( false !== array_search( $post_type_field, $pt_options['fields'] ) ) ? "checked='checked' " : "" ) . "/> {$post_type_label}</label><br/>\n";
					}

					echo "<br />";

					// Custom fields
					echo "<b>" . __( 'Custom fields', 'wpw' ) . ":</b><br/>\n";
					foreach ( $custom_fields as $post_type_field => $post_type_label ) {
						echo "<label for='post_type_{$key}_field_{$post_type_field}'><input type='checkbox' name='custom[]' value='{$post_type_field}' class='wpw_custom' id='post_type_{$key}_field_{$post_type_field}' " . ( ( false !== array_search( $post_type_field, $pt_options['custom'] ) ) ? "checked='checked' " : "" ) . "/> {$post_type_label}</label><br/>\n";
					}

					echo submit_button( __( 'Save', 'wpw' ) );

					echo "</dd>\n";

				}

			}

			?>

		</dl>
	<?php
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

		WPSWS_Output::get()->output( $return_data );
	}

}