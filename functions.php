<?php
/**
 * Requires at least: 3.1.0
 * Requires PHP:      7.1
 */
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! function_exists( 'wp_get_current_user' ) ) {
	require_once( ABSPATH . 'wp-includes/pluggable.php' );
}
if ( ! function_exists( 'is_plugin_installed' ) ) {
	function is_plugin_installed( $plugin ): bool {
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $plugin ] );
	}
}
if ( ! function_exists( 'activator_admin_notice_ignored' ) ) {
	function activator_admin_notice_ignored(): bool {
		global $pagenow;
		$action = $_REQUEST['action'] ?? '';

		return $pagenow == 'update.php' && in_array( $action, [ 'install-plugin', 'upload-plugin' ], true );
	}
}
if ( ! function_exists( 'activator_admin_notice_plugin_install' ) ) {
	function activator_admin_notice_plugin_install( string $plugin, ?string $wp_plugin_id, string $plugin_name, string $activator_name, string $domain ): bool {
		if ( ! is_plugin_installed( $plugin ) ) {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return true;
			}
			$install_url = wp_nonce_url( self_admin_url( "update.php?action=install-plugin&plugin={$wp_plugin_id}" ), "install-plugin_{$wp_plugin_id}" );
			$message     = '<h3>' . esc_html__( "{$activator_name} plugin requires installing the {$plugin_name} plugin", $domain ) . '</h3>';
			$message     .= '<p>' . __( "Install and activate the \"{$plugin_name}\" plugin to access all the <b>{$activator_name}</b> features.", $domain ) . '</p>';
			if ( $wp_plugin_id !== null ) {
				$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Now', $domain ) ) . '</p>';
			}
			add_action( 'admin_notices', function () use ( $message ) {
				?>
                <div class="notice notice-error">
                <p><?= $message ?></p>
                </div><?php
			} );

			return true;
		}

		return false;
	}
}
if ( ! function_exists( 'activator_admin_notice_plugin_activate' ) ) {
	function activator_admin_notice_plugin_activate( string $plugin, string $activator_name, string $domain ): bool {
		if ( ! is_plugin_active( $plugin ) ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return true;
			}
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			# sub str from the beginning of $plugin to the first '/'
			$plugin_id       = substr( $plugin, 0, strpos( $plugin, '/' ) );
			$activate_action = sprintf(
				'<a href="%s" id="activate-%s" class=button-primary aria-label="%s">%s</a>',
				wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin ) . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'activate-plugin_' . $plugin ),
				esc_attr( $plugin_id ),
				/* translators: %s: Plugin name. */
				esc_attr( sprintf( _x( 'Activate %s', 'plugin' ), $plugin_data['Name'] ) ),
				__( 'Activate Now' )
			);
			$message         = '<h3>' . esc_html__( "You're not using \"{$plugin_data['Name']}\" plugin yet!", $domain ) . '</h3>';
			$message         .= '<p>' . __( "Activate the \"{$plugin_data['Name']}\" plugin to start using all of <b>{$activator_name}</b> plugin’s features.", $domain ) . '</p>';
			$message         .= '<p>' . $activate_action . '</p>';
			add_action( 'admin_notices', function () use ( $message ) {
				?>
                <div class="notice notice-warning">
                <p><?= $message ?></p>
                </div><?php
			} );

			return true;
		}

		return false;
	}
}
if ( ! function_exists( 'activator_json_response' ) ) {
	function activator_json_response( $data ) {
		return [
			'response' => [ 'code' => 200, 'message' => 'OK' ],
			'body'     => json_encode( $data )
		];
	}
}
