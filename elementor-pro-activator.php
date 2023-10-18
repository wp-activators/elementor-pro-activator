<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Elementor Pro Activator
 * Plugin URI:        https://github.com/wp-activators/elementor-pro-activator
 * Description:       Elementor Pro Plugin Activator
 * Version:           1.3.0
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/
defined( 'ABSPATH' ) || exit;

use ElementorPro\License\Admin;
use ElementorPro\License\API;

$ELEMENTOR_PRO_ACTIVATOR_NAME   = 'Elementor Pro Activator';
$ELEMENTOR_PRO_ACTIVATOR_DOMAIN = 'elementor-pro-activator';
$functions                      = require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
extract( $functions );
if (
	$activator_admin_notice_ignored()
	|| $activator_admin_notice_plugin_install( 'elementor-pro/elementor-pro.php', null, 'Elementor Pro', $ELEMENTOR_PRO_ACTIVATOR_NAME, $ELEMENTOR_PRO_ACTIVATOR_DOMAIN )
	|| $activator_admin_notice_plugin_activate( 'elementor-pro/elementor-pro.php', $ELEMENTOR_PRO_ACTIVATOR_NAME, $ELEMENTOR_PRO_ACTIVATOR_DOMAIN )
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
add_filter( 'pre_http_request', function ( $pre, $parsed_args, $url ) use ( $license_data, $activator_json_response ) {
	if ( class_exists( API::class ) ) {
		switch ( $url ) {
			case API::BASE_URL . 'license/validate':
				return $activator_json_response( $license_data );
		}
	}

	return $pre;
}, 99, 3 );

