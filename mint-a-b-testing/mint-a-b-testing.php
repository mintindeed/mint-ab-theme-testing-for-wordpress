<?php
/*
Plugin Name: Mint A/B Theme Testing for WordPress
Plugin URI: http://gabrielkoen.com/
Description: Generates a A/B Testing on the fly.
Version: 0.9.0.2
Author: Gabriel Koen
Plugin URI: http://gabrielkoen.com/
License: GPLv2
*/

// Plugin is not needed when doing ajax calls
if ( !defined('DOING_AJAX') || !DOING_AJAX ) {
	// Plugin directory needs to be the same as the plugin filename
	$plugin_path = dirname( __FILE__ );

	include_once $plugin_path . '/class-mint-a-b-testing-options.php';
	add_action( 'plugins_loaded', 'mint_a_b_testing_options_loader', 10 );

	include_once $plugin_path . '/class-mint-a-b-testing.php';
	add_action( 'plugins_loaded', 'mint_a_b_testing_loader', 11 );

	register_activation_hook( __FILE__, array('Mint_AB_Testing_Options', 'activate') );
	register_activation_hook( __FILE__, array('Mint_AB_Testing_Options', 'deactivate') );
}

/**
 * For loading Mint_AB_Testing via WordPress action
 *
 * @since 0.9.0.0 2011-11-05 Gabriel Koen
 * @version 0.9.0.0 2011-11-05 Gabriel Koen
 */
function mint_a_b_testing_loader() {
	if ( is_admin() ) {
		return;
	}

	new Mint_AB_Testing();
}

/**
 * For loading Mint_AB_Testing_Options via WordPress action
 *
 * @since 0.9.0.0 2011-11-05 Gabriel Koen
 * @version 0.9.0.0 2011-11-05 Gabriel Koen
 */
function mint_a_b_testing_options_loader() {
	Mint_AB_Testing_Options::instance();
}

// EOF