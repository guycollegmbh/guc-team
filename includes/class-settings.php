<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GUC_Team_Settings {

	const OPTION_NAME  = 'guc_team_settings';
	const OPTION_GROUP = 'guc_team_settings_group';
	const PAGE_SLUG    = 'guc-team-settings';
	const CAPABILITY   = 'edit_posts'; // same as the Team Members menu and the sorting page

	public static function init() {
		add_action( 'admin_menu', [ self::class, 'add_menu' ] );
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
		add_filter( 'option_page_capability_' . self::OPTION_GROUP, [ self::class, 'capability' ] );
	}

	/**
	 * Capability for saving: options.php requires manage_options unless filtered per option group.
	 */
	public static function capability() {
		return self::CAPABILITY;
	}

	/**
	 * Default values of all plugin settings.
	 * All settings live in one option (array): a new setting needs an entry here,
	 * a line in sanitize() and a field in register_settings().
	 */
	public static function defaults() {
		return [
			'filter_all_label' => '', // empty = built-in "All" label
		];
	}

	/**
	 * Return a single setting, falling back to its default if nothing is stored yet.
	 */
	public static function get( $key ) {
		$stored   = get_option( self::OPTION_NAME, [] );
		$settings = wp_parse_args( is_array( $stored ) ? $stored : [], self::defaults() );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Label of the "All" filter button in the frontend listing.
	 */
	public static function get_filter_all_label() {
		$label = (string) self::get( 'filter_all_label' );

		return $label !== '' ? $label : __( 'All', 'guc-team' );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=team_member',
			__( 'Team Members – Einstellungen', 'guc-team' ),
			__( 'Einstellungen', 'guc-team' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ self::class, 'render_page' ]
		);
	}

	public static function register_settings() {
		register_setting( self::OPTION_GROUP, self::OPTION_NAME, [
			'type'              => 'array',
			'sanitize_callback' => [ self::class, 'sanitize' ],
		] );

		add_settings_section(
			'guc_team_section_filter',
			__( 'Filter', 'guc-team' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'filter_all_label',
			__( 'Filter-Label «All»', 'guc-team' ),
			[ self::class, 'render_text_field' ],
			self::PAGE_SLUG,
			'guc_team_section_filter',
			[
				'key'         => 'filter_all_label',
				'label_for'   => 'guc_team_filter_all_label',
				'placeholder' => __( 'All', 'guc-team' ),
				'description' => __( 'Text des Filter-Buttons, der alle Team Members anzeigt. Leer lassen, um den Standardtext «All» zu verwenden.', 'guc-team' ),
			]
		);
	}

	/**
	 * Sanitize the settings array before it is stored. Unknown keys are dropped.
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : [];

		return [
			'filter_all_label' => isset( $input['filter_all_label'] ) ? sanitize_text_field( $input['filter_all_label'] ) : '',
		];
	}

	public static function enqueue( $hook ) {
		if ( $hook !== 'team_member_page_' . self::PAGE_SLUG ) {
			return;
		}
		wp_enqueue_script(
			'guc-team-admin-settings',
			GUC_TEAM_URL . 'assets/js/admin-settings.js',
			[],
			GUC_TEAM_VERSION,
			true
		);
		wp_localize_script( 'guc-team-admin-settings', 'gucTeamSettings', [
			'copied'    => __( 'Kopiert!', 'guc-team' ),
			'copyError' => __( 'Kopieren fehlgeschlagen. Bitte manuell kopieren.', 'guc-team' ),
		] );
		wp_enqueue_style( 'guc-team-admin', GUC_TEAM_URL . 'assets/css/admin.css', [], GUC_TEAM_VERSION );
	}

	public static function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Team Members – Einstellungen', 'guc-team' ); ?></h1>

			<?php settings_errors(); ?>

			<h2><?php esc_html_e( 'Shortcode', 'guc-team' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="guc_team_shortcode"><?php esc_html_e( 'Team-Listing', 'guc-team' ); ?></label>
					</th>
					<td>
						<div class="guc-shortcode-copy">
							<input type="text" id="guc_team_shortcode" class="regular-text code"
								   value="[team_members]" readonly>
							<button type="button" class="button guc-copy-button">
								<?php esc_html_e( 'Kopieren', 'guc-team' ); ?>
							</button>
							<span class="guc-copy-feedback" aria-live="polite"></span>
						</div>
						<p class="description">
							<?php esc_html_e( 'Diesen Shortcode auf einer Seite oder in einem Beitrag einfügen, um das Team-Listing auszugeben.', 'guc-team' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Generic text field for a setting.
	 *
	 * @param array $args  'key' (settings key), 'label_for' (input ID), optional 'placeholder' and 'description'.
	 */
	public static function render_text_field( $args ) {
		?>
		<input type="text" class="regular-text"
			   id="<?php echo esc_attr( $args['label_for'] ); ?>"
			   name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['key'] . ']' ); ?>"
			   value="<?php echo esc_attr( self::get( $args['key'] ) ); ?>"
			   placeholder="<?php echo esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' ); ?>">
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}
}
