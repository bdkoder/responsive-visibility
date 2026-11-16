<?php
/**
 * WowDevs Offer banner: loader shared by every wowDevs plugin.
 *
 * Copy the whole `wowdevs-offer` folder unchanged into each plugin. When several plugins are active,
 * only the newest copy of the class loads, so one banner shows. Never change the function
 * signatures below: older copies of this file may define them first.
 *
 * Spec: .ai/features/wowdevs-offer/README.md
 *
 * @package WowDevs_Offer
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wowdevs_offer_add_version' ) ) {

	/**
	 * Offer a copy of the class. The highest version is the one that loads.
	 *
	 * @param string $version Class version.
	 * @param string $file    Absolute path of the class file.
	 * @param string $class_name Class name.
	 */
	function wowdevs_offer_add_version( $version, $file, $class_name ) {
		$GLOBALS['wowdevs_offer']['versions'][ (string) $version ] = [ (string) $file, (string) $class_name ];
	}

	/**
	 * Register a plugin that wants its offer shown.
	 *
	 * @param array $product { product: offer.json key, page: admin ?page= slug }.
	 */
	function wowdevs_offer_register( $product ) {
		if ( ! is_array( $product ) || empty( $product['product'] ) ) {
			return;
		}

		$GLOBALS['wowdevs_offer']['products'][ (string) $product['product'] ] = $product;
	}

	/**
	 * Load the newest class once and hand it every registered product.
	 */
	function wowdevs_offer_boot() {
		$state = isset( $GLOBALS['wowdevs_offer'] ) && is_array( $GLOBALS['wowdevs_offer'] ) ? $GLOBALS['wowdevs_offer'] : [];

		if ( empty( $state['versions'] ) || empty( $state['products'] ) ) {
			return;
		}

		$versions = array_keys( $state['versions'] );
		usort( $versions, 'version_compare' );
		list( $file, $class ) = $state['versions'][ end( $versions ) ];

		if ( ! class_exists( $class, false ) && is_readable( $file ) ) {
			require_once $file;
		}

		if ( class_exists( $class, false ) && method_exists( $class, 'boot' ) ) {
			call_user_func( [ $class, 'boot' ], $state['products'] );
		}
	}

	add_action( 'init', 'wowdevs_offer_boot', 20 );
}

wowdevs_offer_add_version( '1.0.0', __DIR__ . '/class-offer.php', 'WowDevs_Offer_1_0_0' );
