<?php
namespace WowDevs\Responsive_Visibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Breakpoints {

	public static function register() {
		add_action( 'wp_head', array( __CLASS__, 'dynamic_css' ), 99 );
	}

	public static function get_defaults() {
		return array(
			array(
				'slug'      => 'mobile',
				'label'     => 'Mobile',
				'max_width' => 767,
			),
			array(
				'slug'      => 'tablet',
				'label'     => 'Tablet',
				'max_width' => 1024,
			),
			array(
				'slug'      => 'desktop',
				'label'     => 'Desktop',
				'max_width' => null,
			),
		);
	}

	public static function class_for_slug( $slug ) {
		$legacy = array(
			'mobile'  => 'mobile-hidden',
			'tablet'  => 'tablet-hidden',
			'desktop' => 'desktop-hidden',
		);
		return isset( $legacy[ $slug ] ) ? $legacy[ $slug ] : 'rv-hidden--' . $slug;
	}

	public static function sort( array $breakpoints ) {
		usort( $breakpoints, function ( $a, $b ) {
			if ( null === $a['max_width'] && null === $b['max_width'] ) {
				return 0;
			}
			if ( null === $a['max_width'] ) {
				return 1;
			}
			if ( null === $b['max_width'] ) {
				return -1;
			}
			return $a['max_width'] - $b['max_width'];
		} );
		return $breakpoints;
	}

	public static function dynamic_css() {
		// Guard BEFORE sort() — sort() is array-typed; a corrupted non-array option
		// (manual DB edit, bad migration) would TypeError-fatal inside wp_head.
		$breakpoints = get_option( 'responsive_visibility_breakpoints', self::get_defaults() );

		if ( empty( $breakpoints ) || ! is_array( $breakpoints ) ) {
			return;
		}

		$breakpoints = self::sort( $breakpoints );

		$css      = '';
		$prev_min = 0;
		$count    = count( $breakpoints );

		foreach ( $breakpoints as $i => $bp ) {
			$slug      = sanitize_key( $bp['slug'] );
			$class     = self::class_for_slug( $slug );
			$is_last   = ( $i === $count - 1 );
			$max_width = isset( $bp['max_width'] ) ? absint( $bp['max_width'] ) : null;

			if ( $is_last && $count > 1 ) {
				$css .= "@media (min-width:{$prev_min}px){body .{$class}{display:none!important}}";
			} elseif ( 0 === $prev_min ) {
				if ( null === $max_width ) {
					continue; // A lone, uncapped breakpoint defines no hide range.
				}
				$css .= "@media (max-width:{$max_width}px){body .{$class}{display:none!important}}";
				$prev_min = $max_width + 1;
			} else {
				$css .= "@media (min-width:{$prev_min}px) and (max-width:{$max_width}px){body .{$class}{display:none!important}}";
				$prev_min = $max_width + 1;
			}
		}

		echo '<style id="rv-dynamic-breakpoints">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
