<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Elementor Pro Activ@tor
 * Plugin URI:        https://bit.ly/elm-act
 * Description:       Elementor Pro Plugin Activ@tor
 * Version:           1.4.0
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Author:            moh@medhk2
 * Author URI:        https://bit.ly/medhk2
 **/
defined( 'ABSPATH' ) || exit;

use ElementorPro\License\Admin;
use ElementorPro\License\API;

$PLUGIN_NAME   = 'Elementor Pro Activ@tor';
$PLUGIN_DOMAIN = 'elementor-pro-activ@tor';
extract( require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php' );
if (
	$admin_notice_ignored()
	|| $admin_notice_plugin_install( 'elementor-pro/elementor-pro.php', null, 'Elementor Pro', $PLUGIN_NAME, $PLUGIN_DOMAIN )
	|| $admin_notice_plugin_activate( 'elementor-pro/elementor-pro.php', $PLUGIN_NAME, $PLUGIN_DOMAIN )
) {
	return;
}
require_once WP_PLUGIN_DIR . '/elementor-pro/license/admin.php';
require_once WP_PLUGIN_DIR . '/elementor-pro/license/api.php';
$license_data = [
	'success'          => true,
	'payment_id'       => '0123456789',
	'license_limit'    => 1000,
	'site_count'       => 1,
	'activations_left' => 1000,
	'expires'          => 'lifetime',
];
add_action( 'plugins_loaded', function () use ( $license_data ) {
	if ( class_exists( Admin::class ) ) {
		Admin::set_license_key( md5( 'free4all' ) );
	}
	if ( class_exists( API::class ) ) {
		API::set_transient( Admin::LICENSE_DATA_OPTION_NAME, $license_data, '+1000 year' );
	}
} );
add_filter( 'pre_http_request', function ( $pre, $parsed_args, $url ) use ( $license_data, $json_response ) {
	if ( class_exists( API::class ) ) {
		switch ( $url ) {
			case API::BASE_URL . 'license/validate':
				return $json_response( $license_data );
		}
	}

	return $pre;
}, 99, 3 );

