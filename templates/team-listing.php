<?php
/**
 * Template: Team Members Listing
 * Variables available: $wrapper_id, $groups, $instance
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="guc-team-wrapper" id="<?php echo esc_attr( $wrapper_id ); ?>" data-instance="<?php echo esc_attr( $instance ); ?>">

	<!-- Filter Navigation -->
	<nav class="guc-team-filter" role="tablist" aria-label="<?php esc_attr_e( 'Filter team members by category', 'guc-team' ); ?>">
		<?php foreach ( $groups as $i => $group ) : ?>
			<button
				class="guc-team-filter__btn<?php echo $i === 0 ? ' is-active' : ''; ?>"
				data-category="<?php echo esc_attr( $group['slug'] ); ?>"
				role="tab"
				aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
				aria-controls="guc-group-<?php echo esc_attr( $wrapper_id . '-' . $group['slug'] ); ?>"
			>
				<?php echo esc_html( $group['label'] ); ?>
			</button>
		<?php endforeach; ?>
	</nav>

	<!-- Member Groups (one per category, shown/hidden by JS) -->
	<div class="guc-team-groups">
		<?php foreach ( $groups as $i => $group ) : ?>
			<div
				id="guc-group-<?php echo esc_attr( $wrapper_id . '-' . $group['slug'] ); ?>"
				class="guc-team-group<?php echo $i === 0 ? ' is-active' : ''; ?>"
				data-category="<?php echo esc_attr( $group['slug'] ); ?>"
				role="tabpanel"
				aria-hidden="<?php echo $i === 0 ? 'false' : 'true'; ?>"
			>
				<!-- Cards are injected here by frontend.js -->
			</div>
		<?php endforeach; ?>
	</div>

</div>

<?php // Modal is rendered via wp_footer in GUC_Team_Shortcode::render_modal_html() ?>
