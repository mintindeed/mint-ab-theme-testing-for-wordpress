<?php
/**
 * Handles the generation of the A/B Testing
 *
 * @since 0.9.0.0
 * @version 0.9.0.3
 */
class Mint_AB_Testing
{

	/**
	 *
	 *
	 * @since 0.9.0.2
	 * @version 0.9.0.3
	 *
	 * @var bool
	 */
	protected $_has_endpoint = false;

	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 *
	 * @var null|string
	 */
	protected $_can_view_alternate_theme = null;

	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 *
	 * @var null|string
	 */
	protected $_use_alternate_theme = null;

	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 *
	 * @var null|string
	 */
	protected $_theme_template = null;

	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 *
	 * @var null|string
	 */
	protected $_theme_stylesheet = null;

	/**
	 * Hook into actions and filters here, along with any other global setup
	 * that needs to run when this plugin is invoked
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function __construct() {
		if ( $this->get_can_view_alternate_theme() ) {
			add_filter( 'request', array(&$this, 'request') );

			if ( ! isset($_COOKIE[Mint_AB_Testing_Options::cookie_name]) ) {
				$this->set_theme_cookie();
			}

			if ( $this->get_use_alternate_theme() ) {
				add_filter( 'template', array(&$this, 'get_template') );
				add_filter( 'stylesheet', array(&$this, 'get_stylesheet') );

				add_action( 'template_redirect', array(&$this, 'redirect') );
			}
		} else {
			$this->delete_theme_cookie();
		}
	}


	/**
	 *
	 *
	 * @since 0.9.0.1
	 * @version 0.9.0.3
	 */
	public function redirect() {
		if ( $this->get_use_alternate_theme() && false === $this->has_endpoint() ) {
			//if ( defined('WP_CACHE') && WP_CACHE ) {
				// Caching is enabled so use a javascript redirect
			//} else {
				$options = Mint_AB_Testing_Options::instance();
				$alternate_theme_uri = $_SERVER['REQUEST_URI'];
				if ( '' === get_option('permalink_structure') ) {
					$alternate_theme_uri = add_query_arg($options->get_option('endpoint'), 'true', $_SERVER['REQUEST_URI']);
				} elseif ( false === strpos($_SERVER['REQUEST_URI'], $options->get_option('endpoint')) ) {
					$raw_uri = parse_url($_SERVER['REQUEST_URI']);
					$alternate_theme_uri = $raw_uri['path'];
					$alternate_theme_uri = trailingslashit($alternate_theme_uri);
					$alternate_theme_uri .= $options->get_option('endpoint');

					if ( '/' === substr(get_option('permalink_structure'), -1) ) {
						$alternate_theme_uri = trailingslashit($alternate_theme_uri);
					}

					if ( isset($raw_uri['query']) ) {
						$alternate_theme_uri .= '?' . $raw_uri['query'];
					}
				}

				wp_safe_redirect( $alternate_theme_uri );

				die();
			//}
		}
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function get_can_view_alternate_theme() {
		if ( is_null($this->_can_view_alternate_theme) ) {
			$this->_can_view_alternate_theme = false;

			$options = Mint_AB_Testing_Options::instance();

			if ( 'yes' === $options->get_option('enable') ) {
				$this->_can_view_alternate_theme = true;
			}
		}

		return $this->_can_view_alternate_theme;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function get_use_alternate_theme() {
		if ( is_null($this->_use_alternate_theme) ) {
			$this->_use_alternate_theme = false;

			$options = Mint_AB_Testing_Options::instance();

			if ( $this->has_endpoint() || $this->has_cookie() || $this->won_lottery() ) {
				$alternate_theme = get_theme( $options->get_option('alternate_theme') );

				if ( ! is_null($alternate_theme) ) {
					$this->_use_alternate_theme = true;
					$this->_theme_template = $alternate_theme['Template'];
					$this->_theme_stylesheet = $alternate_theme['Stylesheet'];
				}
			}
		}

		return $this->_use_alternate_theme;
	}


	/**
	 *
	 *
	 * @since 0.9.0.1
	 * @version 0.9.0.3
	 *
	 * @todo There's gotta be a better way...  (bool)get_query_var(self::get_option('endpoint')) never works because I always have to parse the querystring.  Parsing the request URI seems like the wrong thing to do, but it appears to be a catch 22: if I load the theme after get_query_var is populated, then the "A" theme's template files get loaded with the "B" theme's stylesheet, if I check for the "B" theme endpoint early enough to tell WordPress to load the right template files, then get_query_var isn't populated.
	 */
	public function has_endpoint() {
		if ( false === $this->_has_endpoint ) {
			global $wp_query;

			$options = Mint_AB_Testing_Options::instance();

			$this->_has_endpoint = false;

			if ( is_object($wp_query) ) {
				$this->_has_endpoint = (bool)get_query_var($options->get_option('endpoint'));
			} elseif ( '' === get_option('permalink_structure') ) {
				if ( isset($_GET[$options->get_option('endpoint')]) ) {
					$this->_has_endpoint = ('true' === $_GET[$options->get_option('endpoint')]) ? true : false;
				}
			} else {
				$endpoint = '/' . $options->get_option('endpoint') . '/';

				if ( $endpoint === $_SERVER['REQUEST_URI'] || strpos($_SERVER['REQUEST_URI'], $endpoint) !== false ) {
					$this->_has_endpoint = true;
				}
			}
		}

		return $this->_has_endpoint;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function has_cookie() {
		if ( isset($_COOKIE[Mint_AB_Testing_Options::cookie_name]) && 'true' === $_COOKIE[Mint_AB_Testing_Options::cookie_name] ) {
			return true;
		}

		return false;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function won_lottery() {
		$options = Mint_AB_Testing_Options::instance();
		if ( ! isset($_COOKIE[Mint_AB_Testing_Options::cookie_name]) && rand(0, 100) <= $options->get_option('ratio') ) {
			return true;
		}

		return false;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function set_theme_cookie() {
		// If there's no cookie yet, and the user is visiting the alternate endpoint,
		// we can assume they want to be here.	That means they'll likely be switching
		// back and forth manually, like an admin viewing the A and B themes,
		// so we don't want to automatically redirect.
		if ( $this->has_endpoint() ) {
			$cookie_value = 'false';
		} else {
			$cookie_value = ($this->get_use_alternate_theme()) ? 'true' : 'false';
		}

		$options = Mint_AB_Testing_Options::instance();

		$cookie_expiry = $options->get_option('cookie_ttl');

		if ( $cookie_expiry > 0 ) {
			$cookie_expiry = time() + $cookie_expiry;
		}

		setcookie( Mint_AB_Testing_Options::cookie_name, $cookie_value, $cookie_expiry, COOKIEPATH, COOKIE_DOMAIN );
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function delete_theme_cookie() {
		setcookie( Mint_AB_Testing_Options::cookie_name, 'false', 266165580, COOKIEPATH, COOKIE_DOMAIN );
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function get_template( $template ) {
		$template = $this->_theme_template;

		return $template;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.3
	 */
	public function get_stylesheet( $stylesheet ) {
		$stylesheet = $this->_theme_stylesheet;

		return $stylesheet;
	}


	/**
	 *
	 *
	 * @since 0.9.0.1
	 * @version 0.9.0.3
	 */
	public function request( $query_vars ) {
		$options = Mint_AB_Testing_Options::instance();

		if ( isset( $query_vars[$options->get_option('endpoint')] ) ) {
			$query_vars[$options->get_option('endpoint')] = true;
		} else {
			$query_vars[$options->get_option('endpoint')] = $this->has_endpoint();
		}

		return $query_vars;
	}

}

// EOF