<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GUC_Team_Taxonomy {

	public static function init() {
		add_action( 'init', [ self::class, 'register' ] );
	}

	public static function register() {
		$labels = [
			'name'              => __( 'Team Categories', 'guc-team' ),
			'singular_name'     => __( 'Team Category', 'guc-team' ),
			'search_items'      => __( 'Search Categories', 'guc-team' ),
			'all_items'         => __( 'All Categories', 'guc-team' ),
			'edit_item'         => __( 'Edit Category', 'guc-team' ),
			'update_item'       => __( 'Update Category', 'guc-team' ),
			'add_new_item'      => __( 'Add New Category', 'guc-team' ),
			'new_item_name'     => __( 'New Category Name', 'guc-team' ),
			'menu_name'         => __( 'Categories', 'guc-team' ),
			'not_found'         => __( 'No categories found.', 'guc-team' ),
		];

		register_taxonomy( 'team_category', 'team_member', [
			'labels'            => $labels,
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => false, // we handle the column ourselves
			'query_var'         => false,
			'rewrite'           => false,
			'show_in_rest'      => false,
		] );
	}
}
