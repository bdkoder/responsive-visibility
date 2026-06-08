<?php
/**
 * Plugin Name:       Responsive Visibility for Blocks Editor
 * Description:       The responsive visibility bundle will give you the ability to control a page's content based on the device your visitors are using to view the page.
 * Requires at least: 6.2
 * Requires PHP:      7.2
 * Version:           1.1.0
 * Author:            bdkoder
 * Author URI:        https://github.com/bdkoder
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       responsive-visibility
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'RV_PLUGIN_FILE', __FILE__ );
define( 'RV_VERSION', '1.1.0' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-breakpoints.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-render.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-rest-breakpoints.php';

use WowDevs\Responsive_Visibility\Breakpoints;
use WowDevs\Responsive_Visibility\Render;
use WowDevs\Responsive_Visibility\Rest_Breakpoints;
use WowDevs\Responsive_Visibility\Admin_Settings;

Breakpoints::register();
Render::register();
// REST must register unconditionally — /wp-json requests are not is_admin().
Rest_Breakpoints::register();

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin-settings.php';
	Admin_Settings::register();
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function responsive_visibility_init() {
	$extentions = [
		'responsive-visibility',
	];

	foreach ( $extentions as $extention ) {
		$ext_dir_path = plugin_dir_path( __FILE__ ) . 'build/extentions/' . $extention . '/index.asset.php';

		if ( file_exists( $ext_dir_path ) ) {
			$ext_assets = include_once $ext_dir_path;

			if ( ! empty( $ext_assets ) && is_admin() ) {
				wp_register_script(
					"{$extention}-editor-script",
					plugin_dir_url( __FILE__ ) . 'build/extentions/' . $extention . '/index.js',
					$ext_assets['dependencies'],
					$ext_assets['version'],
					true
				);

				wp_register_style(
					"{$extention}-editor-style",
					plugin_dir_url( __FILE__ ) . 'build/extentions/' . $extention . '/index.css',
					[],
					$ext_assets['version']
				);

				wp_enqueue_script( "{$extention}-editor-script" );
				wp_enqueue_style( "{$extention}-editor-style" );

				wp_localize_script(
					"{$extention}-editor-script",
					'rvBreakpoints',
					array(
						'breakpoints' => get_option( 'responsive_visibility_breakpoints', Breakpoints::get_defaults() ),
						'settingsUrl' => admin_url( 'options-general.php?page=responsive-visibility' ),
					)
				);
			}

			if ( ! empty( $ext_assets ) && ! is_admin() ) {
				wp_register_style(
					"{$extention}-style",
					plugin_dir_url( __FILE__ ) . 'build/extentions/' . $extention . '/style-index.css',
					[],
					$ext_assets['version'],
					'all'
				);

				wp_enqueue_style( "{$extention}-style" );
			}
		}
	}
}
add_action( 'init', 'responsive_visibility_init' );


/**
 * SDK Integration
 */

if ( ! function_exists( 'responsive_visibility_dci_plugin' ) ) {
	function responsive_visibility_dci_plugin() {

		// Include DCI SDK.
		require_once dirname( __FILE__ ) . '/dci/start.php';

		dci_dynamic_init( array(
			'product_id'           => 4,
			'plugin_name'          => 'Responsive Visibility for Blocks Editor', // make simple, must not empty
			'plugin_title'         => 'Love using Responsive Visibility? Congrats 🎉  ( Never miss an Important Update )', // You can describe your plugin title here
			'plugin_icon'          => plugins_url( 'assets/imgs/icon-256x256.png', __FILE__ ), // delete the line if you don't need
			'api_endpoint'         => 'https://dashboard.wowdevs.com/wp-json/dci/v1/data-insights',
			'slug'                 => 'responsive-visibility', // folder-name
			'core_file'            => false,
			'plugin_deactivate_id' => false,
			'menu'                 => array(
				'slug' => 'responsive-visibility',
			),
			'public_key'           => 'pk_f4yNaYz9vQ0oBK761nhLUn6OduwLoTm9',
			'is_premium'           => false,
			// 'custom_data' => array(
			// 'test' => 'value',
			// ),
			'popup_notice'         => false,
			'deactivate_feedback'  => true,
			// 'delay_time'    => array(
			// 'time' => 3 * DAY_IN_SECONDS,
			// ),
			'text_domain'          => 'responsive-visibility',
			'plugin_msg'           => '<p>Be Top-contributor by sharing non-sensitive plugin data and create an impact to the global WordPress community today! You can receive valuable emails periodically.</p>',
		) );
	}
	add_action( 'admin_init', 'responsive_visibility_dci_plugin' );
}


/**
 * Review Automation Integration
 */

if ( ! function_exists( 'responsive_visibility_rc_plugin' ) ) {
	function responsive_visibility_rc_plugin() {

		require_once dirname( __FILE__ ) . '/includes/feedbacks/start.php';

		rc_dynamic_init(
			[
				'plugin_name'  => 'Responsive Visibility for Blocks Editor',
				'plugin_icon'  => plugins_url( 'assets/imgs/icon-256x256.png', __FILE__ ),
				'slug'         => 'no-need',
				'menu'         => [
					'slug' => 'responsive-visibility',
				],
				'review_url'   => 'https://wordpress.org/support/plugin/responsive-visibility/reviews/#new-post',
				'plugin_title' => 'Yay! Great that you\'re using Responsive Visibility',
				'plugin_msg'   => '<p>Loved using Responsive Visibility on your website? Share your experience in a review and help us spread the love to everyone right now. Good words will help the community.</p>',
			]
		);
	}
	add_action( 'admin_init', 'responsive_visibility_rc_plugin' );
}
