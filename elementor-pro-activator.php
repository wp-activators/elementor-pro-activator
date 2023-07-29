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
const ELEMENTOR_PRO_ACTIVATOR_NAME = 'Elementor Pro Activator';
use ElementorPro\License\Admin;
use ElementorPro\License\API;

require_once( ABSPATH . 'wp-includes/pluggable.php' );
require_once( ABSPATH . 'wp-admin/includes/screen.php' );
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! function_exists( 'is_plugin_installed' ) ) {
	function is_plugin_installed( $plugin ) {
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $plugin ] );
	}
}
$screen = get_current_screen();
if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
	return;
}
if ( ! is_plugin_installed( 'elementor-pro/elementor-pro.php' ) ) {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}
	$message = '<h3>' . esc_html__( ELEMENTOR_PRO_ACTIVATOR_NAME . ' plugin requires installing the Elementor Pro plugin', 'elementor-pro-activator' ) . '</h3>';
	$message .= '<p>' . esc_html__( 'Install and activate the Elementor Pro plugin to access all the ' . ELEMENTOR_PRO_ACTIVATOR_NAME . ' features.', 'elementor-pro-activator' ) . '</p>';
	add_action( 'admin_notices', function () use ( $message ) {
		?>
        <div class="notice notice-error">
        <p><?= $message ?></p>
        </div><?php
	} );

	return;
} elseif ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$plugin_file     = 'elementor-pro/elementor-pro.php';
	$plugin_data     = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
	$activate_action = sprintf(
		'<a href="%s" id="activate-%s" class=button-primary aria-label="%s">%s</a>',
		wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin_file ) . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'activate-plugin_' . $plugin_file ),
		esc_attr( 'elementor-pro' ),
		/* translators: %s: Plugin name. */
		esc_attr( sprintf( _x( 'Activate %s', 'plugin' ), $plugin_data['Name'] ) ),
		__( 'Activate Now' )
	);
	$message         = '<h3>' . esc_html__( "You're not using {$plugin_data['Name']} plugin yet!", 'elementor-pro-activator' ) . '</h3>';
	$message         .= '<p>' . esc_html__( "Activate the {$plugin_data['Name']} plugin to start using all of " . ELEMENTOR_PRO_ACTIVATOR_NAME . ' plugin’s features.', 'elementor-pro-activator' ) . '</p>';
	$message         .= '<p>' . $activate_action . '</p>';
	add_action( 'admin_notices', function () use ( $message ) {
		?>
        <div class="notice notice-warning">
        <p><?= $message ?></p>
        </div><?php
	} );

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
