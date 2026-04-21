<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GUC_Team_Shortcode {

	public static function init() {
		add_shortcode( 'team_members', [ self::class, 'render' ] );
	}

	/**
	 * [team_members] shortcode.
	 *
	 * @param array $atts  Shortcode attributes (reserved for future use).
	 */
	public static function render( $atts ) {
		self::enqueue_assets();

		// Fetch all published members.
		$all_ids = get_posts( [
			'post_type'      => 'team_member',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		if ( empty( $all_ids ) ) {
			return '<p class="guc-team-empty">' . esc_html__( 'No team members found.', 'guc-team' ) . '</p>';
		}

		// Build full member data array (used for JSON + template).
		$members_data = [];
		foreach ( $all_ids as $id ) {
			$first       = get_post_meta( $id, '_team_first_name', true );
			$last        = get_post_meta( $id, '_team_last_name', true );
			$function    = get_post_meta( $id, '_team_function', true );
			$phone       = get_post_meta( $id, '_team_phone', true );
			$email       = get_post_meta( $id, '_team_email', true );
			$description = get_post_meta( $id, '_team_description', true );
			$photo_id    = (int) get_post_meta( $id, '_team_photo_id', true );

			// Listing image (cropped square, used in grid).
			$photo_grid  = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium_large' ) : '';
			// Modal image (larger, used in lightbox).
			$photo_modal = $photo_id ? wp_get_attachment_image_url( $photo_id, 'large' ) : '';

			// Terms for this member.
			$terms     = get_the_terms( $id, 'team_category' );
			$term_slugs = [];
			if ( $terms && ! is_wp_error( $terms ) ) {
				$term_slugs = wp_list_pluck( $terms, 'slug' );
			}

			$members_data[ $id ] = [
				'id'          => $id,
				'firstName'   => $first,
				'lastName'    => $last,
				'fullName'    => trim( $first . ' ' . $last ),
				'function'    => $function,
				'phone'       => $phone,
				'email'       => $email,
				'description' => $description,
				'photoGrid'   => $photo_grid,
				'photoModal'  => $photo_modal,
				'categories'  => $term_slugs,
			];
		}

		// Fetch categories.
		$terms = get_terms( [
			'taxonomy'   => 'team_category',
			'hide_empty' => true,
		] );

		// Build groups with sorted IDs.
		$groups = [];

		// "All" group.
		$all_sorted = GUC_Team_Admin_Sorting::sort_members( 'all', $all_ids );
		$groups[]   = [
			'slug'    => 'all',
			'label'   => __( 'All', 'guc-team' ),
			'members' => $all_sorted,
		];

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_members = get_objects_in_term( $term->term_id, 'team_category' );
				$term_members = array_map( 'intval', (array) $term_members );
				$term_members = array_values( array_intersect( $term_members, $all_ids ) );
				$sorted       = GUC_Team_Admin_Sorting::sort_members( $term->slug, $term_members );

				$groups[] = [
					'slug'    => $term->slug,
					'label'   => $term->name,
					'members' => $sorted,
				];
			}
		}

		// Pass data to JS.
		$js_data = [
			'members' => array_values( $members_data ),
			'groups'  => $groups,
			'i18n'    => [
				'close'    => __( 'Close', 'guc-team' ),
				'phone'    => __( 'Phone', 'guc-team' ),
				'email'    => __( 'Email', 'guc-team' ),
				'prev'     => __( 'Previous', 'guc-team' ),
				'next'     => __( 'Next', 'guc-team' ),
				'noPhoto'  => __( 'No photo available', 'guc-team' ),
			],
		];

		// Unique instance ID to support multiple shortcodes on same page.
		static $instance = 0;
		$instance++;
		$wrapper_id = 'guc-team-' . $instance;

		wp_localize_script( 'guc-team-frontend', 'gucTeamData_' . $instance, $js_data );

		ob_start();
		include GUC_TEAM_PATH . 'templates/team-listing.php';
		return ob_get_clean();
	}

	private static function enqueue_assets() {
		static $enqueued = false;
		if ( $enqueued ) {
			return;
		}
		$enqueued = true;

		wp_enqueue_style(
			'guc-team-frontend',
			GUC_TEAM_URL . 'assets/css/frontend.css',
			[],
			GUC_TEAM_VERSION
		);
		wp_enqueue_script(
			'guc-team-frontend',
			GUC_TEAM_URL . 'assets/js/frontend.js',
			[],
			GUC_TEAM_VERSION,
			true
		);

		// Output the shared modal HTML once in wp_footer, guaranteed after all shortcode renders.
		add_action( 'wp_footer', [ self::class, 'render_modal_html' ], 5 );
	}

	public static function render_modal_html() {
		?>
		<div class="guc-team-modal" id="guc-team-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Team member details', 'guc-team' ); ?>" hidden>
			<div class="guc-team-modal__backdrop"></div>
			<div class="guc-team-modal__container">

				<button class="guc-team-modal__close" aria-label="<?php esc_attr_e( 'Close', 'guc-team' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
						<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/>
					</svg>
				</button>

				<button class="guc-team-modal__nav guc-team-modal__nav--prev" aria-label="<?php esc_attr_e( 'Previous member', 'guc-team' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
						<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
					</svg>
				</button>

				<div class="guc-team-modal__content">
					<div class="guc-team-modal__photo">
						<img src="" alt="" class="guc-team-modal__img">
					</div>
					<div class="guc-team-modal__body">
						<h2 class="guc-team-modal__name"></h2>
						<p class="guc-team-modal__function"></p>
						<div class="guc-team-modal__contacts">
							<a href="#" class="guc-team-modal__phone guc-team-modal__contact-link">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
									<path fill="currentColor" d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/>
								</svg>
								<span class="guc-team-modal__phone-text"></span>
							</a>
							<a href="#" class="guc-team-modal__email guc-team-modal__contact-link">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
									<path fill="currentColor" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
								</svg>
								<span class="guc-team-modal__email-text"></span>
							</a>
						</div>
						<div class="guc-team-modal__description"></div>
					</div>
				</div>

				<button class="guc-team-modal__nav guc-team-modal__nav--next" aria-label="<?php esc_attr_e( 'Next member', 'guc-team' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
						<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
					</svg>
				</button>

			</div>
		</div>
		<?php
	}
}
