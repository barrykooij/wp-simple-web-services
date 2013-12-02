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
		add_menu_page( 'Overview', 'Web Services', 'manage_options', 'wpw', array( $this, 'screen_main' ), 'div' );
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wpw-admin', plugin_dir_url( WPSWS_PLUGIN_FILE ) . '/assets/js/wpw-admin.js', array( 'jquery' ), '1.0.0' );
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
		$options['get_posts'][ $_POST['post_type'] ] = array(
			'enabled' => $_POST['enabled'],
			'fields'	=> $fields,
			'custom'	=> $custom
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
		<div class="wrap" id="wpw-wrap">
			<h2>WordPress Simple Web Services</h2>
			<div>
				<p><?php _e( 'Welcome to WordPress Simple Web Services! You can enable post types by clicking the "Enable post type" checkbox below. If you encounter problems or find any bugs please report hem on <a href="https://github.com/barrykooij/wp-simple-web-services/issues" target="_blank">GitHub</a>.', 'wpsws' ); ?></p>
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
								if ( isset( $gp_options[ $key ] ) ) {
									$pt_options = wp_parse_args( $gp_options[ $key ], $pt_options );
								}

								// Default post type fields
								$post_type_supports	= array(
									'ID' 						=> 'ID',
									'post_date' 		=> __( 'Post Date', 'wpw' ),
									'post_status' 	=> __( 'Post Status', 'wpw' ),
									'post_modified' => __( 'Post Modified', 'wpw' ),
									'post_parent' 	=> __(  'Post Parent', 'wpw' ),
									'menu_order' 		=> __( 'Menu Order', 'wpw' ),
									'post_type' 		=> __( 'Post Type', 'wpw' )
								);

								// Default post type fields that might be supported
								$post_type_maybe_supports = array(
									'title' 			=> array( array( 'key' => 'post_title', 'value' => __( 'Post Title', 'wpw' ) ) ),
									'editor' 			=> array( array( 'key' => 'post_content', 'value' => __( 'Post Content', 'wpw' ) ) ),
									'author' 			=> array( array( 'key' => 'post_author', 'value' => __( 'Post Author', 'wpw' ) ) ),
									'thumbnail' 	=> array( array( 'key' => 'thumbnail', 'value' => __( 'Thumbnail', 'wpw' ) ) ),
									'excerpt' 		=> array( array( 'key' => 'post_excerpt', 'value' => __( 'Post Excerpt', 'wpw' ) ) ),
									'comments' 		=> array( array( 'key' => 'comment_status', 'value' => __( 'Comment Status', 'wpw' ) ), array( 'key' => 'comment_count', 'value' => __( 'Comment Count', 'wpw' ) ) ),
								);

								// Check if the current post type supports the optional fields
								foreach ( $post_type_maybe_supports as $supports_key => $post_type_maybe_support_fields ) {
									if ( post_type_supports( $key, $supports_key ) ) {
										foreach ( $post_type_maybe_support_fields as $post_type_maybe_support_field ) {
											$post_type_supports[ $post_type_maybe_support_field['key'] ] = $post_type_maybe_support_field['value'];
										}
									}
								}

								// Custom fields
								$custom_fields 	= array();
								$dummy_post 		= get_posts( array( 'post_type' => $key, 'posts_per_page' => 1 ) );
								if ( is_array( $dummy_post ) && count( $dummy_post ) > 0 ) {
									$dummy_post = array_shift( $dummy_post );

									$post_custom_fields = get_post_custom( $dummy_post->ID );

									if ( is_array( $post_custom_fields ) && count( $post_custom_fields ) > 0 ) {
										foreach ( $post_custom_fields as $custom_field => $custom_value ) {
											if ( substr( $custom_field, 0, 1 ) != '_' ) {
												$custom_fields[ $custom_field ] = $custom_field;
											}
										}
									}

								}

								echo "<dt><a href=''>{$post_type->labels->name}</a></dt>\n";
								echo "<dd id='wpw_pt_{$key}'>";

									echo "<input type='hidden' class='ajax_nonce' value='" . wp_create_nonce( 'wpw-ajax-security' ) . "' />\n";
									echo "<input type='hidden' class='post_type' value='" . $key . "' />\n";

									echo "<label for='enable_{$key}'><input type='checkbox' name='enabled' class='wpw_enabled' id='enable_{$key}' " . ( ( 'true' == $pt_options['enabled'] ) ? "checked='checked' " : "" ) . "/> " . __( 'Enable post type', 'wpw' ) . "</label><br/><br/>\n";

									// Default fields
									echo "<b>" . __( 'Enable fields', 'wpw' ). ":</b><br/>\n";
									foreach ( $post_type_supports as $post_type_field => $post_type_label ) {
										echo "<label for='post_type_{$key}_field_{$post_type_field}'><input type='checkbox' name='field[]' value='{$post_type_field}' class='wpw_fields' id='post_type_{$key}_field_{$post_type_field}' " . ( ( false !== array_search( $post_type_field, $pt_options['fields'] ) ) ? "checked='checked' " : "" ) . "/> {$post_type_label}</label><br/>\n";
									}

									echo "<br />";

									// Custom fields
									echo "<b>" . __( 'Custom fields', 'wpw' ). ":</b><br/>\n";
									foreach ( $custom_fields as $post_type_field => $post_type_label ) {
										echo "<label for='post_type_{$key}_field_{$post_type_field}'><input type='checkbox' name='custom[]' value='{$post_type_field}' class='wpw_custom' id='post_type_{$key}_field_{$post_type_field}' " . ( ( false !== array_search( $post_type_field, $pt_options['custom'] ) ) ? "checked='checked' " : "" ) . "/> {$post_type_label}</label><br/>\n";
									}

									echo submit_button( __( 'Save', 'wpw' ) );

								echo "</dd>\n";

							}

						}

					?>

				</dl>

			</div>
		</div>
	<?php
	}

}