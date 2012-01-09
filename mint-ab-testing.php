<?php
/*
Plugin Name: Mint A/B Theme Testing for WordPress
Plugin URI: https://github.com/mintindeed/mint-ab-theme-testing-for-wordpress
Description: Generates a A/B Testing on the fly.
Version: 0.9.0.6
Author: Gabriel Koen
Author URI: http://gabrielkoen.com/
License: GPLv2
*/

// Plugin is not needed when doing ajax calls
if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
	include_once __DIR__ . '/class-mint-ab-testing-options.php';
	add_action( 'plugins_loaded', 'mint_ab_testing_options_loader', 10 );

	include_once __DIR__ . '/class-mint-ab-testing.php';
	add_action( 'plugins_loaded', 'mint_ab_testing_loader', 11 );

	include_once __DIR__ . '/class-mint-ab-testing-admin.php';
	add_action( 'plugins_loaded', 'mint_ab_testing_admin_loader', 11 );
}


/**
 * For loading Mint_AB_Testing via WordPress action
 *
 * @since 0.9.0.0
 * @version 0.9.0.6
 */
function mint_ab_testing_loader() {
	if ( is_admin() ) {
		return;
	}

	new Mint_AB_Testing();
}


/**
 * For loading Mint_AB_Testing_Admin via WordPress action
 *
 * @since 0.9.0.3
 * @version 0.9.0.6
 */
function mint_ab_testing_admin_loader() {
	if ( is_admin() ) {
		new Mint_AB_Testing_Admin();
	}
}


/**
 * For loading Mint_AB_Testing_Options via WordPress action
 *
 * @since 0.9.0.0
 * @version 0.9.0.6
 */
function mint_ab_testing_options_loader() {
	Mint_AB_Testing_Options::instance();
}


// EOF