<?php
/**
 * Handles the generation of the A/B Testing
 *
 * @since 0.9 2011-11-05 Gabriel Koen
 * @version 0.9 2011-06-27 Satyanarayan Verma
 */
class Mint_AB_Testing {
	/**
	 *
	 *
	 * @var null|string
	 */
	protected $_can_view_alternate_theme = null;

	/**
	 *
	 *
	 * @var null|string
	 */
	protected $_use_alternate_theme = null;

	/**
	 *
	 *
	 * @var null|string
	 */
	protected $_theme_template = null;

	/**
	 *
	 *
	 * @var null|string
	 */
	protected $_theme_stylesheet = null;

	/**
	 * Hook into actions and filters here, along with any other global setup
	 * that needs to run when this plugin is invoked
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function __construct() {
		$options = Mint_AB_Testing_Options::instance();

		if ( $this->get_can_view_alternate_theme() ) {
			if ( 'yes' === $options::get_option('use_javascript') ) {
				add_action('wp_head', array(&$this, 'theme_redirect', 0);
			}

			// If not using JS theme redirect, and no alternate theme cookie,
			// then set the alternate theme cookie (this also does the RNG to
			// determine whether to use the alternate theme).
			if ( 'no' === $options::get_option('use_javascript') && ! isset($_COOKIE['mint_alternate_theme_' . COOKIEHASH]) ) {
				$this->set_theme_cookie();
			}

			if ( $this->get_use_alternate_theme() ) {
				add_filter( 'template', array(&$this, 'get_template') );
				add_filter( 'stylesheet', array(&$this, 'get_stylesheet') );
			}
		}
	}

	/**
	 *
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function get_can_view_alternate_theme() {
		if ( is_null($this->_can_view_alternate_theme) ) {
			$this->_can_view_alternate_theme = false;
			$options = Mint_AB_Testing_Options::instance();
			if ( 'yes' === $options::get_option('enable') ) {
				/*
				$preview_for = $options::get_option('preview_for');
				if ( ! empty( $preview_for ) ) {
					global $user;
					user_can( $user, $options::$roles[$role] );
				}
				*/
				$this->_can_view_alternate_theme = true;
			}
		}

		return $this->_can_view_alternate_theme;
	}

	/**
	 *
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function get_use_alternate_theme() {
		if ( is_null($this->_use_alternate_theme) ) {
			$this->_use_alternate_theme = false;
			$options = Mint_AB_Testing_Options::instance();
			// If not using javascript redirect & has the alternate theme cookie set
			// OR check vs. ratio
			if ( ( 'no' === $options::get_option('use_javascript') && isset($_COOKIE['mint_alternate_theme_' . COOKIEHASH]) && 'true' === $_COOKIE['mint_alternate_theme_' . COOKIEHASH] )
				|| ( ! isset($_COOKIE['mint_alternate_theme_' . COOKIEHASH]) && rand(0, 100) <= $options::get_option('ratio') ) ) {
				$alternate_theme = get_theme( $options::get_option('alternate_theme') );
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
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function set_theme_cookie() {
		$cookie_value = ($this->get_use_alternate_theme()) ? 'true' : 'false';

		$options = Mint_AB_Testing_Options::instance();
		$cookie_expiry = $options::get_option('cookie_ttl');
		if ( $cookie_expiry > 0 ) {
			$cookie_expiry = time() + $cookie_expiry;
		}

		setcookie( 'mint_alternate_theme_' . COOKIEHASH, $cookie_value, $cookie_expiry, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 *
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function delete_theme_cookie() {
		setcookie( 'mint_alternate_theme_' . COOKIEHASH, 'false', 266165580, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 *
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function get_template( $template ) {
		$template = $this->_theme_template;
		return $template;
	}

	/**
	 *
     *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function get_stylesheet( $stylesheet ) {
		$stylesheet = $this->_theme_stylesheet;
		return $stylesheet;
	}

	/**
	 *
	 *
	 * @since 0.9 2011-11-05 Gabriel Koen
	 * @version 0.9 2011-11-05 Gabriel Koen
	 */
	public function theme_redirect() {
	}

}

// EOF