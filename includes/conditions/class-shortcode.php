<?php
/**
 * Condition: Shortcode — true when the given shortcode renders non-empty output.
 *
 * Lets a developer gate a block on any custom logic by wrapping it in a shortcode that
 * prints something (true) or nothing (false). Advanced/escape-hatch condition.
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode extends Condition {

	public function get_type() {
		return 'shortcode';
	}

	public function get_label() {
		return __( 'Shortcode', 'responsive-visibility' );
	}

	public function get_group() {
		return 'misc';
	}

	public function get_value_field() {
		return array(
			'control'     => 'text',
			'default'     => '',
			'placeholder' => '[my_shortcode]',
		);
	}

	public function matches( $value ) {
		$shortcode = trim( (string) $value );
		if ( '' === $shortcode ) {
			return false;
		}
		$output = trim( wp_strip_all_tags( do_shortcode( $shortcode ) ) );
		// Non-empty output (and not a literal "0") counts as true.
		return '' !== $output && '0' !== $output;
	}
}
