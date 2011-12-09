<?php
/**
 * Handles the generation of the A/B Testing
 *
 * @since 0.9.0.0
 * @version 0.9.0.4
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

			if ( defined('WP_CACHE') && WP_CACHE ) {
				// Caching is enabled so use a javascript redirect
				add_action( 'wp_head', array(&$this, 'javascript_redirect'), 0 );
			} else {
				if ( ! isset($_COOKIE[Mint_AB_Testing_Options::cookie_name]) ) {
					$this->set_theme_cookie();
				}

				add_action( 'template_redirect', array(&$this, 'serverside_redirect') );
			}


			if ( $this->get_use_alternate_theme() ) {
				add_filter( 'template', array(&$this, 'get_template') );
				add_filter( 'stylesheet', array(&$this, 'get_stylesheet') );
				$this->add_endpoint_filters();
			}
		} else {
			$this->delete_theme_cookie();
		}
	}


	/**
	 *
	 *
	 * @since 0.9.0.4
	 * @version 0.9.0.4
	 */
	public function add_endpoint_filters() {
		add_filter( 'the_content', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'get_the_excerpt', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'get_the_author_url', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'wp_nav_menu', array(&$this, 'rewrite_urls'), 99 );

		add_filter( 'widget_text', array(&$this, 'rewrite_urls'), 99 );

		add_filter( 'post_link', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'page_link', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'post_type_link', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'attachment_link', array(&$this, 'rewrite_urls'), 99 );

		add_filter( 'category_link', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'tag_link', array(&$this, 'rewrite_urls'), 99 );

		add_filter( 'day_link', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'month_link', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'year_link', array(&$this, 'rewrite_urls'), 99 );

		add_filter( 'author_link', array(&$this, 'rewrite_urls'), 99 );
		add_filter( 'comment_reply_link', array(&$this, 'rewrite_urls'), 99 );

		add_filter( 'get_pagenum_link', array(&$this, 'remove_endpoint_from_url', 99 );
	}


	/**
	 * Parse HTML for
	 *
	 * @todo This could be more efficient
	 *
	 * @since 0.9.0.4
	 * @version 0.9.0.4
	 */
	public function rewrite_urls( $content ) {
		// If this is a single URL, we don't need to do anything too complicated
		if ( preg_match('~^https?://~', $content) ) {
			$content = $this->add_endpoint_to_url($content);
			return $content;
		}

		// Get the relative URLs for wp-admin and wp-content, in case they've been changed
		$relative_content_url = str_replace(home_url(), '', content_url());
		$relative_admin_url = str_replace(home_url(), '', get_admin_url());

		// Subpatterns to exclude
		$exclude_file_extensions = '(?!.*\.([a-z0-9]{2,4}))';
		$exclude_wp_content = '(?!' . preg_quote($relative_content_url) . ')';
		$exclude_wp_admin = '(?!' . preg_quote($relative_admin_url) . ')';

		// Build the pattern to match
		$pattern = '~href=[\'"]?(/|' . preg_quote(home_url()) . ')' . $exclude_wp_content . $exclude_wp_admin . $exclude_file_extensions . '([^\'"<>\s]+)[\'"]?~';

		if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
			$count = count($matches);

			for ( $i=0; $i < $count; $i++ ) {
				$find = array_shift($matches[$i]);

				// Short-circuit on feed urls.  Arguably this could be in the
				// regex pattern.
				if ( strpos($find, '/feed/') !== false ) {
					continue;
				}

				$replace_base = implode( $matches[$i] );

				$replace = $this->add_endpoint_to_url( $replace_base );

				// Create a new replace based on the href pattern in $find
				$replace = str_replace($replace_base, $replace, $find);

				// Replace the href in $content
				$content = str_replace($find, $replace, $content);
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
	 * @version 0.9.0.4
	 */
	public function add_endpoint_to_url( $replace_base ) {
		$options = Mint_AB_Testing_Options::instance();

		$replace_parts = parse_url( $replace_base );

		$replace = '';

		if ( isset($replace_parts['scheme']) && isset($replace_parts['host']) ) {
			$replace = $replace_parts['scheme'] . '://' . $replace_parts['host'];
		}

		if ( isset($replace_parts['path']) ) {
			$replace .= $replace_parts['path'];
		}

		$replace = trailingslashit( $replace );

		if ( '' === get_option('permalink_structure') ) {
			if ( isset($replace_parts['query']) ) {
				$replace .= '?' . $replace_parts['query'];
			}

			$replace = add_query_arg( $options->get_option('endpoint'), 'true', $replace );
		} else {
			$replace .= $options->get_option('endpoint');
			$replace = trailingslashit( $replace );

			if ( isset($replace_parts['query']) ) {
				$replace .= '?' . $replace_parts['query'];
			}
		}

		if ( isset($replace_parts['fragment']) ) {
			$replace .= '#' . $replace_parts['fragment'];
		}

		return $replace;
	}


	/**
	 * Remove endpoint from a single URL
	 *
	 * @since 0.9.0.4
	 * @version 0.9.0.4
	 */
	public function remove_endpoint_from_url( $url ) {
		$options = Mint_AB_Testing_Options::instance();

		if ( '' === get_option('permalink_structure') ) {
			$url = remove_query_arg( $options->get_option('endpoint'), $url );
		} else {
			$url = str_replace('/' . $options->get_option('endpoint'), '', $url);
		}

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
	 * @version 0.9.0.4
	 */
	public function serverside_redirect() {
		if ( $this->get_use_alternate_theme() && false === $this->has_endpoint() ) {
			$alternate_theme_uri = $this->rewrite_urls($_SERVER['REQUEST_URI']);

			wp_safe_redirect( $alternate_theme_uri );

			die();
		} elseif ( $this->has_endpoint() && isset($_COOKIE[Mint_AB_Testing_Options::cookie_name]) ) {
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
	 * @version 0.9.0.4
	 */
	public function javascript_redirect() {
		$options = Mint_AB_Testing_Options::instance();

		?>
		<script type="text/javascript">
		//<![CDATA[
		var mint_ab_test = {
			cookie_name: "<?php echo Mint_AB_Testing_Options::cookie_name; ?>",
			endpoint: "<?php echo $options->get_option('endpoint'); ?>",
			_has_endpoint: null,

			run: function() {
				if ( false == this.has_endpoint() && this.use_alternate_theme() ) {
					this.do_redirect();
				} else if ( this.has_endpoint() && false === this.has_cookie() ) {
					// If the user landed on "B" theme, keep them there
					this.set_cookie( true, <?php echo $options->get_option('cookie_ttl'); ?> );
				}
			},

			do_redirect: function() {
				<?php
				if ( '' === get_option('permalink_structure') ) {
					?>

					var params = document.location.search.substr(1).split('&');

					if ( "" == params ) {
						document.location.search = "?" + this.endpoint;
						return;
					}

					params[params.length] = [this.endpoint];

					document.location.search = params.join("&");
					return;
					<?php
				} else {
					?>

					var current_location = window.location.pathname;

					<?php
					// Don't need to check if the path ends with a trailing slash because
					// WP will have redirected us before we even get here.
					// However, do need to check for a trailing slash so that we can
					// output the correct redirect path
					if ( '/' === substr(get_option('permalink_structure'), -1) ) {
						?>

						var new_location = current_location + this.endpoint + "/";
						<?php
					} else {
						// Permalink doesn't end with a trailing slash, so we'll add a
						// slash to the current location (so that we can append the
						// endpoint)
						// @todo Could put some logic here to be smarter about how the endpoint is added to URLs without a trailing slash
						?>

						var new_location = current_location + "/" + this.endpoint;
						<?php
					}
					?>

					window.parent.location.replace(new_location);
					<?php
				}
				?>
			},

			has_endpoint: function() {
				if ( null == this._has_endpoint ) {
					// Check for querystring
					var regex = new RegExp("[\\?&]" + this.endpoint + "(|([\=\?#].*))$");
					this._has_endpoint = regex.test(window.location.href);

					// If no querystring, check in URL
					if ( false == this._has_endpoint ) {
						var regex = new RegExp("\/" + this.endpoint + "\/(|([\?#].*))$");
						this._has_endpoint = regex.test(window.location.href);
					}
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
					if ( Math.floor(Math.random()*101) <= <?php echo $options->get_option('ratio'); ?> ) {
						use_alternate_theme = true;
					}
				}

				this.set_cookie( use_alternate_theme, <?php echo $options->get_option('cookie_ttl'); ?> );

				return use_alternate_theme;

			},

			set_cookie: function( value, expiry ) {
				var expires = "";

				if ( null != expiry && ! isNaN(expiry) && expiry > 0 ) {
					var expiry_date=new Date();
					expiry_date.setDate(expiry_date.getDate() + expiry);
					expires = "; expires=" + expiry_date.toGMTString();
				}

				document.cookie = this.cookie_name + "=" + value + expires + "; path=<?php echo COOKIEPATH; ?>; domain=<?php echo COOKIE_DOMAIN; ?>";
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
	 * @version 0.9.0.4
	 */
	public function get_use_alternate_theme() {
		if ( is_null($this->_use_alternate_theme) ) {
			$this->_use_alternate_theme = false;

			$options = Mint_AB_Testing_Options::instance();

			$conditions_met = ($this->has_endpoint() && ($this->has_cookie() || $this->won_lottery()));
			if ( $this->has_endpoint() || $conditions_met ) {
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
			} else {
				// First test if there's the querystring param
				if ( isset($_GET[$options->get_option('endpoint')]) ) {
					$this->_has_endpoint = ('true' === $_GET[$options->get_option('endpoint')]) ? true : false;
				}

				// If still false, check for presence in URL
				$endpoint = '/' . $options->get_option('endpoint') . '/';
				if ( ! $this->_has_endpoint && ( $endpoint === $_SERVER['REQUEST_URI'] || strpos($_SERVER['REQUEST_URI'], $endpoint) !== false ) ) {
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
	 * @version 0.9.0.4
	 */
	public function set_theme_cookie() {
		// If the user landed on "B" theme, keep them there
		if ( $this->has_endpoint() ) {
			$cookie_value = 'true';
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