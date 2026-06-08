<?php
/**
 * Condition: Login Status — is the visitor logged in or logged out.
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Authentication extends Condition {

	public function get_type() {
		return 'authentication';
	}

	public function get_label() {
		return __( 'Login Status', 'responsive-visibility' );
	}

	public function get_group() {
		return 'user';
	}

	public function get_value_field() {
		return array(
			'control' => 'select',
			'default' => 'logged-in',
			'options' => array(
				array(
					'label' => __( 'Logged in', 'responsive-visibility' ),
					'value' => 'logged-in',
				),
				array(
					'label' => __( 'Logged out', 'responsive-visibility' ),
					'value' => 'logged-out',
				),
			),
		);
	}

	public function matches( $value ) {
		$logged_in = is_user_logged_in();
		return ( 'logged-out' === $value ) ? ! $logged_in : $logged_in;
	}
}
