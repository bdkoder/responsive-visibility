<?php
namespace WowDevs\Responsive_Visibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings → Responsive Visibility. Hosts the React breakpoints app: registers the
 * options page, renders the mount node, and enqueues build/admin + bootstrap data.
 * All persistence happens through Rest_Breakpoints. See .ai/admin-react/SKILL.md.
 */
class Admin_Settings {

	private static $hook = '';

	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'settings_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function settings_menu() {
		self::$hook = add_options_page(
			__( 'Responsive Visibility', 'responsive-visibility' ),
			__( 'Responsive Visibility', 'responsive-visibility' ),
			'manage_options',
			'responsive-visibility',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( $hook !== self::$hook ) {
			return;
		}

		$dir        = plugin_dir_path( RV_PLUGIN_FILE );
		$asset_file = $dir . 'build/admin/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_register_script(
			'rv-admin',
			plugins_url( 'build/admin/index.js', RV_PLUGIN_FILE ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// wp-scripts emits `style.scss` imports as style-index.css; fall back just in case.
		$style_rel = file_exists( $dir . 'build/admin/style-index.css' )
			? 'build/admin/style-index.css'
			: 'build/admin/index.css';

		wp_register_style(
			'rv-admin',
			plugins_url( $style_rel, RV_PLUGIN_FILE ),
			array( 'wp-components' ),
			$asset['version']
		);

		wp_add_inline_script(
			'rv-admin',
			'window.rvAdmin = ' . wp_json_encode(
				array(
					// apiFetch carries core's wp_rest nonce automatically (wp-api-fetch dep).
					'restPath'    => '/' . Rest_Breakpoints::REST_NAMESPACE . Rest_Breakpoints::REST_ROUTE,
					'breakpoints' => get_option( 'responsive_visibility_breakpoints', Breakpoints::get_defaults() ),
					'defaults'    => Breakpoints::get_defaults(),
					'strings'     => self::strings(),
				)
			) . ';',
			'before'
		);

		wp_enqueue_script( 'rv-admin' );
		wp_enqueue_style( 'rv-admin' );
	}

	private static function strings() {
		return array(
			'title'        => __( 'Responsive Visibility: Breakpoints', 'responsive-visibility' ),
			'intro'        => __( 'Define breakpoints to control block visibility on different screen sizes. These values apply site-wide to all blocks using Responsive Visibility.', 'responsive-visibility' ),
			'label'        => __( 'Label', 'responsive-visibility' ),
			'maxWidth'     => __( 'Max Width (px)', 'responsive-visibility' ),
			'hiddenWhen'   => __( 'Hidden when', 'responsive-visibility' ),
			'addRow'       => __( 'Add Breakpoint', 'responsive-visibility' ),
			'remove'       => __( 'Remove', 'responsive-visibility' ),
			'save'         => __( 'Save Breakpoints', 'responsive-visibility' ),
			'reset'        => __( 'Reset to defaults', 'responsive-visibility' ),
			'resetConfirm' => __( 'Reset all breakpoints to the defaults (Mobile 767, Tablet 1024, Desktop)? This cannot be undone.', 'responsive-visibility' ),
			'largest'      => __( '∞ Largest device', 'responsive-visibility' ),
			'needMax'      => __( 'set a max-width', 'responsive-visibility' ),
			'untitled'     => __( 'Untitled', 'responsive-visibility' ),
			'labelPlaceholder' => __( 'e.g. Widescreen', 'responsive-visibility' ),
			'saved'        => __( 'Breakpoints saved.', 'responsive-visibility' ),
			'resetDone'    => __( 'Breakpoints reset to defaults.', 'responsive-visibility' ),
			'multiBlank'   => __( 'Only the largest device can have a blank max-width. Set a value on the others.', 'responsive-visibility' ),
			'error'        => __( 'Something went wrong. Please try again.', 'responsive-visibility' ),
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><div id="rv-admin-root"></div><noscript>'
			. esc_html__( 'This settings page requires JavaScript.', 'responsive-visibility' )
			. '</noscript></div>';
	}
}
