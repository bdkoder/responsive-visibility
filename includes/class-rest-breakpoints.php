<?php
namespace WowDevs\Responsive_Visibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API for the breakpoints settings page (React admin).
 *
 * This controller is the ONLY writer of the `responsive_visibility_breakpoints`
 * option. It ports the slug-preserving two-pass sanitizer so renaming a label never
 * changes a breakpoint's slug — saved blocks keep working. See
 * .ai/settings-hardening/SKILL.md and .ai/admin-react/SKILL.md.
 */
class Rest_Breakpoints {

	const REST_NAMESPACE = 'responsive-visibility/v1';
	const REST_ROUTE     = '/breakpoints';
	const OPTION         = 'responsive_visibility_breakpoints';

	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_breakpoints' ),
					'permission_callback' => array( __CLASS__, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'save_breakpoints' ),
					'permission_callback' => array( __CLASS__, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'reset_breakpoints' ),
					'permission_callback' => array( __CLASS__, 'permission_check' ),
				),
			)
		);
	}

	public static function permission_check() {
		return current_user_can( 'manage_options' );
	}

	public static function get_breakpoints() {
		return rest_ensure_response(
			array(
				'breakpoints' => get_option( self::OPTION, Breakpoints::get_defaults() ),
				'defaults'    => Breakpoints::get_defaults(),
			)
		);
	}

	public static function save_breakpoints( \WP_REST_Request $request ) {
		$incoming = $request->get_param( 'breakpoints' );
		if ( ! is_array( $incoming ) ) {
			return new \WP_Error(
				'rv_invalid_payload',
				__( 'Invalid breakpoints payload.', 'responsive-visibility' ),
				array( 'status' => 400 )
			);
		}

		$result = self::sanitize_breakpoints( $incoming );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_option( self::OPTION, $result );

		return rest_ensure_response( array( 'breakpoints' => $result ) );
	}

	public static function reset_breakpoints() {
		delete_option( self::OPTION );

		return rest_ensure_response( array( 'breakpoints' => Breakpoints::get_defaults() ) );
	}

	/**
	 * Slug-preserving sanitizer — the single source of truth for writes.
	 *
	 * Pass 1 reserves every incoming (locked) slug; pass 2 reuses those verbatim and
	 * mints `sanitize_title(label)` (with a `-2` collision bump) only for new rows.
	 *
	 * @param array $incoming Rows of { slug?, label, max_width }.
	 * @return array|\WP_Error Sorted breakpoints, or WP_Error on validation failure.
	 */
	public static function sanitize_breakpoints( array $incoming ) {
		$slugs_used = array();
		foreach ( $incoming as $row ) {
			$label = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( $row['label'] ) ) : '';
			if ( '' === $label ) {
				continue;
			}
			$slug = isset( $row['slug'] ) ? sanitize_key( $row['slug'] ) : '';
			if ( '' !== $slug ) {
				$slugs_used[] = $slug;
			}
		}

		$breakpoints = array();
		foreach ( $incoming as $row ) {
			$label = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( $row['label'] ) ) : '';
			if ( '' === $label ) {
				continue;
			}

			$slug = isset( $row['slug'] ) ? sanitize_key( $row['slug'] ) : '';
			if ( '' === $slug ) {
				$slug          = sanitize_title( $label );
				$original_slug = $slug;
				$counter       = 2;
				while ( in_array( $slug, $slugs_used, true ) ) {
					$slug = $original_slug . '-' . $counter;
					++$counter;
				}
				$slugs_used[] = $slug;
			}

			$raw_max   = isset( $row['max_width'] ) ? $row['max_width'] : null;
			$max_width = ( null === $raw_max || '' === $raw_max ) ? null : max( 1, absint( $raw_max ) );

			$breakpoints[] = array(
				'slug'      => $slug,
				'label'     => $label,
				'max_width' => $max_width,
			);
		}

		if ( count( $breakpoints ) < 1 ) {
			return new \WP_Error(
				'rv_empty',
				__( 'You must have at least one breakpoint.', 'responsive-visibility' ),
				array( 'status' => 400 )
			);
		}

		$null_count = count(
			array_filter(
				$breakpoints,
				function ( $bp ) {
					return null === $bp['max_width'];
				}
			)
		);
		if ( $null_count > 1 ) {
			return new \WP_Error(
				'rv_multi_blank',
				__( 'Only one breakpoint can have no max-width (the largest device).', 'responsive-visibility' ),
				array( 'status' => 400 )
			);
		}

		return Breakpoints::sort( $breakpoints );
	}
}
