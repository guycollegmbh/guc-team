<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GUC_Team_Admin_Sorting {

	const OPTION_PREFIX = 'guc_team_order_';

	public static function init() {
		add_action( 'admin_menu', [ self::class, 'add_menu' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
		add_action( 'wp_ajax_guc_team_save_order', [ self::class, 'ajax_save_order' ] );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=team_member',
			__( 'Sort Members', 'guc-team' ),
			__( 'Sort Members', 'guc-team' ),
			'edit_posts',
			'guc-team-sorting',
			[ self::class, 'render_page' ]
		);
	}

	public static function enqueue( $hook ) {
		if ( $hook !== 'team_member_page_guc-team-sorting' ) {
			return;
		}
		// SortableJS from CDN (no build tools needed).
		wp_enqueue_script(
			'sortablejs',
			'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
			[],
			'1.15.2',
			true
		);
		wp_enqueue_script(
			'guc-team-admin-sorting',
			GUC_TEAM_URL . 'assets/js/admin-sorting.js',
			[ 'sortablejs', 'jquery' ],
			GUC_TEAM_VERSION,
			true
		);
		wp_localize_script( 'guc-team-admin-sorting', 'gucTeamSorting', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'guc_team_order' ),
			'saved'   => __( 'Order saved!', 'guc-team' ),
			'saving'  => __( 'Saving…', 'guc-team' ),
			'error'   => __( 'Error saving order. Please try again.', 'guc-team' ),
		] );
		wp_enqueue_style( 'guc-team-admin', GUC_TEAM_URL . 'assets/css/admin.css', [], GUC_TEAM_VERSION );
	}

	/**
	 * Return the stored order for a given category slug (or 'all').
	 * Falls back to all published team_member IDs in date order.
	 */
	public static function get_order( $category_slug ) {
		$stored = get_option( self::OPTION_PREFIX . $category_slug, [] );
		return is_array( $stored ) ? array_map( 'intval', $stored ) : [];
	}

	/**
	 * Return all team members sorted according to stored order for a category.
	 * Members not yet in the stored order are appended at the end.
	 *
	 * @param string   $category_slug  'all' or a term slug
	 * @param int[]    $member_ids     The IDs of members belonging to this category
	 */
	public static function sort_members( $category_slug, array $member_ids ) {
		$stored_order = self::get_order( $category_slug );

		$sorted   = [];
		$unsorted = array_flip( $member_ids ); // id => index for O(1) lookup

		foreach ( $stored_order as $id ) {
			if ( isset( $unsorted[ $id ] ) ) {
				$sorted[] = $id;
				unset( $unsorted[ $id ] );
			}
		}

		// Append any members not yet in stored order.
		foreach ( array_keys( $unsorted ) as $id ) {
			$sorted[] = $id;
		}

		return $sorted;
	}

	public static function render_page() {
		// Collect all published team members.
		$all_members = get_posts( [
			'post_type'      => 'team_member',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		] );

		// Build member data map for rendering.
		$member_data = [];
		foreach ( $all_members as $id ) {
			$first     = get_post_meta( $id, '_team_first_name', true );
			$last      = get_post_meta( $id, '_team_last_name', true );
			$function  = get_post_meta( $id, '_team_function', true );
			$photo_id  = (int) get_post_meta( $id, '_team_photo_id', true );
			$thumb_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '';
			$member_data[ $id ] = [
				'name'     => trim( $first . ' ' . $last ),
				'function' => $function,
				'thumb'    => $thumb_url,
			];
		}

		// Collect all categories.
		$terms = get_terms( [
			'taxonomy'   => 'team_category',
			'hide_empty' => false,
		] );

		// Build tabs: first tab is "All".
		$tabs = [ (object) [
			'slug'    => 'all',
			'name'    => __( 'All', 'guc-team' ),
			'members' => self::sort_members( 'all', $all_members ),
		] ];

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_members = get_objects_in_term( $term->term_id, 'team_category' );
				$term_members = array_map( 'intval', (array) $term_members );
				// Restrict to published members only.
				$term_members = array_intersect( $term_members, $all_members );
				$tabs[] = (object) [
					'slug'    => $term->slug,
					'name'    => $term->name,
					'members' => self::sort_members( $term->slug, array_values( $term_members ) ),
				];
			}
		}
		?>
		<div class="wrap guc-sorting-wrap">
			<h1><?php esc_html_e( 'Sort Team Members', 'guc-team' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Drag and drop to set the display order for each category. Each category has its own independent order.', 'guc-team' ); ?>
			</p>

			<?php if ( empty( $all_members ) ) : ?>
				<div class="notice notice-warning inline">
					<p><?php esc_html_e( 'No published team members found. Add some team members first.', 'guc-team' ); ?></p>
				</div>
			<?php else : ?>

			<div class="guc-sorting-tabs">
				<ul class="guc-sorting-tab-nav">
					<?php foreach ( $tabs as $i => $tab ) : ?>
						<li>
							<a href="#guc-tab-<?php echo esc_attr( $tab->slug ); ?>"
							   class="<?php echo $i === 0 ? 'active' : ''; ?>">
								<?php echo esc_html( $tab->name ); ?>
								<span class="guc-member-count">(<?php echo count( $tab->members ); ?>)</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php foreach ( $tabs as $i => $tab ) : ?>
					<div id="guc-tab-<?php echo esc_attr( $tab->slug ); ?>"
						 class="guc-sorting-tab-panel <?php echo $i === 0 ? 'active' : ''; ?>"
						 data-category="<?php echo esc_attr( $tab->slug ); ?>">

						<ul class="guc-sortable-list" id="guc-sort-<?php echo esc_attr( $tab->slug ); ?>">
							<?php foreach ( $tab->members as $member_id ) :
								if ( ! isset( $member_data[ $member_id ] ) ) continue;
								$m = $member_data[ $member_id ];
								?>
								<li class="guc-sortable-item" data-id="<?php echo esc_attr( $member_id ); ?>">
									<span class="guc-drag-handle dashicons dashicons-menu"></span>
									<?php if ( $m['thumb'] ) : ?>
										<img src="<?php echo esc_url( $m['thumb'] ); ?>" alt="" class="guc-member-thumb">
									<?php else : ?>
										<span class="guc-member-thumb guc-member-thumb--placeholder"></span>
									<?php endif; ?>
									<span class="guc-member-info">
										<strong><?php echo esc_html( $m['name'] ); ?></strong>
										<?php if ( $m['function'] ) : ?>
											<span class="guc-member-function"><?php echo esc_html( $m['function'] ); ?></span>
										<?php endif; ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>

						<div class="guc-sorting-actions">
							<button type="button" class="button button-primary guc-save-order"
									data-category="<?php echo esc_attr( $tab->slug ); ?>">
								<?php esc_html_e( 'Save Order', 'guc-team' ); ?>
							</button>
							<span class="guc-save-feedback" style="display:none"></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php endif; ?>
		</div>
		<?php
	}

	public static function ajax_save_order() {
		check_ajax_referer( 'guc_team_order', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'guc-team' ) ] );
		}

		$category = isset( $_POST['category'] ) ? sanitize_key( $_POST['category'] ) : '';
		if ( ! $category ) {
			wp_send_json_error( [ 'message' => __( 'Invalid category.', 'guc-team' ) ] );
		}

		$order = isset( $_POST['order'] ) ? (array) $_POST['order'] : [];
		$order = array_map( 'absint', $order );
		$order = array_filter( $order ); // remove zeros

		update_option( self::OPTION_PREFIX . $category, array_values( $order ), false );

		wp_send_json_success( [ 'message' => __( 'Order saved!', 'guc-team' ) ] );
	}
}
