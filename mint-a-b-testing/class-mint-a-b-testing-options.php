<?php
/**
 * Handles get/set of plugin options and WordPress options page
 *
 * @since 0.9 2011-11-05 Gabriel Koen
 * @version 0.9 2011-11-05 Gabriel Koen
 */
class Mint_AB_Testing_Options
{

	/**
	 * String to use for the plugin name.  Used for generating class names, etc.
	 *
	 * @var string
	 */
	public static $plugin_id = 'mint-a-b-testing';

	/**
	 * String to use for the textdomain filename
	 *
	 * @var string
	 */
	public static $text_domain = 'mint-a-b-testing';

	/**
	 * Name of the option group for WordPress settings API
	 *
	 * @var string
	 */
	protected $_option_group = 'mint-a-b-testing-group';

	/**
	 * Name of the option for WordPress settings API
	 *
	 * @var string
	 */
	public static $option_name = 'mint_a_b_testing_options';

	/**
	 * Contains default optionss that get overridden in the constructor
	 *
	 * @var array
	 */
	public static $options_defaults = array(
		'enable' => 'no',
		'ratio' => 50,
		'alternate_theme' => 'Twenty Ten',
		'cookie_ttl' => 0,
		'use_javascript' => 'no',
	);

	/**
	 * Contains merged defaults + saved options
	 *
	 * @var array
	 */
	public static $options = array();

	/**
	 * Contains default optionss that get overridden in the constructor
	 *
	 * @var array
	public static $roles = array(
		'administrator' => 'switch_theme',
		'editor' => 'edit_others_posts',
		'author' => 'publish_posts',
		'contributor' => 'edit_posts',
		'subscriber' => 'read',
	);
	 */


	/**
	 * Hook into actions and filters here, along with any other global setup
	 * that needs to run when this plugin is invoked
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	private function __construct() {
		add_action( 'admin_menu', array(&$this,'admin_menu'), 10, 0 );
		add_action( 'admin_init', array(&$this,'register_settings'), 99, 0 );
	}

    /**
     * Returns Singleton instance of this plugin
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
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
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
     */
    public static function setup_options() {
		self::$options = array_merge(self::$options_defaults, get_option( self::$option_name, array() ));
    }

	/**
	 * Add the admin menu page
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function admin_menu() {
		add_options_page( __( 'A/B Testing Configuration', self::$text_domain ), __( 'A/B Testing', self::$text_domain ), 'manage_options', self::$plugin_id, array(&$this, 'settings_page') );
	}

	/**
	 * Register the plugin settings with the WordPress settings API
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function register_settings() {
		register_setting( $this->_option_group, self::$option_name, array(&$this, 'settings_section_validate_main') );

		add_settings_section(self::$plugin_id . '-main', __( 'Main Settings', self::$text_domain ), array(&$this, 'settings_section_description_main'), self::$plugin_id);

		add_settings_field($this->_option_group . '-alternate_theme', __( 'Theme Select', self::$text_domain ), array(&$this, 'settings_field_alternate_theme'), self::$plugin_id, self::$plugin_id . '-main');

		add_settings_field($this->_option_group . '-ratio', __( 'Ratio', self::$text_domain ), array(&$this, 'settings_field_ratio'), self::$plugin_id, self::$plugin_id . '-main');

		add_settings_field($this->_option_group . '-cookie_ttl', __( '"B" Theme TTL', self::$text_domain ), array(&$this, 'settings_field_cookie_ttl'), self::$plugin_id, self::$plugin_id . '-main');

		add_settings_field($this->_option_group . '-use_javascript', __( 'Use Javascript Theme Redirect', self::$text_domain ), array(&$this, 'settings_field_use_javascript'), self::$plugin_id, self::$plugin_id . '-main');

		add_settings_field($this->_option_group . '-enable', __( 'Enable A/B Testing', self::$text_domain ), array(&$this, 'settings_field_enable'), self::$plugin_id, self::$plugin_id . '-main');
	}

	/**
	 * Output the description HTML for the "Main" settings section
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function settings_section_description_main() {
	}

	/**
	 * Output keyword taxonomies settings field(s)
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function settings_field_enable() {
		$settings_field_name = 'enable';
		$enable = self::get_option($settings_field_name);
		$id = $this->_option_group . '-' . $settings_field_name;

		echo '<label><input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="yes" ' . checked( ( 'yes' === $enable ), true, false) . '/>&nbsp;' . __( 'On', self::$text_domain ) . '</label><br />';
		echo '<label><input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="no" ' . checked( ( 'no' === $enable ), true, false) . '/>&nbsp;' . __( 'Off', self::$text_domain ) . '</label><br />';
		//echo '<label><input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="preview" ' . checked( ( 'preview' === $enable ), true, false) . '/>&nbsp;' . __( 'Administrator Preview', self::$text_domain ) . '</label><br />';
	}

	/**
	 * Output keyword taxonomies settings field(s)
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function settings_field_use_javascript() {
		$settings_field_name = 'use_javascript';
		$use_javascript = self::get_option($settings_field_name);
		$id = $this->_option_group . '-' . $settings_field_name;

		echo '<label><input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="yes" ' . checked( ( 'yes' === $use_javascript ), true, false) . '/>&nbsp;' . __( 'Yes, determine the theme clientside', self::$text_domain ) . '</label>';
		echo ' <span class="description">' . __( 'This will prepend /v2/ when viewing the alternate theme, and redirect alternate theme visitors to that URL via javascript.', self::$text_domain ) . '</span><br />';
		echo '<label><input name="' . self::$option_name . '[' . $settings_field_name . ']" id="' . $id . '" type="radio" value="no" ' . checked( ( 'no' === $use_javascript ), true, false) . '/>&nbsp;' . __( 'No, determine the theme serverside', self::$text_domain ) . '</label>';
		echo ' <span class="description">' . __( 'You should select "No" if you are using any kind of caching, unless you know what you are doing.', self::$text_domain ) . '</span><br />';
	}

	/**
	 * Output sitemap filename settings field
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
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
	 * Output sitemap filename settings field
     *
	 * @since 0.9 2011-05-09 Gabriel Koen
	 * @version 0.9 2011-05-09 Gabriel Koen
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
	 * Output sitemap filename settings field
     *
	 * @since 0.9 2011-05-09 Gabriel Koen
	 * @version 0.9 2011-05-09 Gabriel Koen
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
	 * Validate the options being saved in the "Main" settings section
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 * @todo Error messages / warnings
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
					break;
				case 'use_javascript':
					$value = ('yes' === $value) ? $value : 'no';
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
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
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
	 * Plugin option getter
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
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
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public static function set_option($option_key, $option_value = '') {
		self::$options[$option_key] = $option_value;
	}


}

// EOF