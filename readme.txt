=== Mint A/B Theme Testing for WordPress ===
Contributors: mintindeed
Tags: a/b testing, alternate theme
Requires at least: 3.2
Tested up to: 3.3.1
Stable tag: 0.9.0.6

Do A/B testing for your WordPress site design or layout.

== Description ==

This plugin is for A/B testing your theme, and does not support A/B testing content.

= How it works =
* When a user is randomly selected to see the "B" theme, a cookie is set and they are redirected to the "B" version of a URL.
* The "A" URL looks like this: `http://example.com/2011/11/hello-world/`
* The "B" URL looks like this: `http://example.com/2011/11/hello-world/?v02`

= Technical notes =
* *Works with caching.*  Proxy caches and server caches are no problem.  If caching is enabled, the users will be redirected via javascript.
* *As simple and lightweight as possible.*  This plugin was written and designed for performance-sensitive sites that get millions of pageviews per month.

== Installation ==

1. Make sure you have an alternate theme for testing.  Normally this will be a copy of your current theme, plus some changes.  It will live in your `/wp-content/themes/` directory like any other theme.
1. *Important:* This plugin does not manage your widgets.  If you plan on having different widgets in your A/B themes, then you will need to create a new sidebar in _both_ themes and then only display that sidebar in your "B" theme.

== Frequently Asked Questions ==

= Where are the plugin settings? =

Appearance > A/B Testing

= Can I use something else besides "v02" to designate my alternate theme? =

Yes, this is configurable in the settings options.

= Do I have to use a querystring parameter, can I make the URL something like `http://example.com/2011/11/hello-world/v02/` or `http://example.com/v02/2011/11/hello-world/` instead? =

There is currently a bug in WordPress that prevents this.  [See #19493.](http://core.trac.wordpress.org/ticket/19493)

Prepending the "B" theme flag to the URL, e.g. `http://example.com/v02/2011/11/hello-world/`,

= Can I schedule the tests? =

Not directly.  The simplest way to manage your tests is to use the settings page.

All the options (on/off, "B" theme, etc) are stored in the WordPress options table.  You can write your own script to change the "B" theme, change the "B" theme URL, and enable/disable the testing.

= What happens if I disable A/B testing in the plugin settings, but someone still has a "B" theme cookie or visits a link to the "B" theme? =

They will see the "A" theme.  Even if A/B testing is disabled, the "B" theme URLs will still work -- they will just show the "A" theme.

Once the [issue with endpoints](http://core.trac.wordpress.org/ticket/19493) is sorted out, the "B" theme URLs will break if this plugin is disabled.  It was written to be very lightweight when A/B testing is disabled because of this.


== Screenshots ==

1. Settings
2. A/B test example

== Changelog ==

= 0.9.0.6 =
* Initial release.

== Upgrade Notice ==

= 0.9.0.6 =
N/A
