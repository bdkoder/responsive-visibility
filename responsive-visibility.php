<?php
/**
 * Plugin Name:       Responsive Visibility
 * Description:       The responsive visibility bundle will give you the ability to control a page's content based on the device your visitors are using to view the page.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           1.0.1
 * Author:            bdkoder
 * Author URI:        https://github.com/bdkoder
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       responsive-visibility
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */


function wb_responsive_visibility_init() {
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
add_action( 'init', 'wb_responsive_visibility_init' );
function wb_responsive_visibility_render_block( $block_content, $block, $content ) {
	if ( ! empty( $block['attrs'] ) ) {
		$tags = new WP_HTML_Tag_Processor( $block_content );
		$tags->next_tag();
		if ( ! empty( $block['attrs']['hideOnDesktop'] ) ) {
			$tags->add_class( 'desktop-hidden' );
		}

		if ( ! empty( $block['attrs']['hideOnTablet'] ) ) {
			$tags->add_class( 'tablet-hidden' );
		}

		if ( ! empty( $block['attrs']['hideOnMobile'] ) ) {
			$tags->add_class( 'mobile-hidden' );
		}

		$block_content = $tags->get_updated_html();
	}
	return $block_content;
}

add_filter( 'render_block', "wb_responsive_visibility_render_block", 10, 3 );
