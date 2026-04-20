<?php
/**
 * Plugin Name:       Responsive Visibility for Blocks Editor
 * Description:       The responsive visibility bundle will give you the ability to control a page's content based on the device your visitors are using to view the page.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           1.0.6
 * Author:            bdkoder
 * Author URI:        https://github.com/bdkoder
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       responsive-visibility
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'includes/admin-settings.php';

/**
 * Returns the default breakpoints matching the plugin's original hardcoded values.
 */
function rv_default_breakpoints() {
	return array(
		array( 'slug' => 'mobile',  'label' => 'Mobile',  'max_width' => 767  ),
		array( 'slug' => 'tablet',  'label' => 'Tablet',  'max_width' => 1024 ),
		array( 'slug' => 'desktop', 'label' => 'Desktop', 'max_width' => null ),
	);
}

/**
 * Maps a breakpoint slug to its CSS class name.
 * The default three slugs keep their legacy class names for backward compatibility.
 */
function rv_class_for_slug( $slug ) {
	$legacy = array(
		'mobile'  => 'mobile-hidden',
		'tablet'  => 'tablet-hidden',
		'desktop' => 'desktop-hidden',
	);
	return isset( $legacy[ $slug ] ) ? $legacy[ $slug ] : 'rv-hidden--' . $slug;
}

/**
 * Outputs dynamic CSS media queries based on saved breakpoint settings.
 * Runs on wp_head at priority 99 to override the compiled default stylesheet.
 */
function responsive_visibility_dynamic_css() {
	$breakpoints = get_option( 'responsive_visibility_breakpoints', rv_default_breakpoints() );

	if ( empty( $breakpoints ) || ! is_array( $breakpoints ) ) {
		return;
	}

	// Sort: nulls (no upper limit) last; others ascending.
	usort( $breakpoints, function ( $a, $b ) {
		if ( null === $a['max_width'] && null === $b['max_width'] ) return 0;
		if ( null === $a['max_width'] ) return 1;
		if ( null === $b['max_width'] ) return -1;
		return $a['max_width'] - $b['max_width'];
	} );

	$css      = '';
	$prev_min = 0;
	$count    = count( $breakpoints );

	foreach ( $breakpoints as $i => $bp ) {
		$slug      = sanitize_key( $bp['slug'] );
		$class     = rv_class_for_slug( $slug );
		$is_last   = ( $i === $count - 1 );
		$max_width = isset( $bp['max_width'] ) ? absint( $bp['max_width'] ) : null;

		if ( $is_last ) {
			$css .= "@media (min-width:{$prev_min}px){body .{$class}{display:none!important}}";
		} elseif ( 0 === $prev_min ) {
			$css .= "@media (max-width:{$max_width}px){body .{$class}{display:none!important}}";
			$prev_min = $max_width + 1;
		} else {
			$css .= "@media (min-width:{$prev_min}px) and (max-width:{$max_width}px){body .{$class}{display:none!important}}";
			$prev_min = $max_width + 1;
		}
	}

	echo '<style id="rv-dynamic-breakpoints">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'responsive_visibility_dynamic_css', 99 );

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
						'breakpoints' => get_option( 'responsive_visibility_breakpoints', rv_default_breakpoints() ),
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
function responsive_visibility_render_block( $block_content, $block, $content ) {
	if ( empty( $block['attrs'] ) ) {
		return $block_content;
	}

	$tags = new WP_HTML_Tag_Processor( $block_content );
	if ( ! $tags->next_tag() ) {
		return $block_content;
	}

	// New system: hiddenBreakpoints array (e.g. ["mobile","widescreen"]).
	if ( ! empty( $block['attrs']['hiddenBreakpoints'] ) && is_array( $block['attrs']['hiddenBreakpoints'] ) ) {
		foreach ( $block['attrs']['hiddenBreakpoints'] as $slug ) {
			$tags->add_class( rv_class_for_slug( sanitize_key( (string) $slug ) ) );
		}
	}

	// Legacy: original boolean attributes — kept for backward compatibility.
	if ( ! empty( $block['attrs']['hideOnDesktop'] ) ) {
		$tags->add_class( 'desktop-hidden' );
	}
	if ( ! empty( $block['attrs']['hideOnTablet'] ) ) {
		$tags->add_class( 'tablet-hidden' );
	}
	if ( ! empty( $block['attrs']['hideOnMobile'] ) ) {
		$tags->add_class( 'mobile-hidden' );
	}

	return $tags->get_updated_html();
}

add_filter( 'render_block', 'responsive_visibility_render_block', 10, 3 );


/**
 * SDK Integration
 */

if ( ! function_exists( 'responsive_visibility_dci_plugin' ) ) {
	function responsive_visibility_dci_plugin() {

		// Include DCI SDK.
		require_once dirname( __FILE__ ) . '/dci/start.php';
		wp_register_style( 'dci-sdk-responsive-visibility', plugins_url( 'dci/assets/css/dci.css', __FILE__ ), array(), '1.2.1', 'all' );
		wp_enqueue_style( 'dci-sdk-responsive-visibility' );

		dci_dynamic_init( array(
			'sdk_version'          => '1.2.1',
			'product_id'           => 4,
			'plugin_name'          => 'Responsive Visibility for Blocks Editor', // make simple, must not empty
			'plugin_title'         => 'Love using Responsive Visibility? Congrats 🎉  ( Never miss an Important Update )', // You can describe your plugin title here
			'plugin_icon'          => plugins_url( 'assets/imgs/icon-256x256.png', __FILE__ ), // delete the line if you don't need
			'api_endpoint'         => 'https://dashboard.wowdevs.com/wp-json/dci/v1/data-insights',
			'slug'                 => 'no-need', // folder-name or write 'no-need' if you don't want to use
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
