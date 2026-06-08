<?php
/**
 * Condition: User Role — does the logged-in user have the chosen role.
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Role extends Condition {

	public function get_type() {
		return 'role';
	}

	public function get_label() {
		return __( 'User Role', 'responsive-visibility' );
	}

	public function get_group() {
		return 'user';
	}

	public function get_value_field() {
		return array(
			'control' => 'select',
			'default' => '',
			'options' => $this->role_options(),
		);
	}

	public function matches( $value ) {
		$role = sanitize_key( (string) $value );
		if ( '' === $role ) {
			return false;
		}
		return in_array( $role, $this->current_user_roles(), true );
	}

	/**
	 * All registered roles as { label, value } pairs for the editor select.
	 *
	 * @return array[]
	 */
	private function role_options() {
		$options = array();
		$names   = wp_roles()->get_names(); // slug => display name
		foreach ( $names as $slug => $label ) {
			$options[] = array(
				'label' => $label,
				'value' => $slug,
			);
		}
		return $options;
	}
}
