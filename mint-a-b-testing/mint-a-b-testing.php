<?php
/*
Plugin Name: Mint A/B Theme Testing for WordPress
Plugin URI: http://gabrielkoen.com/
Description: Generates a A/B Testing on the fly.
Version: 0.9.0.3
Author: Gabriel Koen
Plugin URI: http://gabrielkoen.com/
License: GPLv2
*/

// Plugin is not needed when doing ajax calls
if ( !defined('DOING_AJAX') || !DOING_AJAX ) {
	include_once __DIR__ . '/class-mint-a-b-testing-options.php';
	add_action( 'plugins_loaded', 'mint_a_b_testing_options_loader', 10 );

	include_once __DIR__ . '/class-mint-a-b-testing.php';
	add_action( 'plugins_loaded', 'mint_a_b_testing_loader', 11 );

	include_once __DIR__ . '/class-mint-a-b-testing-admin.php';
	add_action( 'plugins_loaded', 'mint_a_b_testing_admin_loader', 11 );

	register_activation_hook( __FILE__, 'mint_a_b_testing_activate' );
	register_activation_hook( __FILE__, 'mint_a_b_testing_deactivate' );
}


/**
 * For loading Mint_AB_Testing via WordPress action
 *
 * @since 0.9.0.0
 * @version 0.9.0.3
 */
function mint_a_b_testing_loader() {
	if ( is_admin() ) {
		return;
	}

	new Mint_AB_Testing();
}


/**
 * For loading Mint_AB_Testing_Admin via WordPress action
 *
 * @since 0.9.0.3
 * @version 0.9.0.3
 */
function mint_a_b_testing_admin_loader() {
	if ( is_admin() ) {
		new Mint_AB_Testing_Admin();
	}
}


/**
 * For loading Mint_AB_Testing_Options via WordPress action
 *
 * @since 0.9.0.0
 * @version 0.9.0.3
 */
function mint_a_b_testing_options_loader() {
	Mint_AB_Testing_Options::instance();
}


/**
 * Run the activation function
 *
 * @since 0.9.0.3
 * @version 0.9.0.3
 */
function mint_a_b_testing_activate() {
	$Mint_AB_Testing_Admin = new Mint_AB_Testing_Admin();

	$Mint_AB_Testing_Admin->activate();
}


/**
 * Run the deactivation function
 *
 * @since 0.9.0.3
 * @version 0.9.0.3
 */
function mint_a_b_testing_deactivate() {
	$Mint_AB_Testing_Admin = new Mint_AB_Testing_Admin();

	$Mint_AB_Testing_Admin->deactivate();
}

// EOF