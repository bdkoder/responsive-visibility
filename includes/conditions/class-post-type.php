<?php
/**
 * Condition: Post Type — is the current view a singular item or archive of this post type.
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post_Type extends Condition {

	public function get_type() {
		return 'post_type';
	}

	public function get_label() {
		return __( 'Post Type', 'responsive-visibility' );
	}

	public function get_group() {
		return 'post';
	}

	public function get_value_field() {
		return array(
			'control' => 'select',
			'default' => '',
			'options' => $this->post_type_options(),
		);
	}

	public function matches( $value ) {
		$post_type = sanitize_key( (string) $value );
		if ( '' === $post_type ) {
			return false;
		}
		return is_singular( $post_type ) || is_post_type_archive( $post_type );
	}

	/**
	 * Public post types as { label, value } pairs for the editor select.
	 *
	 * @return array[]
	 */
	private function post_type_options() {
		$options = array();
		$types   = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $types as $type ) {
			$options[] = array(
				'label' => $type->labels->singular_name,
				'value' => $type->name,
			);
		}
		return $options;
	}
}
