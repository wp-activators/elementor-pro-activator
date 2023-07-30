<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Elementor Pro Activator
 * Plugin URI:        https://github.com/wp-activators/elementor-pro-activator
 * Description:       Elementor Pro Plugin Activator
 * Version:           1.0.0
 * Requires at least: 3.1
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/

defined( 'ABSPATH' ) || exit;

use ElementorPro\License\Admin;
use ElementorPro\License\API;

const ELEMENTOR_PRO_ACTIVATOR_NAME   = 'Elementor Pro Activator';
const ELEMENTOR_PRO_ACTIVATOR_DOMAIN = 'elementor-pro-activator';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
if (
	activator_admin_notice_ignored()
	|| activator_admin_notice_plugin_install( 'elementor-pro/elementor-pro.php', null, 'Elementor Pro', ELEMENTOR_PRO_ACTIVATOR_NAME, ELEMENTOR_PRO_ACTIVATOR_DOMAIN )
	|| activator_admin_notice_plugin_activate( 'elementor-pro/elementor-pro.php', ELEMENTOR_PRO_ACTIVATOR_NAME, ELEMENTOR_PRO_ACTIVATOR_DOMAIN )
) {
	return;
}
require_once WP_PLUGIN_DIR . '/elementor-pro/license/admin.php';
require_once WP_PLUGIN_DIR . '/elementor-pro/license/api.php';

function initElementorProActivator() {
	Admin::set_license_key( md5( 'free4all' ) );
	$license_data = [
		'success'          => true,
		'payment_id'       => '0123456789',
		'license_limit'    => 1000,
		'site_count'       => 1,
		'activations_left' => 1000,
		'expires'          => 'lifetime',
	];
	API::set_transient( Admin::LICENSE_DATA_OPTION_NAME, $license_data, '+1000 year' );
}

add_action( 'plugins_loaded', function () {
	if ( class_exists( Admin::class ) ) {
		initElementorProActivator();
	}
} );
