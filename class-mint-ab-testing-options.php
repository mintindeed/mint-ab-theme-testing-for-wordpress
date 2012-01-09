<?php
/**
 * Handles get/set of plugin options and WordPress options page
 *
 * @since 0.9.0.0
 * @version 0.9.0.6
 */
class Mint_AB_Testing_Options
{

	/**
	 * String to use for the plugin name.  Used for generating class names, etc.
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 *
	 * @constant string
	 */
	const plugin_id = 'mint-ab-testing';


	/**
	 *
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.6
	 *
	 * @constant string
	 */
	const cookie_name = 'mint_alternate_theme';


	/**
	 * Name of the option group for WordPress settings API
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 *
	 * @constant string
	 */
	const option_group = 'mint-ab-testing-group';


	/**
	 * Name of the option for WordPress settings API
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 *
	 * @constant string
	 */
	const option_name = 'mint_ab_testing_options';


	/**
	 * Contains default options that get overridden in the constructor
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 *
	 * @var array
	 */
	protected $_options_defaults = array(
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
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 * @var array
	 */
	protected $_options = array();


	/**
	 * Hook into actions and filters here, along with any other global setup
	 * that needs to run when this plugin is invoked
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 */
	public function __construct() {
		$this->_setup_options();
	}


	/**
	 * Merge the saved options with the defaults
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.6
	 */
	protected function _setup_options() {
		$this->_options = array_merge( $this->_options_defaults, get_option( self::option_name, array() ) );
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
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 *
	 * @return Mint_AB_Testing_Options
	 */
	public static function instance()
	{
		static $_instance = null;

		if ( is_null( $_instance ) ) {
			$class = __CLASS__;
			$_instance = new $class();
		}

		return $_instance;
	}


	/**
	 * Plugin option getter
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 */
	public function get_option( $option_key = '' ) {
		if ( isset( $this->_options[$option_key] ) ) {
			return $this->_options[$option_key];
		}

		return null;
	}


	/**
	 * Plugin option setter
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 */
	public function set_option( $option_key, $option_value = '' ) {
		$this->_options[$option_key] = $option_value;
	}

}

// EOF