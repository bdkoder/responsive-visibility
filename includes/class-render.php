<?php
namespace WowDevs\Responsive_Visibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Render {

	public static function register() {
		add_filter( 'render_block', array( __CLASS__, 'render_block' ), 10, 3 );
	}

	public static function render_block( $block_content, $block, $content ) {
		if ( empty( $block['attrs'] ) ) {
			return $block_content;
		}

		$a = $block['attrs'];

		// Login-status condition — server-side removal (NOT CSS: auth can't be tested in
		// CSS, and member-only content must never sit in guest HTML). Runs before the
		// device logic; '' (default) means everyone, so existing blocks are untouched.
		if ( ! empty( $a['loginVisibility'] ) ) {
			$login_visibility = $a['loginVisibility'];
			if ( in_array( $login_visibility, array( 'logged-in', 'logged-out' ), true ) ) {
				$logged_in = is_user_logged_in();
				if ( 'logged-in' === $login_visibility && ! $logged_in ) {
					return ''; // Logged-in-only block, viewer is a guest.
				}
				if ( 'logged-out' === $login_visibility && $logged_in ) {
					return ''; // Logged-out-only block, viewer is a member.
				}
			}
		}

		$has_attr = ( ! empty( $a['hiddenBreakpoints'] ) && is_array( $a['hiddenBreakpoints'] ) )
			|| ! empty( $a['hideOnDesktop'] )
			|| ! empty( $a['hideOnTablet'] )
			|| ! empty( $a['hideOnMobile'] );

		if ( ! $has_attr ) {
			return $block_content;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_content );
		if ( ! $tags->next_tag() ) {
			return $block_content;
		}

		// New system: hiddenBreakpoints array (e.g. ["mobile","widescreen"]).
		if ( ! empty( $block['attrs']['hiddenBreakpoints'] ) && is_array( $block['attrs']['hiddenBreakpoints'] ) ) {
			foreach ( $block['attrs']['hiddenBreakpoints'] as $slug ) {
				$tags->add_class( Breakpoints::class_for_slug( sanitize_key( (string) $slug ) ) );
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
}
