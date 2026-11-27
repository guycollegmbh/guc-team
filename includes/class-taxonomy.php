<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GUC_Team_Taxonomy {

	public static function init() {
		add_action( 'init', [ self::class, 'register' ] );

		// "Im Team-Listing ausblenden" option on categories.
		add_action( 'team_category_add_form_fields',  [ self::class, 'field_add' ] );
		add_action( 'team_category_edit_form_fields', [ self::class, 'field_edit' ] );
		add_action( 'created_team_category',          [ self::class, 'save_field' ] );
		add_action( 'edited_team_category',           [ self::class, 'save_field' ] );
	}

	/**
	 * Is this category hidden from the default [team_members] listing?
	 */
	public static function is_hidden( $term_id ) {
		return (bool) get_term_meta( (int) $term_id, 'guc_team_hidden', true );
	}

	public static function field_add() {
		wp_nonce_field( 'guc_team_hidden', 'guc_team_hidden_nonce' );
		?>
		<div class="form-field">
			<label for="guc_team_hidden">
				<input type="checkbox" name="guc_team_hidden" id="guc_team_hidden" value="1">
				<?php esc_html_e( 'Im Team-Listing ausblenden', 'guc-team' ); ?>
			</label>
			<p><?php esc_html_e( 'Erscheint nicht als Filter-Button in [team_members]. Ausgabe nur gezielt über [team_members category="slug"].', 'guc-team' ); ?></p>
		</div>
		<?php
	}

	public static function field_edit( $term ) {
		wp_nonce_field( 'guc_team_hidden', 'guc_team_hidden_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Im Team-Listing ausblenden', 'guc-team' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="guc_team_hidden" value="1" <?php checked( self::is_hidden( $term->term_id ) ); ?>>
					<?php esc_html_e( 'Kategorie nicht als Filter-Button in [team_members] anzeigen.', 'guc-team' ); ?>
				</label>
				<p class="description">
					<?php
					printf(
						/* translators: %s: shortcode with the slug of this category */
						esc_html__( 'Ausgabe nur gezielt über %s', 'guc-team' ),
						'<code>[team_members category="' . esc_html( $term->slug ) . '"]</code>'
					);
					?>
				</p>
			</td>
		</tr>
		<?php
	}

	public static function save_field( $term_id ) {
		if ( ! isset( $_POST['guc_team_hidden_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['guc_team_hidden_nonce'] ) ), 'guc_team_hidden' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		if ( ! empty( $_POST['guc_team_hidden'] ) ) {
			update_term_meta( $term_id, 'guc_team_hidden', 1 );
		} else {
			delete_term_meta( $term_id, 'guc_team_hidden' );
		}
	}

	public static function register() {
		$labels = [
			'name'              => __( 'Team-Kategorien', 'guc-team' ),
			'singular_name'     => __( 'Team-Kategorie', 'guc-team' ),
			'search_items'      => __( 'Kategorien durchsuchen', 'guc-team' ),
			'all_items'         => __( 'Alle Kategorien', 'guc-team' ),
			'edit_item'         => __( 'Kategorie bearbeiten', 'guc-team' ),
			'update_item'       => __( 'Kategorie aktualisieren', 'guc-team' ),
			'add_new_item'      => __( 'Neue Kategorie hinzufügen', 'guc-team' ),
			'new_item_name'     => __( 'Name der neuen Kategorie', 'guc-team' ),
			'menu_name'         => __( 'Kategorien', 'guc-team' ),
			'not_found'         => __( 'Keine Kategorien gefunden.', 'guc-team' ),
			// Otherwise WordPress falls back to its "Schlagwörter" (tags) wording.
			'popular_items'              => __( 'Häufig verwendete Kategorien', 'guc-team' ),
			'separate_items_with_commas' => __( 'Kategorien durch Kommas trennen', 'guc-team' ),
			'add_or_remove_items'        => __( 'Kategorien hinzufügen oder entfernen', 'guc-team' ),
			'choose_from_most_used'      => __( 'Aus den meistgenutzten Kategorien auswählen', 'guc-team' ),
			'back_to_items'              => __( '← Zurück zu den Kategorien', 'guc-team' ),
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
