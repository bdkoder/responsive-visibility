<?php
/**
 * Visibility Conditions engine.
 *
 * Owns the registry of condition classes, the editor schema (sent to JS), and the single
 * front-end decision: given a block's saved condition config, should the block be removed?
 *
 * Stored on every block as the `rvConditions` attribute:
 *
 *   array(
 *     'enable'   => bool,
 *     'action'   => 'show' | 'hide',   // show/hide the block WHEN the rules are met
 *     'relation' => 'all' | 'any',     // all rules must match, or any one
 *     'rules'    => array(
 *       array( 'id' => '…', 'type' => 'role', 'operator' => 'is'|'is_not', 'value' => '…' ),
 *       …
 *     ),
 *   )
 *
 * Each rule's truth = its Condition::matches() flipped by the is/is-not operator. The engine
 * is server-side (returns '' from render_block) because auth/role/etc. cannot be done in CSS
 * and must never leak gated markup. Login-status is just the `authentication` condition.
 */
namespace WowDevs\Responsive_Visibility;

use WowDevs\Responsive_Visibility\Conditions\Condition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/conditions/abstract-condition.php';
require_once __DIR__ . '/conditions/class-authentication.php';
require_once __DIR__ . '/conditions/class-role.php';
require_once __DIR__ . '/conditions/class-user.php';
require_once __DIR__ . '/conditions/class-post.php';
require_once __DIR__ . '/conditions/class-post-type.php';
require_once __DIR__ . '/conditions/class-static-page.php';
require_once __DIR__ . '/conditions/class-shortcode.php';

class Conditions {

	const ATTR = 'rvConditions';

	/**
	 * Lazily-built map of type => Condition instance.
	 *
	 * @var Condition[]|null
	 */
	private static $conditions = null;

	/**
	 * Condition classes in editor display order. Add a core condition here (one line) plus its
	 * class file — nothing else changes; the editor UI is schema-driven.
	 *
	 * Extensible: an add-on (e.g. a Pro tier) registers its own Condition subclasses via the
	 * `responsive_visibility_condition_classes` filter — no core edits needed. The add-on is
	 * responsible for loading its class files before the filter runs.
	 *
	 * @return string[]
	 */
	private static function classes() {
		$classes = array(
			Conditions\Authentication::class,
			Conditions\Role::class,
			Conditions\User::class,
			Conditions\Post::class,
			Conditions\Post_Type::class,
			Conditions\Static_Page::class,
			Conditions\Shortcode::class,
		);

		/**
		 * Filters the registered visibility-condition classes.
		 *
		 * @param string[] $classes Fully-qualified Condition subclass names.
		 */
		return apply_filters( 'responsive_visibility_condition_classes', $classes );
	}

	/**
	 * All condition instances keyed by type.
	 *
	 * @return Condition[]
	 */
	public static function all() {
		if ( null === self::$conditions ) {
			self::$conditions = array();
			foreach ( self::classes() as $class ) {
				$condition = new $class();
				self::$conditions[ $condition->get_type() ] = $condition;
			}
		}
		return self::$conditions;
	}

	/**
	 * One condition by type, or null if unknown.
	 *
	 * @param string $type
	 * @return Condition|null
	 */
	public static function get( $type ) {
		$all = self::all();
		return isset( $all[ $type ] ) ? $all[ $type ] : null;
	}

	/**
	 * Editor groups for the condition dropdown.
	 *
	 * @return array slug => label
	 */
	public static function groups() {
		$groups = array(
			'user' => __( 'User', 'responsive-visibility' ),
			'post' => __( 'Post', 'responsive-visibility' ),
			'misc' => __( 'Misc', 'responsive-visibility' ),
		);

		/**
		 * Filters the condition groups shown in the editor. Add-ons can add groups
		 * (e.g. WooCommerce, ACF) for their own conditions.
		 *
		 * @param array $groups slug => label.
		 */
		return apply_filters( 'responsive_visibility_condition_groups', $groups );
	}

	/**
	 * Schema localized to the editor as `window.rvConditions`. PHP stays the single source of
	 * truth for which conditions exist and how their value control looks.
	 *
	 * @return array
	 */
	public static function js_registry() {
		$types = array();
		foreach ( self::all() as $condition ) {
			$types[] = array(
				'type'  => $condition->get_type(),
				'label' => $condition->get_label(),
				'group' => $condition->get_group(),
				'value' => $condition->get_value_field(),
			);
		}
		return array(
			'groups' => self::groups(),
			'types'  => $types,
		);
	}

	/**
	 * Should this block be removed for the current request, per its saved conditions?
	 *
	 * @param array $attrs Block attributes.
	 * @return bool
	 */
	public static function should_hide( $attrs ) {
		if ( empty( $attrs[ self::ATTR ] ) || ! is_array( $attrs[ self::ATTR ] ) ) {
			return false;
		}

		$config = $attrs[ self::ATTR ];
		if ( empty( $config['enable'] ) ) {
			return false;
		}

		$rules = ( isset( $config['rules'] ) && is_array( $config['rules'] ) ) ? $config['rules'] : array();

		$results = array();
		foreach ( $rules as $rule ) {
			$result = self::evaluate_rule( $rule );
			if ( null === $result ) {
				continue; // Incomplete/unknown rule — ignore it entirely.
			}
			$results[] = $result;
		}

		// No valid rule means no opinion — never hide.
		if ( empty( $results ) ) {
			return false;
		}

		$relation = ( isset( $config['relation'] ) && 'any' === $config['relation'] ) ? 'any' : 'all';

		// Strict comparisons: only real booleans count (mirrors the engine we ported from).
		$met = ( 'any' === $relation )
			? in_array( true, $results, true )
			: ! in_array( false, $results, true );

		$action = ( isset( $config['action'] ) && 'hide' === $config['action'] ) ? 'hide' : 'show';

		// "show when met" hides when NOT met; "hide when met" hides when met.
		return ( 'show' === $action ) ? ! $met : $met;
	}

	/**
	 * Evaluate one rule to a strict bool, or null when it can't be evaluated.
	 *
	 * @param array $rule
	 * @return bool|null
	 */
	private static function evaluate_rule( $rule ) {
		if ( ! is_array( $rule ) || empty( $rule['type'] ) ) {
			return null;
		}

		$condition = self::get( sanitize_key( $rule['type'] ) );
		if ( ! $condition ) {
			return null;
		}

		$value = isset( $rule['value'] ) ? $rule['value'] : '';

		// A condition that needs a value but has none is incomplete — skip it.
		if ( null !== $condition->get_value_field() && '' === $value ) {
			return null;
		}

		$matched  = (bool) $condition->matches( $value );
		$operator = ( isset( $rule['operator'] ) && 'is_not' === $rule['operator'] ) ? 'is_not' : 'is';

		return ( 'is_not' === $operator ) ? ! $matched : $matched;
	}
}
