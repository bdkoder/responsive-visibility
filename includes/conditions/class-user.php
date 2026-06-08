<?php
/**
 * Condition: Specific User — is the current logged-in user this exact user (by ID).
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class User extends Condition {

	public function get_type() {
		return 'user';
	}

	public function get_label() {
		return __( 'Specific User', 'responsive-visibility' );
	}

	public function get_group() {
		return 'user';
	}

	public function get_value_field() {
		return array(
			'control'     => 'number',
			'default'     => '',
			'placeholder' => __( 'User ID', 'responsive-visibility' ),
		);
	}

	public function matches( $value ) {
		$user_id = absint( $value );
		if ( ! $user_id ) {
			return false;
		}
		return get_current_user_id() === $user_id;
	}
}
