<?php
/**
 * Handles admin pages
 *
 * @since 0.9.0.3
 * @version 0.9.0.3
 */
class Mint_AB_Testing_Admin
{

	/**
	 * Hook into actions and filters here, along with any other global setup
	 * that needs to run when this plugin is invoked
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function __construct() {
		add_action( 'admin_menu', array(&$this,'admin_menu') );
		add_action( 'admin_init', array(&$this,'register_settings') );
		add_action( 'generate_rewrite_rules', array(&$this, 'generate_rewrite_rules') );
	}


	/**
	 *
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function activate() {
		global $wp_rewrite;

		$this->generate_rewrite_rules();

		$wp_rewrite->flush_rules(false);
	}


	/**
	 *
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function deactivate() {
		global $wp_rewrite;

		$wp_rewrite->flush_rules(false);
	}


	/**
	 *
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function generate_rewrite_rules() {
		$options = Mint_AB_Testing_Options::instance();

		add_rewrite_endpoint( $options->get_option('endpoint'), EP_ALL );
	}


	/**
	 * Add the admin menu page
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function admin_menu() {
		add_theme_page( __( 'A/B Testing Configuration', Mint_AB_Testing_Options::text_domain ), __( 'A/B Testing', Mint_AB_Testing_Options::text_domain ), 'manage_options', Mint_AB_Testing_Options::plugin_id, array(&$this, 'settings_page') );
	}


	/**
	 * Register the plugin settings with the WordPress settings API
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function register_settings() {
		register_setting( Mint_AB_Testing_Options::option_group, Mint_AB_Testing_Options::option_name, array(&$this, 'settings_section_validate_main') );

		add_settings_section(Mint_AB_Testing_Options::plugin_id . '-main', __( 'Main Settings', Mint_AB_Testing_Options::text_domain ), array(&$this, 'settings_section_description_main'), Mint_AB_Testing_Options::plugin_id);

		add_settings_field(Mint_AB_Testing_Options::option_group . '-endpoint', __( '"B" Theme URL Endpoint', Mint_AB_Testing_Options::text_domain ), array(&$this, 'settings_field_endpoint'), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main');

		add_settings_field(Mint_AB_Testing_Options::option_group . '-alternate_theme', __( 'Theme Select', Mint_AB_Testing_Options::text_domain ), array(&$this, 'settings_field_alternate_theme'), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main');

		add_settings_field(Mint_AB_Testing_Options::option_group . '-ratio', __( 'Ratio', Mint_AB_Testing_Options::text_domain ), array(&$this, 'settings_field_ratio'), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main');

		add_settings_field(Mint_AB_Testing_Options::option_group . '-cookie_ttl', __( '"B" Theme TTL', Mint_AB_Testing_Options::text_domain ), array(&$this, 'settings_field_cookie_ttl'), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main');


		add_settings_field(Mint_AB_Testing_Options::option_group . '-enable', __( 'Enable A/B Testing', Mint_AB_Testing_Options::text_domain ), array(&$this, 'settings_field_enable'), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main');
	}


	/**
	 * Output the description HTML for the "Main" settings section
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function settings_section_description_main() {
		// Doesn't do anything, I just don't want to forget it's here
	}


	/**
	 * Output enable/disable settings field(s)
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function settings_field_enable() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'enable';
		$id = $options::option_group . '-' . $settings_field_name;

		$enable = $options->get_option($settings_field_name);

		echo '<label><input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="yes" ' . checked( ( 'yes' === $enable ), true, false) . '/>&nbsp;' . __( 'On', $options::text_domain ) . '</label><br />';
		echo '<label><input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="no" ' . checked( ( 'no' === $enable ), true, false) . '/>&nbsp;' . __( 'Off', $options::text_domain ) . '</label><br />';
	}


	/**
	 * Output alternate theme select settings field
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function settings_field_alternate_theme() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'alternate_theme';
		$id = $options::option_group . '-' . $settings_field_name;

		$alternate_theme = $options->get_option($settings_field_name);
		$current_theme = get_current_theme();
		$themes = get_allowed_themes();
		unset($themes[$current_theme]);

		printf( __( '"A" Theme: %s<br />', $options::text_domain ), $current_theme );

		_e( '"B" Theme: ', $options::text_domain );

		if ( empty($themes) ) {
			_e( 'You have no other themes installed!', $options::text_domain );

			echo '<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="hidden" value="" />';
		} else {
			echo '<select name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '">';
			echo '<option value="">' . __( 'Select one...', $options::text_domain ) . '</option>';

			foreach ( $themes as $name => $data ) {

				echo '<option value="' . $name . '"' . selected( ( $alternate_theme === $name ), true, false ) . '>' . $name . '</option>';
			}

			echo '</select>';
		}
	}


	/**
	 * Output A/B ratio settings field
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 *
	 * @todo Use jQuery UI slider
	 */
	public function settings_field_ratio() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'ratio';
		$ratio = $options->get_option($settings_field_name);
		$id = $options::option_group . '-' . $settings_field_name;

		$field_output = '<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="4" value="' . $ratio . '" />';
		printf( __( 'Show the "B" Theme %s%% of the time', $options::text_domain ), $field_output );
	}


	/**
	 * Output cookie TTL settings field
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function settings_field_cookie_ttl() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'cookie_ttl';
		$cookie_ttl = $options->get_option($settings_field_name);
		$id = $options::option_group . '-' . $settings_field_name;

		$field_output = '<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="3" value="' . $cookie_ttl . '" />';
		printf( __( 'Visitors who see the "B" Theme will see it for %s days', $options::text_domain ), $field_output );

		echo '<br /><span class="description">' . __( 'Set to "0" to expire at the end of the browser session', $options::text_domain ) . '</span>';
	}


	/**
	 * Output the endpoint settings field
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function settings_field_endpoint() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'endpoint';
		$endpoint = $options->get_option($settings_field_name);
		$id = $options::option_group . '-' . $settings_field_name;

		echo home_url() . '/<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="4" value="' . $endpoint . '" />/';
		echo '<br /><span class="description">' . __( 'This identifies the alternate theme ("B" theme).	 Users who visit a URL with this at the end will see the "B" theme.', $options::text_domain ) . '</span>';
	}


	/**
	 * Validate the options being saved in the "Main" settings section
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 *
	 * @todo Error messages / warnings
	 * @todo Don't make this an all-in-one function
	 */
	public function settings_section_validate_main($saved_options) {
		$options = Mint_AB_Testing_Options::instance();

		foreach ( $saved_options as $key => $value ) {
			switch ( $key ) {
				case 'alternate_theme':
					// Prevent invalid or nonexistent theme names from being saved
					$themes = get_allowed_themes();

					if ( ! isset($themes[$value]) ) {
						$value = '';
					}
					break;

				case 'ratio':
					// Make sure ratio is an integer
					$value = intval($value);

					if ( $value > 100 ) {
						$value = 100;
					} elseif ( $value < 0 ) {
						$value = 0;
					}
					break;

				case 'enable':
					// Make sure "enable" is one of our valid values
					$value = (in_array( $value, array( 'yes', 'no', 'preview' ) )) ? $value : 'no';
					// Always run "activate" so that the endpoints are present regardless
					// whether or not the A/B testing is enabled.  This prevents 404s as
					// long as the plugin stays enabled.
					$this->activate();
					break;

				case 'cookie_ttl':
					// Make sure ratio is an integer
					$value = intval($value);

					if ( $value < 0 ) {
						$value = 0;
					} else {
						$value = (60 * 60 * 24 * 1);
					}
					break;

				case 'endpoint':
					// If the endpoint has been changed, reset the endpoints
					if ( $value !== $options->get_option('endpoint') ) {
						$options->set_option('endpoint', $value);
						$this->activate();
					}
					break;

				case 'used_endpoints':
					$value = explode(',', $value);
					break;

				default:
					// Do nothing
					break;
			}

			$options->set_option($key, $value);
		}

		return $saved_options;
	}


	/**
	 * Output the settings page
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.3
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h2><?php _e('Mint A/B Testing', Mint_AB_Testing_Options::text_domain ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( Mint_AB_Testing_Options::option_group );
				do_settings_sections( Mint_AB_Testing_Options::plugin_id );
				?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes', Mint_AB_Testing_Options::text_domain ) ?>" />
				</p>
			</form>
		</div>
		<?php
	}

}

// EOF