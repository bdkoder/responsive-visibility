<?php
/**
 * Condition: Page Type — which kind of page is currently being viewed.
 */
namespace WowDevs\Responsive_Visibility\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Static_Page extends Condition {

	public function get_type() {
		return 'static_page';
	}

	public function get_label() {
		return __( 'Page Type', 'responsive-visibility' );
	}

	public function get_group() {
		return 'post';
	}

	public function get_value_field() {
		return array(
			'control' => 'select',
			'default' => 'front_page',
			'options' => array(
				array(
					'label' => __( 'Front page', 'responsive-visibility' ),
					'value' => 'front_page',
				),
				array(
					'label' => __( 'Blog / posts page', 'responsive-visibility' ),
					'value' => 'blog',
				),
				array(
					'label' => __( 'Single post', 'responsive-visibility' ),
					'value' => 'single',
				),
				array(
					'label' => __( 'Page', 'responsive-visibility' ),
					'value' => 'page',
				),
				array(
					'label' => __( 'Archive', 'responsive-visibility' ),
					'value' => 'archive',
				),
				array(
					'label' => __( 'Search results', 'responsive-visibility' ),
					'value' => 'search',
				),
				array(
					'label' => __( '404 (not found)', 'responsive-visibility' ),
					'value' => 'not_found',
				),
			),
		);
	}

	public function matches( $value ) {
		switch ( $value ) {
			case 'front_page':
				return is_front_page();
			case 'blog':
				return is_home();
			case 'single':
				return is_single();
			case 'page':
				return is_page();
			case 'archive':
				return is_archive();
			case 'search':
				return is_search();
			case 'not_found':
				return is_404();
			default:
				return false;
		}
	}
}
