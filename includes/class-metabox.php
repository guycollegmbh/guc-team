<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GUC_Team_Metabox {

	public static function init() {
		add_action( 'add_meta_boxes', [ self::class, 'register' ] );
		add_action( 'save_post_team_member', [ self::class, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_media' ] );
	}

	public static function register() {
		add_meta_box(
			'guc_team_details',
			__( 'Team Member Details', 'guc-team' ),
			[ self::class, 'render' ],
			'team_member',
			'normal',
			'high'
		);
	}

	public static function enqueue_media( $hook ) {
		global $post;
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		if ( isset( $post ) && $post->post_type === 'team_member' ) {
			wp_enqueue_media();
			wp_enqueue_style( 'guc-team-admin', GUC_TEAM_URL . 'assets/css/admin.css', [], GUC_TEAM_VERSION );
		}
	}

	public static function render( $post ) {
		wp_nonce_field( 'guc_team_save_details', 'guc_team_nonce' );

		$first_name  = get_post_meta( $post->ID, '_team_first_name', true );
		$last_name   = get_post_meta( $post->ID, '_team_last_name', true );
		$function    = get_post_meta( $post->ID, '_team_function', true );
		$phone       = get_post_meta( $post->ID, '_team_phone', true );
		$email       = get_post_meta( $post->ID, '_team_email', true );
		$description = get_post_meta( $post->ID, '_team_description', true );
		$photo_id    = (int) get_post_meta( $post->ID, '_team_photo_id', true );
		$photo_url   = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : '';
		?>
		<div class="guc-team-metabox">

			<div class="guc-team-row guc-team-row--half">
				<div class="guc-team-field">
					<label for="guc_first_name"><?php esc_html_e( 'First Name', 'guc-team' ); ?> <span class="required">*</span></label>
					<input type="text" id="guc_first_name" name="guc_first_name"
						   value="<?php echo esc_attr( $first_name ); ?>" required>
				</div>
				<div class="guc-team-field">
					<label for="guc_last_name"><?php esc_html_e( 'Last Name', 'guc-team' ); ?> <span class="required">*</span></label>
					<input type="text" id="guc_last_name" name="guc_last_name"
						   value="<?php echo esc_attr( $last_name ); ?>" required>
				</div>
			</div>

			<div class="guc-team-row">
				<div class="guc-team-field">
					<label for="guc_function"><?php esc_html_e( 'Function / Title', 'guc-team' ); ?></label>
					<input type="text" id="guc_function" name="guc_function"
						   value="<?php echo esc_attr( $function ); ?>"
						   placeholder="<?php esc_attr_e( 'e.g. Head of Marketing', 'guc-team' ); ?>">
				</div>
			</div>

			<div class="guc-team-row guc-team-row--half">
				<div class="guc-team-field">
					<label for="guc_phone"><?php esc_html_e( 'Phone', 'guc-team' ); ?></label>
					<input type="text" id="guc_phone" name="guc_phone"
						   value="<?php echo esc_attr( $phone ); ?>"
						   placeholder="+41 71 000 00 00">
				</div>
				<div class="guc-team-field">
					<label for="guc_email"><?php esc_html_e( 'E-Mail', 'guc-team' ); ?></label>
					<input type="email" id="guc_email" name="guc_email"
						   value="<?php echo esc_attr( $email ); ?>">
				</div>
			</div>

			<div class="guc-team-row">
				<div class="guc-team-field">
					<label for="guc_description"><?php esc_html_e( 'Description', 'guc-team' ); ?></label>
					<textarea id="guc_description" name="guc_description" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
				</div>
			</div>

			<div class="guc-team-row">
				<div class="guc-team-field">
					<label><?php esc_html_e( 'Profile Photo', 'guc-team' ); ?></label>
					<div class="guc-team-photo-upload">
						<div class="guc-team-photo-preview" id="guc_photo_preview">
							<?php if ( $photo_url ) : ?>
								<img src="<?php echo esc_url( $photo_url ); ?>" alt="">
							<?php endif; ?>
						</div>
						<input type="hidden" id="guc_photo_id" name="guc_photo_id"
							   value="<?php echo esc_attr( $photo_id ?: '' ); ?>">
						<div class="guc-team-photo-actions">
							<button type="button" class="button" id="guc_upload_photo">
								<?php esc_html_e( 'Select Photo', 'guc-team' ); ?>
							</button>
							<button type="button" class="button" id="guc_remove_photo"
								<?php echo ! $photo_id ? 'style="display:none"' : ''; ?>>
								<?php esc_html_e( 'Remove Photo', 'guc-team' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

		</div>

		<script>
		(function($) {
			var frame;
			$('#guc_upload_photo').on('click', function(e) {
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: '<?php echo esc_js( __( 'Select Profile Photo', 'guc-team' ) ); ?>',
					button: { text: '<?php echo esc_js( __( 'Use this photo', 'guc-team' ) ); ?>' },
					multiple: false,
					library: { type: 'image' }
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#guc_photo_id').val(attachment.id);
					$('#guc_photo_preview').html('<img src="' + attachment.url + '" alt="">');
					$('#guc_remove_photo').show();
				});
				frame.open();
			});
			$('#guc_remove_photo').on('click', function() {
				$('#guc_photo_id').val('');
				$('#guc_photo_preview').html('');
				$(this).hide();
			});
		})(jQuery);
		</script>
		<?php
	}

	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['guc_team_nonce'] ) ||
			 ! wp_verify_nonce( $_POST['guc_team_nonce'], 'guc_team_save_details' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = [
			'guc_first_name'  => '_team_first_name',
			'guc_last_name'   => '_team_last_name',
			'guc_function'    => '_team_function',
			'guc_phone'       => '_team_phone',
			'guc_description' => '_team_description',
		];

		foreach ( $fields as $input => $meta_key ) {
			if ( $input === 'guc_description' ) {
				$value = isset( $_POST[ $input ] ) ? wp_kses_post( wp_unslash( $_POST[ $input ] ) ) : '';
			} else {
				$value = isset( $_POST[ $input ] ) ? sanitize_text_field( wp_unslash( $_POST[ $input ] ) ) : '';
			}
			update_post_meta( $post_id, $meta_key, $value );
		}

		// Email gets extra sanitization.
		$email = isset( $_POST['guc_email'] ) ? sanitize_email( wp_unslash( $_POST['guc_email'] ) ) : '';
		update_post_meta( $post_id, '_team_email', $email );

		// Photo ID must be a positive integer or empty.
		$photo_id = isset( $_POST['guc_photo_id'] ) ? absint( $_POST['guc_photo_id'] ) : 0;
		update_post_meta( $post_id, '_team_photo_id', $photo_id );
	}
}
