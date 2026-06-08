<?php
/**
 * Condition: Specific Post/Page — is the current singular view this exact post (by ID).
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post extends Condition {

	public function get_type() {
		return 'post';
	}

	public function get_label() {
		return __( 'Specific Post / Page', 'responsive-visibility' );
	}

	public function get_group() {
		return 'post';
	}

	public function get_value_field() {
		return array(
			'control'     => 'number',
			'default'     => '',
			'placeholder' => __( 'Post or Page ID', 'responsive-visibility' ),
		);
	}

	public function matches( $value ) {
		$post_id = absint( $value );
		if ( ! $post_id ) {
			return false;
		}
		return is_singular() && get_queried_object_id() === $post_id;
	}
}
