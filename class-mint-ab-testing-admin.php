<?php
/**
 * Handles admin pages
 *
 * @since 0.9.0.3
 * @version 0.9.0.6
 */
class Mint_AB_Testing_Admin
{

	/**
	 * Hook into actions and filters here, along with any other global setup
	 * that needs to run when this plugin is invoked
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.6
	 */
	public function __construct() {
		add_action( 'admin_menu', array( &$this,'admin_menu' ) );
		add_action( 'admin_init', array( &$this,'register_settings' ) );
	}


	/**
	 * Add the admin menu page
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.6
	 */
	public function admin_menu() {
		add_theme_page( __( 'A/B Testing Configuration', 'mint-ab-testing' ), __( 'A/B Testing', 'mint-ab-testing' ), 'manage_options', Mint_AB_Testing_Options::plugin_id, array( &$this, 'settings_page' ) );
	}


	/**
	 * Register the plugin settings with the WordPress settings API
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.7
	 */
	public function register_settings() {
		register_setting( Mint_AB_Testing_Options::option_group, Mint_AB_Testing_Options::option_name, array( &$this, 'settings_section_validate_main' ) );

		add_settings_section( Mint_AB_Testing_Options::plugin_id . '-main', __( 'Main Settings', 'mint-ab-testing' ), array( &$this, 'settings_section_description_main' ), Mint_AB_Testing_Options::plugin_id );

		add_settings_field( Mint_AB_Testing_Options::option_group . '-endpoint', __( '"B" Theme URL Endpoint', 'mint-ab-testing' ), array( &$this, 'settings_field_endpoint' ), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main' );

		add_settings_field( Mint_AB_Testing_Options::option_group . '-alternate_theme', __( 'Theme Select', 'mint-ab-testing' ), array( &$this, 'settings_field_alternate_theme' ), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main' );

		add_settings_field( Mint_AB_Testing_Options::option_group . '-ratio', __( 'Ratio', 'mint-ab-testing' ), array( &$this, 'settings_field_ratio' ), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main' );

		add_settings_field( Mint_AB_Testing_Options::option_group . '-cookie_ttl', __( '"B" Theme TTL', 'mint-ab-testing' ), array( &$this, 'settings_field_cookie_ttl' ), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main' );

		add_settings_field( Mint_AB_Testing_Options::option_group . '-javascript_redirect', __( 'Javascript Redirect', 'mint-ab-testing' ), array( &$this, 'settings_field_javascript_redirect' ), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main' );

		add_settings_field( Mint_AB_Testing_Options::option_group . '-entrypoints', __( 'Entry Points', 'mint-ab-testing' ), array( &$this, 'settings_field_entrypoints' ), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main' );


		add_settings_field( Mint_AB_Testing_Options::option_group . '-enable', __( 'Enable A/B Testing', 'mint-ab-testing' ), array( &$this, 'settings_field_enable' ), Mint_AB_Testing_Options::plugin_id, Mint_AB_Testing_Options::plugin_id . '-main' );
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
	 * @version 0.9.0.6
	 */
	public function settings_field_enable() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'enable';
		$id = $options::option_group . '-' . $settings_field_name;

		$enable = $options->get_option( $settings_field_name );

		echo '<label><input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="yes" ' . checked( ( 'yes' === $enable ), true, false ) . '/>&nbsp;' . __( 'On', 'mint-ab-testing' ) . '</label><br />';
		echo '<label><input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="no" ' . checked( ( 'no' === $enable ), true, false ) . '/>&nbsp;' . __( 'Off', 'mint-ab-testing' ) . '</label><br />';
	}


	/**
	 * Output alternate theme select settings field
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.6
	 */
	public function settings_field_alternate_theme() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'alternate_theme';
		$id = $options::option_group . '-' . $settings_field_name;

		$alternate_theme = $options->get_option( $settings_field_name );
		$current_theme = get_current_theme();
		$themes = get_allowed_themes();
		unset( $themes[$current_theme] );

		printf( __( '"A" Theme: %s<br />', 'mint-ab-testing' ), $current_theme );

		_e( '"B" Theme: ', 'mint-ab-testing' );

		if ( empty( $themes ) ) {
			_e( 'You have no other themes installed!', 'mint-ab-testing' );

			echo '<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="hidden" value="" />';
		} else {
			echo '<select name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '">';
			echo '<option value="">' . __( 'Select one...', 'mint-ab-testing' ) . '</option>';

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
	 * @version 0.9.0.6
	 *
	 * @todo Use jQuery UI slider
	 */
	public function settings_field_ratio() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'ratio';
		$ratio = $options->get_option( $settings_field_name );
		$id = $options::option_group . '-' . $settings_field_name;

		$field_output = '<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="4" value="' . $ratio . '" />';
		printf( __( 'Show the "B" Theme %s%% of the time', 'mint-ab-testing' ), $field_output );

		echo '<br /><span class="description">' . __( 'Visitors will not be redirected from the "B" theme back to the "A" theme.  If a visitor has an "A" theme cookie and lands on a "B" theme URL (e.g., by following a link), they will stay on the "B" theme.  If they later return to the site via the "A" theme they will stay on the "A" theme because they still have their "A" theme cookie.', 'mint-ab-testing' ) . '</span>';
	}


	/**
	 * Output cookie TTL settings field
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.7
	 */
	public function settings_field_cookie_ttl() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'cookie_ttl';
		$cookie_ttl = $options->get_option( $settings_field_name );
		$id = $options::option_group . '-' . $settings_field_name;

		$field_output = '<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="3" value="' . $cookie_ttl . '" />';
		printf( __( 'Visitors who see the "B" Theme will see it for %s days', 'mint-ab-testing' ), $field_output );

		echo '<br /><span class="description">' . __( 'Set to "0" to expire at the end of the browser session.', 'mint-ab-testing' ) . '</span>';
	}


	/**
	 * Output javascript redirect settings field(s)
	 *
	 * @since 0.9.0.7
	 * @version 0.9.0.7
	 */
	public function settings_field_javascript_redirect() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'javascript_redirect';
		$id = $options::option_group . '-' . $settings_field_name;

		$javascript_redirect = $options->get_option( $settings_field_name );

		echo '<label><input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="checkbox" value="1" ' . checked( ( 1 == $javascript_redirect ), true, false ) . '/>&nbsp;' . __( 'Use Javascript to redirect to "B" theme', 'mint-ab-testing' ) . '</label><br />';
		echo '<span class="description">' . __( 'If your page requests usually get returned from a cache (proxy caching or full page caching) you should enable this.', 'mint-ab-testing' ) . '</span>';

		// Additional help text for handling analytics
		if ( class_exists('Pmc_Google_Analytics') ) {
			$additional_help_text = __( 'It looks like you are using the PMC Google Analytics plugin.  Referrer tracking will be handled automatically.', 'mint-ab-testing' );
		} elseif ( class_exists('Yoast_GA_Plugin_Admin') ) {
			$additional_help_text = __( 'It looks like you are using the Google Analytics for WordPress plugin.  Referrer tracking will be handled automatically.', 'mint-ab-testing' );
		} else {
			$additional_help_text = __( '<br />This plugin will handle this automatically if you are using the <strong>PMC Google Analytics</strong> or <strong>Google Analytics for WordPress</strong> plugin, otherwise you will need to implement this yourself.', 'mint-ab-testing' );
		}
		echo '<br /><span class="description">' . sprintf( __( 'Using javascript redirects with an analytics package like Google Analytics or Omniture requires some additional work to properly track referrers.  %s', 'mint-ab-testing' ), $additional_help_text ) . '</span>';
	}


	/**
	 * Output entry point settings field(s)
	 *
	 * @since 0.9.0.7
	 * @version 0.9.0.7
	 */
	public function settings_field_entrypoints() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'entrypoints';
		$id = $options::option_group . '-' . $settings_field_name;

		$entrypoints = $options->get_option( $settings_field_name );

		echo '<div id="' . $id . '">';
		foreach ( $entrypoints as $entrypoint => $enabled ) {
			switch ( $entrypoint ) {
				case 'home':
					$label = __( 'Home', 'mint-ab-testing' );
					break;

				case 'singular':
					$label = __( 'Single pages: Post, Page, Attachment, and single custom post type pages', 'mint-ab-testing' );
					break;

				case 'archive':
					$label = __( 'Archive pages: Tag, Category, custom taxonomy, date-based archives, and custom post type archives', 'mint-ab-testing' );
					break;

				case 'search':
					$label = __( 'Search results', 'mint-ab-testing' );
					break;

				case '404':
					$label = __( '404 Not Found error pages', 'mint-ab-testing' );
					break;

				default:
					// No default, has to be specified above
					$label = '';
					break;

			}
			echo '<label><input name="' . $options::option_name . '[' . $settings_field_name . '][' . $entrypoint . ']" id="' . $id . '-' . $entrypoint . '" type="checkbox" value="1" ' . checked( ( 1 == $enabled ), true, false ) . '/>&nbsp;' .$label . '</label><br />';
		}
		echo '</div>';

		echo '<a style="cursor: pointer;" onclick="jQuery(\'#' . $id . ' input\').each(function(){ jQuery(this).attr(\'checked\', true); });">' . __( 'Select All', 'mint-ab-testing' ) . '</a> / <a style="cursor: pointer;" onclick="jQuery(\'#' . $id . ' input\').each(function(){ jQuery(this).attr(\'checked\', false); });">' . __( 'Select None', 'mint-ab-testing' ) . '</a><br />';

		echo '<span class="description">' . __( 'Only run the A/B test when landing on one of the page types above.', 'mint-ab-testing' ) . '</span>';
	}


	/**
	 * Output the endpoint settings field
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.6
	 */
	public function settings_field_endpoint() {
		$options = Mint_AB_Testing_Options::instance();
		$settings_field_name = 'endpoint';
		$endpoint = $options->get_option( $settings_field_name );
		$id = $options::option_group . '-' . $settings_field_name;

		echo home_url() . '/<input name="' . $options::option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="4" value="' . $endpoint . '" />/';
		echo '<br /><span class="description">' . __( 'This identifies the alternate theme ("B" theme).	 Users who visit a URL with this at the end will see the "B" theme.', 'mint-ab-testing' ) . '</span>';
	}


	/**
	 * Validate the options being saved in the "Main" settings section
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.7
	 *
	 * @todo Error messages / warnings
	 * @todo Don't make this an all-in-one function
	 */
	public function settings_section_validate_main( $saved_options ) {
		$options = Mint_AB_Testing_Options::instance();

		foreach ( $saved_options as $key => &$value ) {
			switch ( $key ) {
				case 'alternate_theme':
					// Prevent invalid or nonexistent theme names from being saved
					$themes = get_allowed_themes();

					if ( ! isset( $themes[$value] ) ) {
						$value = '';
					}
					break;

				case 'ratio':
					// Make sure ratio is an integer
					$value = intval( $value );

					if ( $value > 100 ) {
						$value = 100;
					} elseif ( $value < 0 ) {
						$value = 0;
					}
					break;

				case 'enable':
					// Make sure "enable" is one of our valid values
					$value = ( in_array( $value, array( 'yes', 'no', 'preview' ) ) ) ? $value : 'no';
					break;

				case 'cookie_ttl':
					// Make sure ratio is an integer
					$value = intval( $value );

					if ( $value < 1 ) {
						$value = 0;
					} else {
						$value = ( 60 * 60 * 24 * 1 );
					}
					break;

				case 'used_endpoints':
					$value = explode( ',', $value );
					break;

				case 'javascript_redirect':
					$value = intval( $value );
					break;

				case 'entrypoints':
					// Only checked (true) settings get POSTed.  Any false/unset entrypoints are not set. So we'll take the defaults, and if it hasn't been POSTed we can safely assume it's false.  Side benefit: we don't have to worry about invalid/unaccounted keys.
					$entrypoint_defaults = $options->get_option_default( 'entrypoints' );
					foreach ( $entrypoint_defaults as $entrypoint => &$enabled ) {
						if ( ! isset( $value[$entrypoint] ) ) {
							$enabled = false;
						}
					}
					$value = $entrypoint_defaults;
					break;

				default:
					// Do nothing
					break;
			}

			// Reset the options in the current instance
			$options->set_option( $key, $value );
		}

		return $saved_options;
	}


	/**
	 * Output the settings page
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.6
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Mint A/B Testing', 'mint-ab-testing' ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( Mint_AB_Testing_Options::option_group );
				do_settings_sections( Mint_AB_Testing_Options::plugin_id );
				?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'mint-ab-testing' ) ?>" />
				</p>
			</form>
		</div>
		<?php
	}

}

// EOF