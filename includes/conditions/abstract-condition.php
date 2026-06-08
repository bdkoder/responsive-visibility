<?php
/**
 * Base class for a single visibility condition.
 *
 * Each condition is one small, self-contained class: it declares how it appears in the
 * editor (type/label/group + an optional value field schema) and answers one question on
 * the front end — `matches()`: is this condition true for the current request?
 *
 * The is/is-not operator and the show/hide + all/any logic live in the Conditions manager,
 * so a condition only ever describes ITSELF. Adding a new condition = one new subclass.
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Condition {

	/**
	 * Stable machine id stored in block attributes (e.g. "authentication"). Never rename.
	 *
	 * @return string
	 */
	abstract public function get_type();

	/**
	 * Human label shown in the editor's condition dropdown.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Group slug for editor grouping. One of Conditions::groups() keys (user|post|misc).
	 *
	 * @return string
	 */
	abstract public function get_group();

	/**
	 * Schema for the value control shown next to this condition in the editor, or null when
	 * the condition needs no value. Shape (sent to JS verbatim):
	 *
	 *   array(
	 *     'control'     => 'select' | 'number' | 'text',
	 *     'options'     => array( array( 'label' => ..., 'value' => ... ), ... ), // select only
	 *     'placeholder' => '…',
	 *     'default'     => '…',
	 *   )
	 *
	 * @return array|null
	 */
	public function get_value_field() {
		return null;
	}

	/**
	 * Is this condition true for the current front-end request?
	 *
	 * @param mixed $value The stored value for this rule (already from block attrs, unsanitized).
	 * @return bool
	 */
	abstract public function matches( $value );

	/**
	 * Roles of the current user (empty array for guests). Shared helper for user conditions.
	 *
	 * @return string[]
	 */
	protected function current_user_roles() {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		return (array) wp_get_current_user()->roles;
	}
}
