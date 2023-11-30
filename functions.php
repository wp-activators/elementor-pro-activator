<?php
/**
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Version:           231129
 */
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! function_exists( 'wp_get_current_user' ) ) {
	require_once( ABSPATH . 'wp-includes/pluggable.php' );
}

/**
 * @param string $name
 * @param string $slug
 * @param callable $callback
 * @param int $priority
 *
 * @return void
 * @author mohamedhk2
 */
$plugins_filter = function ( string $name, string $slug, callable $callback, int $priority = 99 ) {
	$is_current = isset( $_REQUEST['plugin_status'] ) && $_REQUEST['plugin_status'] === $slug;
	add_filter( 'plugins_list', function ( $plugins ) use ( $name, $slug, $is_current, $callback ) {
		/**
		 * @see \WP_Plugins_List_Table::prepare_items
		 * @see \WP_Plugins_List_Table::get_sortable_columns
		 */
		global $status;
		if ( $is_current ) {
			$status = $slug;
		}
		$inj_plugins = array_filter( $plugins['all'], $callback );
		if ( ! empty( $inj_plugins ) ) {
			$plugins[ $slug ] = $inj_plugins;
		}

		return $plugins;
	}, $priority );
	add_filter( 'views_plugins', function ( $views ) use ( $name, $slug, $is_current ) {
		/**
		 * @see \WP_Plugins_List_Table::views
		 * @see \WP_Plugins_List_Table::get_views
		 */
		if ( ! isset( $views[ $slug ] ) ) {
			return $views;
		}
		global $totals;
		if ( $is_current ) {
			foreach ( $views as $key => $view ) {
				$views[ $key ] = str_replace( ' class="current" aria-current="page"', null, $view );
			}
		}
		$views[ $slug ] = sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( add_query_arg( 'plugin_status', $slug, 'plugins.php' ) ),
			$is_current ? ' class="current" aria-current="page"' : null,
			sprintf( '%s <span class="count">(%s)</span>', $name, $totals[ $slug ] )
		);

		return $views;
	}, $priority );
};

$plugins_filter( 'WP Activators', 'wp-activators', function ( $plugin ) {
	return $plugin['Author'] === 'moh@medhk2' && str_ends_with( $plugin['Name'], ' Activ@tor' );
} );

return [
	'is_plugin_installed'          => $is_plugin_installed = function ( $plugin ): bool {
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $plugin ] );
	},
	'admin_notice_ignored'         => function (): bool {
		global $pagenow;
		$action = $_REQUEST['action'] ?? '';

		return $pagenow == 'update.php' && in_array( $action, [ 'install-plugin', 'upload-plugin' ], true );
	},
	'admin_notice_plugin_install'  => function ( string $plugin, ?string $wp_plugin_id, string $plugin_name, string $activator_name, string $domain ) use ( $is_plugin_installed ): bool {
		if ( ! $is_plugin_installed( $plugin ) ) {
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
	},
	'admin_notice_plugin_activate' => function ( string $plugin, string $activator_name, string $domain ): bool {
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
	},
	'json_response'                => function ( $data ) {
		return [
			'response' => [ 'code' => 200, 'message' => 'OK' ],
			'body'     => json_encode( $data )
		];
	},
	'private_property'             => function ( object $object, string $property ) {
		$reflectionProperty = new \ReflectionProperty( get_class( $object ), $property );
		$reflectionProperty->setAccessible( true );

		return $reflectionProperty->getValue( $object );
	},
	'plugins_filter'               => $plugins_filter,
	'download_file'                => function ( $url, $file_path ) {
		$contents = file_get_contents( $url );
		if ( $contents ) {
			put_content:
			$put_contents = file_put_contents( $file_path, $contents );
			if ( $put_contents === false ) {
				unlink( $file_path );

				return false;
			}
		} else {
			$res = wp_remote_get( $url );
			if ( ! is_wp_error( $res ) && ( $res['response']['code'] == 200 ) ) {
				$contents = $res['body'];
				goto put_content;
			} else {
				return false;
			}
		}

		return true;
	},
	'serialize_response'           => function ( $data ): array {
		return [
			'response' => [ 'code' => 200, 'message' => 'OK' ],
			'body'     => serialize( $data )
		];
	}
];
