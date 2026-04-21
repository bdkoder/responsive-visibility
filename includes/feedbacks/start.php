<?php
/**
 * Feedbacks SDK Loader
 * SDK Version 1.0.1
 *
 * Strategy: "highest-version-wins"
 * - If multiple plugins include this feedbacks system, the latest code wins.
 * - One feedback notice per admin page load.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$_rc_sdk_this_version = '1.0.1';

if (
	! isset( $GLOBALS['_rc_sdk_version'] ) ||
	version_compare( $_rc_sdk_this_version, $GLOBALS['_rc_sdk_version'], '>' )
) {
	$GLOBALS['_rc_sdk_version'] = $_rc_sdk_this_version;
	$GLOBALS['_rc_sdk_path']    = __DIR__;
}

if ( ! function_exists( 'rc_dynamic_init' ) ) {
	function rc_dynamic_init( $params ) {

		if ( ! is_admin() ) {
			return;
		}

		// Use the winning Feedback SDK path.
		$sdk_path = $GLOBALS['_rc_sdk_path'];

		// Auto-inject the winning version.
		$params['sdk_version'] = $GLOBALS['_rc_sdk_version'];

		$params['current_page'] = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;
		$params['menu_slug']    = isset( $params['menu']['slug'] ) ? $params['menu']['slug'] : false;

		require_once $sdk_path . '/notice.php';

		if ( function_exists( 'rc_sdk_automate' ) ) {
			rc_sdk_automate( $params );
		}
	}
}
