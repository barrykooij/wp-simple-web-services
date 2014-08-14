<?php

class WPSWS_Settings {

	private static $instance = null;

	private $setting_tabs = array();

	/**
	 * Get singleton instance of class
	 *
	 * @return null|WPSWS_Settings
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
	 * Hooks
	 */
	private function hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {

		register_setting( 'wpsws', WP_Simple_Web_Service::OPTION_KEY );

		do_action( 'wpsws_register_settings', array( &$this ) );

		$db_option = WP_Simple_Web_Service::get()->get_options();

		foreach( $this->setting_tabs as $tab_id => $tab ) {

			foreach( $tab['sections'] as $section_id => $tab_section ) {
				$wp_section_id = 'wpsws_'.$tab_id.'_'.$section_id;
				add_settings_section(
					$wp_section_id,
					$tab_section['title'],
					create_function( '', 'echo "<p class=\"description\">'.$tab_section['desc'].'</p>";' ),
					'wpsws_'.$tab_id
				);

				foreach( $tab_section['settings'] as $setting_id => $setting ) {
					$html_setting_id = WP_Simple_Web_Service::OPTION_KEY.'['.$tab_id.']['.$section_id.']['.$setting_id.']';
					if( ! isset( $db_option[$tab_id] ) || ! isset( $db_option[$tab_id][$section_id][$setting_id] ) ) {
						$value = '';
					} else {
						$value = $db_option[$tab_id][$section_id][$setting_id];
					}


					add_settings_field(
						$html_setting_id,
						$setting['label'],
						array( $this, 'field_'.$setting['type'] ),
						'wpsws_'.$tab_id,
						$wp_section_id,
						array(
							'options' => $setting['type_options'],
							'value' => $value,
							'setting_id' => $html_setting_id,
							'label' => $setting['label']
						)
					);
				}
			}

		}

	}

	/**
	 * Add a settings section to the settings screen
	 *
	 * @param string $id
	 * @param string $title
	 * @param string $desc
	 *
	 * @return bool
	 */
	public function add_tab( $id, $title='', $desc='' ) {
		if( isset( $this->setting_tabs[$id] ) ) {
			trigger_error( 'WP Simple Web Services: There is already a tab with the ID "'.$id.'"', E_USER_NOTICE);
			return false;
		} else {
			$this->setting_tabs[$id] = array(
				'title' => $title,
				'desc' => $desc,
				'sections' => array()
			);
			return true;
		}
	}

	/**
	 * Add a settings section to the settings screen
	 *
	 * @param string $id
	 * @param string $title
	 * @param string $tab_id
	 * @param string $desc
	 *
	 * @return bool
	 */
	public function add_section( $id, $title='', $tab_id, $desc='' ) {
		if( ! isset( $this->setting_tabs[$tab_id] ) ) {
			trigger_error( 'WP Simple Web Services: There is no tab section with the ID "'.$tab_id.'", please register it before adding a setting to it.', E_USER_NOTICE );
			return false;
		} else if( isset( $this->setting_tabs[$tab_id]['sections'][$id] ) ) {
			trigger_error( 'WP Simple Web Services: There is already a section with the ID "'.$id.'"', E_USER_NOTICE);
			return false;
		} else {
			$this->setting_tabs[$tab_id]['sections'][$id] = array(
				'title' => $title,
				'desc' => $desc,
				'settings' => array()
			);
			return true;
		}
	}

	/**
	 * Add setttings field to a settings section on the setting screen
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $type
	 * @param string $tab_id
	 * @param string $section_id
	 * @param string $desc
	 * @param array $type_options
	 *
	 * @return bool
	 */
	public function add_setting( $id, $label='', $type='text', $tab_id, $section_id, $desc='', $type_options=array() ) {

		if( ! isset( $this->setting_tabs[$tab_id] ) ) {
			trigger_error( 'WP Simple Web Services: There is no tab section with the ID "'.$tab_id.'", please register it before adding a setting to it.', E_USER_NOTICE );
			return false;
		} else if( ! isset( $this->setting_tabs[$tab_id]['sections'][$section_id] ) ) {
			trigger_error( 'WP Simple Web Services: There is no settings section with the ID "'.$section_id.'", please register it before adding a setting to it.', E_USER_NOTICE );
			return false;
		} else if( isset( $this->setting_tabs[$tab_id]['sections'][$section_id]['settings'][$id] ) ) {
			trigger_error( 'WP Simple Web Services: There is already a setting with the ID "'.$id.'" in section "'.$section_id.'"', E_USER_NOTICE);
			return false;
		} else {
			$this->setting_tabs[$tab_id]['sections'][$section_id]['settings'][$id] = array(
				'id' => $id,
				'label' => $label,
				'desc' => $desc,
				'type' => $type,
				'type_options' => $type_options,
				'section_id' => $section_id,
			);
			return true;
		}
	}

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
	 * Add menu pages
	 */
	public function add_menu_pages() {
		add_menu_page( 'Overview', 'Web Services', 'manage_options', 'wpsws', array( $this, 'screen_main' ) );
	}

	public function field_checkbox($args) {
	?>
		<label><input type="checkbox" name="<?php echo $args['setting_id']; ?>" <?php checked( 'true', $args['value'] ); ?> value="true" /><?php echo $args['label']; ?></label>
	<?php
	}

	public function field_checkbox_array($args) {
		if( '' === $args['value'] ) {
			$args['value'] = array();
		}
		foreach( $args['options']['fields'] as $field_id => $field_title ) {
		?>
			<label><input type="checkbox" name="<?php echo $args['setting_id']; ?>[]" <?php checked( in_array( $field_id, $args['value'] ) ); ?> value="<?php echo $field_id; ?>" /><?php echo $field_title; ?></label><br/>
		<?php
		}
	}

	public function field_radio_array($args) {
		foreach( $args['options']['fields'] as $field_id => $field_title ) {
			?>
			<label><input type="radio" name="<?php echo $args['setting_id']; ?>" <?php checked( $args['value'], $field_id ); ?> value="<?php echo $field_id; ?>" /><?php echo $field_title; ?></label><br/>
		<?php
		}
	}

	public function field_text($args) {

		$disabled = ( isset( $args['options']['disabled'] ) && $args['options']['disabled'] );
		if( $disabled ) {
			$value = $args['options']['value'];
		} else {
			$value = $args['value'];
		}
	?>
		<input style="width:100%;" type="text" <?php if( !$disabled ){ ?>name="<?php echo $args['setting_id']; ?>" <?php } else{ ?>disabled="disabled"<?php } ?> value="<?php echo $value; ?>" />
	<?php
	}

	/**
	 * The main screen
	 */
	public function screen_main() {
	?>
		<div class="wrap" id="wpw-wrap">
			<h2>WordPress Simple Web Services</h2>

			<div>
				<p><?php _e( 'Welcome to WordPress Simple Web Services! You can enable post types by clicking the "Enable post type" checkbox below. If you encounter problems or find any bugs please report them on <a href="https://github.com/barrykooij/wp-simple-web-services/issues" target="_blank">GitHub</a>.', 'wpsws' ); ?></p>
				<?php settings_errors(); ?>
					<h2 class="nav-tab-wrapper hide-if-no-js">
						<?php foreach( $this->setting_tabs as $tab_id => $tab ) { ?>
							<a href="#" data-tab_id="<?php echo $tab_id; ?>" class="nav-tab"><?php echo $tab['title']; ?></a>
						<?php } ?>
					</h2>
				<form method='post' action='<?php echo admin_url('options.php'); ?>'>
				<?php
					settings_fields( 'wpsws' );
					foreach( $this->setting_tabs as $tab_id => $tab ) {
						echo '<div id="'.$tab_id.'" class="wpsws_tab">';
						echo '<h2>'.$tab['title'].'</h2>';
						echo '<p class="description">'.$tab['desc'].'</p>';

						do_settings_sections( 'wpsws_'.$tab_id );
						submit_button( __( 'Save', 'wpw' ) );
						echo '</div>';
					}
				?>
				</form>
				<script>
					jQuery(document).ready(function($) {
						var tabs = $('.wpsws_tab');
						var nav_items = $('.nav-tab');
						tabs.hide();
						tabs.first().show();
						nav_items.first().addClass('nav-tab-active');
						nav_items.click(function(e){
							e.preventDefault();
							nav_items.removeClass('nav-tab-active');
							tabs.hide();
							tabs.parent().find('#'+$(this).data('tab_id')).show();
							$(this).addClass('nav-tab-active');
						})
					});
				</script>
				<?php
					if( null !== do_action( 'wpsws_general_settings' ) ) {
						trigger_error( 'WP Simple Webservices: the use of the hook "wpsws_general_settings" is deprecated and will be removed in a future version', E_USER_NOTICE );
					}
				?>
			</div>
		</div>
	<?php
	}

}