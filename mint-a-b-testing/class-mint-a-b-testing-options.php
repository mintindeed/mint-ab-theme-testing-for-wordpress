<?php
/**
 * Handles get/set of plugin options and WordPress options page
 *
 * @since 0.9.0.0 2011-11-05 Gabriel Koen
 * @version 0.9.0.2 2011-11-13 Gabriel Koen
 */
class Mint_AB_Testing_Options
{

	/**
	 * String to use for the plugin name.  Used for generating class names, etc.
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 *
	 * @var string
	 */
	public static $plugin_id = 'mint-a-b-testing';

	/**
	 * String to use for the textdomain filename
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 *
	 * @var string
	 */
	public static $text_domain = 'mint-a-b-testing';

	/**
	 * Name of the option group for WordPress settings API
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 *
	 * @var string
	 */
	protected $_option_group = 'mint-a-b-testing-group';

	/**
	 * Name of the option for WordPress settings API
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 *
	 * @var string
	 */
	public static $option_name = 'mint_a_b_testing_options';

	/**
	 * Contains default options that get overridden in the constructor
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 *
	 * @var array
	 */
	public static $options_defaults = array(
		'enable' => 'no',
		'ratio' => 50,
		'alternate_theme' => 'Twenty Ten',
		'cookie_ttl' => 0,
		'endpoint' => 'v02',
	);

	/**
	 * Contains merged defaults + saved options
	 *
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 * @var array
	 */
	public static $options = array();

	/**
	 *
	 *
	 * @since 0.9.0.2 2011-11-15 Gabriel Koen
	 * @version 0.9.0.2 2011-11-15 Gabriel Koen
	 *
	 * @var bool
	 */
	protected $_has_endpoint = false;

	/**
	 * Hook into actions and filters here, along with any other global setup
	 * that needs to run when this plugin is invoked
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 */
	private function __construct() {
		add_action( 'admin_menu', array(&$this,'admin_menu') );
		add_action( 'admin_init', array(&$this,'register_settings') );

		add_action( 'generate_rewrite_rules', array('Mint_AB_Testing_Options', 'add_endpoints') );
		add_filter( 'request', array(&$this, 'request') );

	}

	/**
	 * Returns Singleton instance of this class.  Singleton pattern is used to pass
	 * options back and forth between the Options class and the business logic class, via
	 * static methods.  This overcomes some of the limitations of working with a
	 * procedural framework.
	 *
	 * The singleton pattern isn't necessary for the rest of the Options class --
	 * rendering settings pages, etc.
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 *
	 * @return Mint_AB_Testing_Options
	 */
	public static function instance()
	{
		static $_instance = null;

		if ( is_null($_instance) ) {
			$class = __CLASS__;
			$_instance = new $class();
		}

		return $_instance;
	}

	/**
	 * Merge the saved options with the defaults
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public static function setup_options() {
		self::$options = array_merge(self::$options_defaults, get_option( self::$option_name, array() ));
	}

	/**
	 * Add the admin menu page
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public function admin_menu() {
		add_theme_page( __( 'A/B Testing Configuration', self::$text_domain ), __( 'A/B Testing', self::$text_domain ), 'manage_options', self::$plugin_id, array(&$this, 'settings_page') );
	}

	/**
	 * Register the plugin settings with the WordPress settings API
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public function register_settings() {
		register_setting( $this->_option_group, self::$option_name, array(&$this, 'settings_section_validate_main') );

		add_settings_section(self::$plugin_id . '-main', __( 'Main Settings', self::$text_domain ), array(&$this, 'settings_section_description_main'), self::$plugin_id);

		add_settings_field($this->_option_group . '-endpoint', __( '"B" Theme URL Endpoint', self::$text_domain ), array(&$this, 'settings_field_endpoint'), self::$plugin_id, self::$plugin_id . '-main');

		add_settings_field($this->_option_group . '-alternate_theme', __( 'Theme Select', self::$text_domain ), array(&$this, 'settings_field_alternate_theme'), self::$plugin_id, self::$plugin_id . '-main');

		add_settings_field($this->_option_group . '-ratio', __( 'Ratio', self::$text_domain ), array(&$this, 'settings_field_ratio'), self::$plugin_id, self::$plugin_id . '-main');

		add_settings_field($this->_option_group . '-cookie_ttl', __( '"B" Theme TTL', self::$text_domain ), array(&$this, 'settings_field_cookie_ttl'), self::$plugin_id, self::$plugin_id . '-main');


		add_settings_field($this->_option_group . '-enable', __( 'Enable A/B Testing', self::$text_domain ), array(&$this, 'settings_field_enable'), self::$plugin_id, self::$plugin_id . '-main');
	}

	/**
	 * Output the description HTML for the "Main" settings section
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public function settings_section_description_main() {
	}

	/**
	 * Output enable/disable settings field(s)
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public function settings_field_enable() {
		$settings_field_name = 'enable';
		$enable = self::get_option($settings_field_name);
		$id = $this->_option_group . '-' . $settings_field_name;

		echo '<label><input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="yes" ' . checked( ( 'yes' === $enable ), true, false) . '/>&nbsp;' . __( 'On', self::$text_domain ) . '</label><br />';
		echo '<label><input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="no" ' . checked( ( 'no' === $enable ), true, false) . '/>&nbsp;' . __( 'Off', self::$text_domain ) . '</label><br />';
	}


	/**
	 * Output alternate theme select settings field
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public function settings_field_alternate_theme() {
		$settings_field_name = 'alternate_theme';
		$id = $this->_option_group . '-' . $settings_field_name;

		$alternate_theme = self::get_option($settings_field_name);
		$current_theme = get_current_theme();
		$themes = get_allowed_themes();
		unset($themes[$current_theme]);

		printf( __( '"A" Theme: %s<br />', self::$text_domain ), $current_theme );

		_e( '"B" Theme: ', self::$text_domain );
		if ( empty($themes) ) {
			_e( 'You have no other themes installed!', self::$text_domain );
			echo '<input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="hidden" value="" />';
		} else {
			echo '<select name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '">';
			echo '<option value="">' . __( 'Select one...', self::$text_domain ) . '</option>';
			foreach ( $themes as $name => $data ) {

				echo '<option value="' . $name . '"' . selected( ( $alternate_theme === $name ), true, false ) . '>' . $name . '</option>';
			}
			echo '</select>';
		}
	}

	/**
	 * Output A/B ratio settings field
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 *
	 * @todo Use jQuery UI slider
	 */
	public function settings_field_ratio() {
		$settings_field_name = 'ratio';
		$ratio = self::get_option($settings_field_name);
		$id = $this->_option_group . '-' . $settings_field_name;

		$field_output = '<input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="4" value="' . $ratio . '" />';
		printf( __( 'Show the "B" Theme %s%% of the time', self::$text_domain ), $field_output );
	}


	/**
	 * Output cookie TTL settings field
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public function settings_field_cookie_ttl() {
		$settings_field_name = 'cookie_ttl';
		$cookie_ttl = self::get_option($settings_field_name);
		$id = $this->_option_group . '-' . $settings_field_name;

		$field_output = '<input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="3" value="' . $cookie_ttl . '" />';
		printf( __( 'Visitors who see the "B" Theme will see it for %s days', self::$text_domain ), $field_output );
		echo '<br /><span class="description">' . __( 'Set to "0" to expire at the end of the browser session', self::$text_domain ) . '</span>';
	}

	/**
	 * Output the endpoint settings field
	 *
	 * @since 0.9.0.1 2011-11-13 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 */
	public function settings_field_endpoint() {
		$settings_field_name = 'endpoint';
		$endpoint = self::get_option($settings_field_name);
		$id = $this->_option_group . '-' . $settings_field_name;

		echo home_url() . '/<input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="text" size="4" value="' . $endpoint . '" />/';
		echo '<br /><span class="description">' . __( 'This identifies the alternate theme ("B" theme).	 Users who visit a URL with this at the end will see the "B" theme.', self::$text_domain ) . '</span>';
	}

	/**
	 * Validate the options being saved in the "Main" settings section
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 *
	 * @todo Error messages / warnings
	 * @todo Don't make this an all-in-one function
	 */
	public function settings_section_validate_main($options) {
		foreach ( $options as $key => $value ) {
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
					self::activate();
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
					if ( $value !== self::get_option('endpoint') ) {
						self::set_option('endpoint', $value);
						$this->add_endpoints();
						self::activate();
					}
					break;
				case 'used_endpoints':
					$value = explode(',', $value);
					break;
				default:
					// Do nothing
					break;
			}
			self::$options[$key] = $value;
		}

		return $options;
	}

	/**
	 * Output the settings page
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h2><?php _e('Mint A/B Testing', self::$text_domain ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( $this->_option_group );
				do_settings_sections( self::$plugin_id );
				?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes', self::$text_domain ) ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 *
	 *
	 * @since 0.9.0.1 2011-11-13 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 */
	public static function activate() {
		self::add_endpoints();
		global $wp_rewrite;
		$wp_rewrite->flush_rules(false);
	}


	/**
	 *
	 *
	 * @since 0.9.0.1 2011-11-13 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 */
	public static function deactivate() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules(false);
	}

	/**
	 *
	 *
	 * @since 0.9.0.1 2011-11-13 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 */
	public static function add_endpoints() {
		add_rewrite_endpoint( self::get_option('endpoint'), EP_ALL );
	}

	/**
	 *
	 *
	 * @since 0.9.0.1 2011-11-13 Gabriel Koen
	 * @version 0.9.0.1 2011-11-13 Gabriel Koen
	 */
	public function request( $query_vars ) {
		if ( isset( $query_vars[self::get_option('endpoint')] ) ) {
			$query_vars[self::get_option('endpoint')] = true;
		} else {
			$query_vars[self::get_option('endpoint')] = self::has_endpoint();
		}

		return $query_vars;
	}

	/**
	 *
	 *
	 * @since 0.9.0.1 2011-11-13 Gabriel Koen
	 * @version 0.9.0.2 2011-11-13 Gabriel Koen
	 *
	 * @todo There's gotta be a better way...  (bool)get_query_var(self::get_option('endpoint')) never works because I always have to parse the querystring.  Parsing the request URI seems like the wrong thing to do, but it appears to be a catch 22: if I load the theme after get_query_var is populated, then the "A" theme's template files get loaded with the "B" theme's stylesheet, if I check for the "B" theme endpoint early enough to tell WordPress to load the right template files, then get_query_var isn't populated.
	 */
	public function has_endpoint() {
			global $wp_query;
		if ( ! $this->_has_endpoint ) {
			global $wp_query;
			$this->_has_endpoint = false;
			if ( is_object($wp_query) ) {
				$this->_has_endpoint = (bool)get_query_var(self::get_option('endpoint'));
			} elseif ( '' === get_option('permalink_structure') ) {
				if ( isset($_GET[self::get_option('endpoint')]) ) {
					$this->_has_endpoint = ('true' === $_GET[self::get_option('endpoint')]) ? true : false;
				}
			} else {
				$endpoint = '/' . self::get_option('endpoint') . '/';
				if ( $endpoint === $_SERVER['REQUEST_URI'] || strpos($_SERVER['REQUEST_URI'], $endpoint) !== false ) {
					$this->_has_endpoint = true;
				}
			}
		}

		return $this->_has_endpoint;
	}

	/**
	 * Plugin option getter
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public static function get_option($option_key = '') {
		if ( empty(self::$options) ) {
			self::setup_options();
		}

		if ( isset(self::$options[$option_key]) ) {
			return self::$options[$option_key];
		}

		return null;
	}

	/**
	 * Plugin option setter
	 *
	 * @since 0.9.0.0 2011-11-05 Gabriel Koen
	 * @version 0.9.0.0 2011-11-05 Gabriel Koen
	 */
	public static function set_option($option_key, $option_value = '') {
		self::$options[$option_key] = $option_value;
	}


}

// EOF