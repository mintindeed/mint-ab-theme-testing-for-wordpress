<?php
/**
 * Handles the generation of the A/B Testing
 *
 * @since 0.9.0.0
 * @version 0.9.0.8
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
	 * @version 0.9.0.8
	 */
	public function __construct() {
		if ( $this->get_can_view_alternate_theme() ) {
			add_filter( 'request', array( &$this, 'request' ) );

			$options = Mint_AB_Testing_Options::instance();
			if ( $options->get_option( 'javascript_redirect' ) ) {
				// Caching is enabled so use a javascript redirect
				add_action( 'wp_head', array( &$this, 'javascript_redirect' ), 0 );
			} else {
				if ( ! isset( $_COOKIE[Mint_AB_Testing_Options::cookie_name] ) ) {
					$this->set_theme_cookie();
				}

				add_action( 'template_redirect', array( &$this, 'serverside_redirect' ) );
			}


			if ( $this->get_use_alternate_theme() ) {
				add_filter( 'template', array( &$this, 'get_template' ) );
				add_filter( 'stylesheet', array( &$this, 'get_stylesheet' ) );
				$this->add_endpoint_filters();
				$this->remove_referrer_cookie();
			} else {
				// If we're not on the "B" theme, we should load the "B" theme's functions.php so that ajax calls, sidebars defined in the "B" theme, etc, will all work
				$this->load_alternate_functions();
			}
		} else {
			$this->delete_theme_cookie();
		}
	}


	/**
	 *
	 *
	 * @since 0.9.0.7
	 * @version 0.9.0.7
	 */
	public function remove_referrer_cookie() {
		$options = Mint_AB_Testing_Options::instance();
		if ( $options->get_option( 'javascript_redirect' ) ) {
			if ( class_exists('Pmc_Google_Analytics') ) {
				add_filter( 'pmc_google_analytics_pre_trackpageview', array(&$this, 'javascript_track_referrer') );
			} elseif ( class_exists('Yoast_GA_Plugin_Admin') ) {
				add_filter( 'option_Yoast_Google_Analytics', array(&$this, 'javascript_track_referrer') );
			}
		}

	}


	/**
	 * Output a javascript snippet for tracking the referrer.  Used when doing a
	 * javascript redirect.
	 *
	 * @since 0.9.0.7
	 * @version 0.9.0.7
	 */
	public function javascript_track_referrer( $content ) {

		// Check for the referrer cookie
		// If value is empty then tell Google Analytics to count the current domain as direct traffic, otherwise pass on the referrer value from the previous page
		$referrer_javascript = '

		var mint_ab_referrer_cookie_name = "' . esc_attr( Mint_AB_Testing_Options::referrer_cookie_name ) . '";
		var mint_ab_referrer_cookie_value = "";
		if (document.cookie.length > 0) {
			var mint_ab_cookie_start = document.cookie.indexOf(mint_ab_referrer_cookie_name + "=");
			if (mint_ab_cookie_start != -1) {
				mint_ab_cookie_start = mint_ab_cookie_start + mint_ab_referrer_cookie_name.length + 1;
				var mint_ab_cookie_end = document.cookie.indexOf(";", mint_ab_cookie_start);
				if (mint_ab_cookie_end == -1) {
					mint_ab_cookie_end = document.cookie.length;
				}
				mint_ab_referrer_cookie_value = decodeURIComponent(document.cookie.substring(mint_ab_cookie_start, mint_ab_cookie_end));
				if ( "" == mint_ab_referrer_cookie_value ) {
					_gaq.push(["_addIgnoredRef", "' . parse_url(home_url(), PHP_URL_HOST) . '"]);
				} else {
					_gaq.push(["_setReferrerOverride", mint_ab_referrer_cookie_value]);
				}
				document.cookie = mint_ab_referrer_cookie_name + "=; expires=Thu, 01-Jan-1970 00:00:01 GMT; path=' . SITECOOKIEPATH . '; domain=' . COOKIE_DOMAIN . '";
			}
		}
		';

		if ( class_exists('Yoast_GA_Plugin_Admin') ) {
			$content['customcode'] .= $referrer_javascript;
		} else {
			$content .= $referrer_javascript;
		}

		return $content;
	}


	/**
	 *
	 *
	 * @since 0.9.0.4
	 * @version 0.9.0.6
	 */
	public function add_endpoint_filters() {
		add_filter( 'the_content', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'get_the_excerpt', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'get_the_author_url', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'wp_nav_menu', array( &$this, 'rewrite_urls' ), 99 );

		add_filter( 'widget_text', array( &$this, 'rewrite_urls' ), 99 );

		add_filter( 'post_link', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'page_link', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'post_type_link', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'attachment_link', array( &$this, 'rewrite_urls' ), 99 );

		add_filter( 'get_comments_pagenum_link', array( &$this, 'fix_url_syntax' ), 99 );

		add_filter( 'category_link', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'tag_link', array( &$this, 'rewrite_urls' ), 99 );

		add_filter( 'day_link', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'month_link', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'year_link', array( &$this, 'rewrite_urls' ), 99 );

		add_filter( 'author_link', array( &$this, 'rewrite_urls' ), 99 );
		add_filter( 'comment_reply_link', array( &$this, 'rewrite_urls' ), 99 );
	}


	/**
	 * Fixes mangled URLS
	 * Example: http://wordpress.local/page-with-comments/?v02/comment-page-1/#comments
	 * Right now this only applies to get_comments_pagenum_link() and the 'get_comments_pagenum_link' filter; _wp_link_page() doesn't filter its output
	 *
	 * @see http://core.trac.wordpress.org/ticket/19493
	 */
	public function fix_url_syntax( $url ) {
		$options = Mint_AB_Testing_Options::instance();

		$mangled_url_check = '/?' . $options->get_option( 'endpoint' ) . '/';
		if ( false !== strpos( $url, $mangled_url_check ) ) {
			// We could get complicated here and parse the url and put it back together again, but let's keep it simple unless we have to.  Right now this fixes the one example we know of.
			$url = str_replace( $mangled_url_check, '/', $url );
			if ( false !== strpos( $url, '&' ) ) {
				$url = str_replace('&', '?' . $options->get_option( 'endpoint' ) . '&', $url);
			} else {
				$url = add_query_arg( $options->get_option( 'endpoint' ), '', $url );
			}
		}

		return $url;
	}


	/**
	 * Parse HTML for
	 *
	 * @todo This could be more efficient
	 *
	 * @since 0.9.0.4
	 * @version 0.9.0.6
	 */
	public function rewrite_urls( $content ) {
		// If this is a single URL, we don't need to do anything too complicated
		if ( preg_match( '~^' . preg_quote( home_url() ) . '~', $content ) ) {
			$content = $this->add_endpoint_to_url( $content );
			return $content;
		}

		// Get the relative URLs for wp-admin and wp-content, in case they've been changed
		$relative_content_url = str_replace( home_url(), '', content_url() );
		$relative_admin_url = str_replace( home_url(), '', get_admin_url() );

		// Subpatterns to exclude
		$exclude_file_extensions = '(?!.*\.([a-z0-9]{2,4}))';
		$exclude_wp_content = '(?!' . preg_quote($relative_content_url) . ')';
		$exclude_wp_admin = '(?!' . preg_quote($relative_admin_url) . ')';

		// Build the pattern to match
		$pattern = '~href=[\'"]?(/|' . preg_quote(home_url()) . ')' . $exclude_wp_content . $exclude_wp_admin . $exclude_file_extensions . '([^\'"<>\s]+)[\'"]?~';

		if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
			$count = count( $matches );

			for ( $i=0; $i < $count; $i++ ) {
				$find = array_shift( $matches[$i] );

				// Short-circuit on feed urls.  Arguably this could be in the
				// regex pattern.
				if ( strpos( $find, '/feed/' ) !== false ) {
					continue;
				}

				$replace_base = implode( $matches[$i] );

				$replace = $this->add_endpoint_to_url( $replace_base );

				// Create a new replace based on the href pattern in $find
				$replace = str_replace( $replace_base, $replace, $find );

				// Replace the href in $content
				$content = str_replace( $find, $replace, $content );
			}
		}

		return $content;
	}


	/**
	 * Add endpoint to a single URL
	 *
	 * @todo This could be more efficient
	 *
	 * @since 0.9.0.4
	 * @version 0.9.0.6
	 */
	public function add_endpoint_to_url( $url ) {
		$options = Mint_AB_Testing_Options::instance();

		$url = add_query_arg( $options->get_option( 'endpoint' ), '', $url );

		return $url;
	}


	/**
	 * Remove endpoint from a single URL
	 *
	 * @since 0.9.0.4
	 * @version 0.9.0.6
	 */
	public function remove_endpoint_from_url( $url ) {
		$options = Mint_AB_Testing_Options::instance();

		$url = remove_query_arg( $options->get_option( 'endpoint' ), $url );

		return $url;
	}


	/**
	 * Determine if the redirect is necessary, and then perform the redirect.
	 *
	 * Note the serverside redirect and javascript redirect methods are slightly different
	 * in syntax; the javascript method of has_cookie() is different than the php class
	 * method has_cookie() for example.
	 *
	 * @since 0.9.0.1
	 * @version 0.9.0.6
	 */
	public function serverside_redirect() {
		if ( $this->get_use_alternate_theme() && false === $this->has_endpoint() ) {
			$alternate_theme_uri = $this->add_endpoint_to_url( $_SERVER['REQUEST_URI'] );

			wp_safe_redirect( $alternate_theme_uri );

			die();
		} elseif ( $this->has_endpoint() && ! isset( $_COOKIE[Mint_AB_Testing_Options::cookie_name] ) ) {
			$this->set_theme_cookie();
		}
	}


	/**
	 * Output javascript in the header to test for alternate theme use and redirect to the
	 * alternate theme, if necessary.
	 *
	 * Note the serverside redirect and javascript redirect methods are slightly different
	 * in syntax; the javascript method of has_cookie() is different than the php class
	 * method has_cookie() for example.
	 *
	 * @since 0.9.0.3
	 * @version 0.9.0.7
	 */
	public function javascript_redirect() {
		$options = Mint_AB_Testing_Options::instance();

		?>
		<script type="text/javascript">
		//<![CDATA[
		var mint_ab_test = {
			referrer_cookie_name: "<?php echo Mint_AB_Testing_Options::referrer_cookie_name; ?>",
			cookie_name: "<?php echo Mint_AB_Testing_Options::cookie_name; ?>",
			endpoint: "<?php echo $options->get_option( 'endpoint' ); ?>",
			_has_endpoint: null,
			_is_valid_entrypoint: <?php echo ($this->is_valid_entrypoint() ? 'true' : 'false'); ?>,

			run: function() {
				// If not a valid entry point, bail early
				if ( ! this._is_valid_entrypoint ) {
					if ( false === this.has_cookie() ) {
						this.set_cookie( false, <?php echo $options->get_option( 'cookie_ttl' ); ?> );
					}
					return;
				}

				if ( false == this.has_endpoint() && this.use_alternate_theme() ) {
					<?php
					// Set the referrer cookie if doing a javascript redirect
					if ( $options->get_option( 'javascript_redirect' ) && ( class_exists('Pmc_Google_Analytics') ||  class_exists('Yoast_GA_Plugin_Admin') ) ) {
						?>
						this.set_referrer_cookie();
						<?php
					}
					?>
					this.do_redirect();
				} else if ( this.has_endpoint() && false === this.has_cookie() ) {
					// If the user landed on "B" theme, keep them there
					this.set_cookie( true, <?php echo $options->get_option( 'cookie_ttl' ); ?> );
				}
			},

			set_referrer_cookie: function() {
				var referrer_value = document.referrer;
				if ( referrer_value.indexOf("<?php echo site_url(); ?>") > -1 ) {
					referrer_value = "";
				}
				document.cookie = this.referrer_cookie_name + "=" + encodeURIComponent(referrer_value) + "; path=<?php echo SITECOOKIEPATH; ?>; domain=<?php echo COOKIE_DOMAIN; ?>";
			},

			do_redirect: function() {
				var params = document.location.search.substr(1).split("&");

				if ( "" == params ) {
					document.location.search = "?" + this.endpoint;
					return;
				}

				params[params.length] = [this.endpoint];

				document.location.search = params.join("&");
			},

			has_endpoint: function() {
				if ( null == this._has_endpoint ) {
					// Check for querystring param
					var regex = new RegExp("[\\?&]" + this.endpoint + "(|([\=\?#&].*))$");

					this._has_endpoint = regex.test(window.location.href);
				}

				return this._has_endpoint;
			},

			has_cookie: function() {
				if ( document.cookie.length > 0 && document.cookie.indexOf( this.cookie_name + "=" ) > -1 ) {
					return true;
				}

				return false;
			},

			use_alternate_theme: function() {
				// If there's a cookie set, we don't need to do any logic here, just use
				// the cookie value
				if ( document.cookie.length > 0 ) {
					if ( document.cookie.indexOf( this.cookie_name + "=true" ) > -1 ) {
						return true;
					} else if ( document.cookie.indexOf( this.cookie_name + "=false" ) > -1 ) {
						return false;
					}
				}

				var use_alternate_theme = false;

				if ( false == this.has_endpoint() ) {
					if ( Math.floor( Math.random()*101 ) < <?php echo $options->get_option( 'ratio' ); ?> ) {
						use_alternate_theme = true;
					}
				}

				this.set_cookie( use_alternate_theme, <?php echo $options->get_option( 'cookie_ttl' ); ?> );

				return use_alternate_theme;

			},

			set_cookie: function( value, expiry ) {
				var expires = "";

				if ( null != expiry && ! isNaN( expiry ) && expiry > 0 ) {
					var expiry_date=new Date();
					expiry_date.setDate( expiry_date.getDate() + expiry );
					expires = "; expires=" + expiry_date.toGMTString();
				}

				document.cookie = this.cookie_name + "=" + value + expires + "; path=<?php echo SITECOOKIEPATH; ?>; domain=<?php echo COOKIE_DOMAIN; ?>";
			},
		}

		mint_ab_test.run();

		//]]>
		</script>
		<?php
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 */
	public function get_can_view_alternate_theme() {
		if ( is_null( $this->_can_view_alternate_theme ) ) {
			$this->_can_view_alternate_theme = false;

			$options = Mint_AB_Testing_Options::instance();

			if ( 'yes' === $options->get_option( 'enable' ) ) {
				$this->_can_view_alternate_theme = true;
			}
		}

		return $this->_can_view_alternate_theme;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 */
	public function get_use_alternate_theme() {
		if ( is_null( $this->_use_alternate_theme ) ) {
			$this->_use_alternate_theme = false;

			$options = Mint_AB_Testing_Options::instance();

			$conditions_met = ( $this->has_endpoint() && ( $this->has_cookie() || $this->won_lottery() ) );
			if ( $this->has_endpoint() || $conditions_met ) {
				$alternate_theme = get_theme( $options->get_option( 'alternate_theme' ) );

				if ( ! is_null( $alternate_theme ) ) {
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
	 * @since 0.9.0.7
	 * @version 0.9.0.7
	 */
	public function is_valid_entrypoint() {
		// Never show on previews
		if ( is_preview() ) {
			return false;
		}

		$options = Mint_AB_Testing_Options::instance();
		$entrypoints = $options->get_option( 'entrypoints' );
		foreach ( $entrypoints as $entrypoint => $enabled ) {
			switch ( $entrypoint ) {
				case 'home':
					if ( $enabled && is_home() ) {
						return true;
					}
					break;

				case 'singular':
					if ( $enabled && is_singular() ) {
						return true;
					}
					break;

				case 'archive':
					if ( $enabled && is_archive() ) {
						return true;
					}
					break;

				case 'search':
					if ( $enabled && is_search() ) {
						return true;
					}
					break;

				case '404':
					if ( $enabled && is_404() ) {
						return true;
					}
					break;

				default:
					// No default, has to be specified above
					break;
			}
		}

		// If none of the conditions above took hold, return false because we're not at a specified entrypoint
		return false;

	}


	/**
	 *
	 *
	 * @since 0.9.0.1
	 * @version 0.9.0.6
	 */
	public function has_endpoint() {
		if ( false === $this->_has_endpoint ) {
			$options = Mint_AB_Testing_Options::instance();

			$this->_has_endpoint = ( isset( $_GET[$options->get_option( 'endpoint' )] ) ) ? true : false;
		}

		return $this->_has_endpoint;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 */
	public function has_cookie() {
		if ( isset( $_COOKIE[Mint_AB_Testing_Options::cookie_name] ) && 'true' === $_COOKIE[Mint_AB_Testing_Options::cookie_name] ) {
			return true;
		}

		return false;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.6
	 */
	public function won_lottery() {
		$options = Mint_AB_Testing_Options::instance();
		if ( ! isset( $_COOKIE[Mint_AB_Testing_Options::cookie_name] ) && rand( 0, 100 ) < $options->get_option( 'ratio' ) ) {
			return true;
		}

		return false;
	}


	/**
	 *
	 *
	 * @since 0.9.0.0
	 * @version 0.9.0.7
	 */
	public function set_theme_cookie() {
		// If the user doesn't land on a valid entrypoint, keep them on the "A" theme
		// If the user landed on "B" theme, keep them there
		if ( ! $this->is_valid_entrypoint() ) {
			$cookie_value = 'false';
		} elseif ( $this->has_endpoint() ) {
			$cookie_value = 'true';
		} else {
			$cookie_value = ( $this->get_use_alternate_theme() ) ? 'true' : 'false';
		}

		$options = Mint_AB_Testing_Options::instance();

		$cookie_expiry = $options->get_option( 'cookie_ttl' );

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
	 * Load alternate theme's functions.php so that ajax calls, sidebars defined in the
	 * "B" theme, etc, will all work.  This works slightly differently than expected if
	 * you are loading a child theme: normally a child theme's functions.php is loaded
	 * before the parent theme's, but we are forced to load it afterwards here because we
	 * don't have access to any actions before "after_setup_theme" on VIP.
	 *
	 * @since 0.9.0.8
	 * @version 0.9.0.8
	 */
	public function load_alternate_functions() {
		$options = Mint_AB_Testing_Options::instance();
		$alternate_theme = get_theme( $options->get_option( 'alternate_theme' ) );
		$alternate_theme_functions_path = $alternate_theme['Stylesheet Dir'] . '/functions.php';
		if ( file_exists( $alternate_theme_functions_path ) ) {
			include_once $alternate_theme_functions_path;
		}
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
	 * @version 0.9.0.6
	 */
	public function request( $query_vars ) {
		$options = Mint_AB_Testing_Options::instance();

		if ( isset( $query_vars[$options->get_option( 'endpoint' )] ) ) {
			$query_vars[$options->get_option( 'endpoint' )] = true;
		} else {
			$query_vars[$options->get_option( 'endpoint' )] = $this->has_endpoint();
		}

		return $query_vars;
	}

}

// EOF