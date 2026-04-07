<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GUC_Team_CPT {

	public static function init() {
		add_action( 'init', [ self::class, 'register' ] );
		add_action( 'save_post_team_member', [ self::class, 'sync_post_title' ], 20, 2 );
		add_filter( 'manage_team_member_posts_columns', [ self::class, 'admin_columns' ] );
		add_action( 'manage_team_member_posts_custom_column', [ self::class, 'admin_column_content' ], 10, 2 );
	}

	public static function register() {
		$labels = [
			'name'               => __( 'Team Members', 'guc-team' ),
			'singular_name'      => __( 'Team Member', 'guc-team' ),
			'add_new'            => __( 'Add New', 'guc-team' ),
			'add_new_item'       => __( 'Add New Team Member', 'guc-team' ),
			'edit_item'          => __( 'Edit Team Member', 'guc-team' ),
			'new_item'           => __( 'New Team Member', 'guc-team' ),
			'search_items'       => __( 'Search Team Members', 'guc-team' ),
			'not_found'          => __( 'No team members found.', 'guc-team' ),
			'not_found_in_trash' => __( 'No team members found in trash.', 'guc-team' ),
			'menu_name'          => __( 'Team Members', 'guc-team' ),
		];

		register_post_type( 'team_member', [
			'labels'       => $labels,
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( GUC_TEAM_PATH . 'assets/images/icon.svg' ) ),
			'supports'     => [ 'thumbnail' ], // only featured image; all other fields are meta
			'has_archive'  => false,
			'rewrite'      => false,
			'capabilities' => [
				'create_posts' => 'edit_posts',
			],
			'map_meta_cap' => true,
		] );
	}

	/**
	 * Keep post_title in sync with first + last name so the WP admin list is readable.
	 */
	public static function sync_post_title( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$first = sanitize_text_field( get_post_meta( $post_id, '_team_first_name', true ) );
		$last  = sanitize_text_field( get_post_meta( $post_id, '_team_last_name', true ) );
		$title = trim( $first . ' ' . $last );

		if ( $title && $post->post_title !== $title ) {
			// Unhook to avoid infinite loop.
			remove_action( 'save_post_team_member', [ self::class, 'sync_post_title' ], 20 );
			wp_update_post( [
				'ID'         => $post_id,
				'post_title' => $title,
				'post_name'  => sanitize_title( $title ),
			] );
			add_action( 'save_post_team_member', [ self::class, 'sync_post_title' ], 20, 2 );
		}
	}

	public static function admin_columns( $columns ) {
		$new = [];
		foreach ( $columns as $key => $label ) {
			if ( $key === 'title' ) {
				$new['team_photo']    = __( 'Bild', 'guc-team' );
				$new['team_name']     = __( 'Name', 'guc-team' );
				$new['team_function'] = __( 'Funktion/Titel', 'guc-team' );
				$new['team_cats']     = __( 'Kategorien', 'guc-team' );
			} elseif ( $key === 'date' ) {
				// skip date
			} else {
				$new[ $key ] = $label;
			}
		}
		return $new;
	}

	public static function admin_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'team_photo':
				$photo_id = (int) get_post_meta( $post_id, '_team_photo_id', true );
				$thumb    = $photo_id ? wp_get_attachment_image( $photo_id, [ 50, 50 ] ) : '';
				echo $thumb ?: '<span style="color:#999">—</span>';
				break;
			case 'team_name':
				$first = get_post_meta( $post_id, '_team_first_name', true );
				$last  = get_post_meta( $post_id, '_team_last_name', true );
				echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( trim( $first . ' ' . $last ) ) . '</a>';
				break;
			case 'team_function':
				echo esc_html( get_post_meta( $post_id, '_team_function', true ) );
				break;
			case 'team_cats':
				$terms = get_the_terms( $post_id, 'team_category' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
				} else {
					echo '<span style="color:#999">—</span>';
				}
				break;
		}
	}
}
