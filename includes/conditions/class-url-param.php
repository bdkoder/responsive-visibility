<?php
/**
 * Condition: URL Query Param — true when the request query contains a specific key,
 * optional exact value, or an allow-list of values.
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Url_Param extends Condition {

	public function get_type() {
		return 'url_param';
	}

	public function get_label() {
		return __( 'URL Query Param', 'responsive-visibility' );
	}

	public function get_group() {
		return 'misc';
	}

	public function get_value_field() {
		return array(
			'control'     => 'text',
			'default'     => '',
			'placeholder' => __( 'preview, utm_source=google, or utm_source IN [google, yahoo]', 'responsive-visibility' ),
			'help'        => __( 'Use a key to check existence, key=value for an exact match, or key IN [a, b] for multiple allowed values.', 'responsive-visibility' ),
		);
	}

	public function matches( $value ) {
		$rule = self::parse_rule( $value );
		if ( null === $rule ) {
			return false;
		}

		$params = wp_unslash( $_GET );
		if ( ! is_array( $params ) ) {
			return false;
		}

		$key = $rule['key'];
		if ( '' === $key || ! array_key_exists( $key, $params ) ) {
			return false;
		}

		if ( 'exists' === $rule['mode'] ) {
			return true;
		}

		$actual = $params[ $key ];
		return self::matches_any( $actual, $rule['values'] );
	}

	/**
	 * Parse the rule string into a stable internal shape.
	 *
	 * Supported forms:
	 *   - preview
	 *   - utm_source=google
	 *   - utm_source=google,yahoo
	 *   - utm_source IN [google, yahoo]
	 *
	 * @param mixed $value Raw stored value from block attributes.
	 * @return array{mode:string,key:string,values:string[]}|null
	 */
	private static function parse_rule( $value ) {
		$rule = trim( (string) $value );
		if ( '' === $rule ) {
			return null;
		}

		if ( preg_match( '/^([^\s=]+)\s+in\s+(.+)$/i', $rule, $matches ) ) {
			return array(
				'mode'   => 'in',
				'key'    => sanitize_key( trim( $matches[1] ) ),
				'values' => self::parse_allowed_values( $matches[2] ),
			);
		}

		if ( false !== strpos( $rule, '=' ) ) {
			list( $raw_key, $raw_values ) = array_pad( explode( '=', $rule, 2 ), 2, '' );
			return array(
				'mode'   => 'in',
				'key'    => sanitize_key( trim( (string) $raw_key ) ),
				'values' => self::parse_allowed_values( $raw_values ),
			);
		}

		return array(
			'mode'   => 'exists',
			'key'    => sanitize_key( $rule ),
			'values' => array(),
		);
	}

	/**
	 * Convert a raw RHS string into a clean allow-list.
	 *
	 * @param string $raw Raw comma-separated values, optionally wrapped in [].
	 * @return string[]
	 */
	private static function parse_allowed_values( $raw ) {
		$value = trim( (string) $raw );
		$value = trim( $value, "[] \t\n\r\0\x0B" );

		$allowed = array();
		foreach ( explode( ',', $value ) as $item ) {
			$item = trim( $item );
			if ( '' !== $item ) {
				$allowed[] = $item;
			}
		}

		return array_values( array_unique( $allowed ) );
	}

	/**
	 * Match the actual query value against an allow-list.
	 *
	 * @param mixed   $actual The query param value from $_GET.
	 * @param string[] $allowed Allowed values.
	 * @return bool
	 */
	private static function matches_any( $actual, array $allowed ) {
		if ( empty( $allowed ) ) {
			return false;
		}

		$actual_values = is_array( $actual ) ? array_map( 'strval', $actual ) : array( (string) $actual );
		foreach ( $allowed as $allowed_value ) {
			if ( in_array( $allowed_value, $actual_values, true ) ) {
				return true;
			}
		}

		return false;
	}
}
