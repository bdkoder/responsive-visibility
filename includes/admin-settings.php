<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rv_settings_menu() {
	add_options_page(
		__( 'Responsive Visibility', 'responsive-visibility' ),
		__( 'Responsive Visibility', 'responsive-visibility' ),
		'manage_options',
		'responsive-visibility',
		'rv_settings_page'
	);
}
add_action( 'admin_menu', 'rv_settings_menu' );

function rv_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$saved    = false;
	$error    = '';

	if ( isset( $_POST['rv_save_breakpoints'] ) && check_admin_referer( 'rv_save_breakpoints_nonce' ) ) {
		$labels     = isset( $_POST['rv_label'] ) ? (array) $_POST['rv_label'] : array();
		$max_widths = isset( $_POST['rv_max_width'] ) ? (array) $_POST['rv_max_width'] : array();

		$breakpoints = array();
		$slugs_used  = array();

		foreach ( $labels as $i => $label ) {
			$label = sanitize_text_field( $label );
			if ( '' === $label ) {
				continue;
			}

			$slug          = sanitize_title( $label );
			$max_width_raw = trim( isset( $max_widths[ $i ] ) ? $max_widths[ $i ] : '' );
			$max_width     = ( '' === $max_width_raw ) ? null : absint( $max_width_raw );

			// Ensure unique slugs.
			$original_slug = $slug;
			$counter       = 2;
			while ( in_array( $slug, $slugs_used, true ) ) {
				$slug = $original_slug . '-' . $counter;
				$counter++;
			}
			$slugs_used[] = $slug;

			$breakpoints[] = array(
				'slug'      => $slug,
				'label'     => $label,
				'max_width' => $max_width,
			);
		}

		if ( count( $breakpoints ) < 1 ) {
			$error = __( 'You must have at least one breakpoint.', 'responsive-visibility' );
		} else {
			// Sort: nulls (no upper limit) go last; others ascending by max_width.
			usort( $breakpoints, function ( $a, $b ) {
				if ( null === $a['max_width'] && null === $b['max_width'] ) return 0;
				if ( null === $a['max_width'] ) return 1;
				if ( null === $b['max_width'] ) return -1;
				return $a['max_width'] - $b['max_width'];
			} );

			update_option( 'responsive_visibility_breakpoints', $breakpoints );
			$saved = true;
		}
	}

	$breakpoints = get_option( 'responsive_visibility_breakpoints', rv_default_breakpoints() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Responsive Visibility — Breakpoints', 'responsive-visibility' ); ?></h1>
		<p><?php esc_html_e( 'Define breakpoints to control block visibility on different screen sizes. These values apply site-wide to all blocks using Responsive Visibility.', 'responsive-visibility' ); ?></p>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Breakpoints saved successfully.', 'responsive-visibility' ); ?></p></div>
		<?php endif; ?>
		<?php if ( $error ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<form method="post" id="rv-settings-form">
			<?php wp_nonce_field( 'rv_save_breakpoints_nonce' ); ?>

			<table class="wp-list-table widefat fixed striped" style="max-width:680px;">
				<thead>
					<tr>
						<th style="width:38%;"><?php esc_html_e( 'Label', 'responsive-visibility' ); ?></th>
						<th style="width:32%;">
							<?php esc_html_e( 'Max Width (px)', 'responsive-visibility' ); ?>
							<span class="dashicons dashicons-editor-help rv-tip" title="<?php esc_attr_e( 'Leave blank for the largest breakpoint (no upper limit). Example: Desktop has no max-width.', 'responsive-visibility' ); ?>" style="cursor:help;font-size:16px;vertical-align:middle;color:#72777c;"></span>
						</th>
						<th></th>
					</tr>
				</thead>
				<tbody id="rv-breakpoints-body">
					<?php foreach ( $breakpoints as $bp ) : ?>
					<tr class="rv-breakpoint-row">
						<td>
							<input
								type="text"
								name="rv_label[]"
								value="<?php echo esc_attr( $bp['label'] ); ?>"
								class="regular-text"
								required
								placeholder="<?php esc_attr_e( 'e.g. Mobile', 'responsive-visibility' ); ?>"
							/>
						</td>
						<td>
							<input
								type="number"
								name="rv_max_width[]"
								value="<?php echo null !== $bp['max_width'] ? esc_attr( $bp['max_width'] ) : ''; ?>"
								min="1"
								max="99999"
								class="small-text"
								placeholder="∞"
							/> <span style="color:#888;">px</span>
						</td>
						<td>
							<button type="button" class="button rv-remove-row"><?php esc_html_e( '✕ Remove', 'responsive-visibility' ); ?></button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin-top:8px;">
				<button type="button" class="button" id="rv-add-breakpoint">
					<?php esc_html_e( '+ Add Breakpoint', 'responsive-visibility' ); ?>
				</button>
			</p>

			<p class="description" style="max-width:680px;">
				<?php esc_html_e( 'Breakpoints are sorted automatically by max-width. The breakpoint with no max-width becomes the largest device (e.g., Desktop). Tip: match your theme\'s breakpoints for seamless integration.', 'responsive-visibility' ); ?>
			</p>

			<?php submit_button( __( 'Save Breakpoints', 'responsive-visibility' ), 'primary', 'rv_save_breakpoints' ); ?>
		</form>
	</div>

	<script>
	(function () {
		var body     = document.getElementById('rv-breakpoints-body');
		var addBtn   = document.getElementById('rv-add-breakpoint');
		var labelPH  = <?php echo wp_json_encode( __( 'e.g. Widescreen', 'responsive-visibility' ) ); ?>;
		var removeTxt = <?php echo wp_json_encode( __( '✕ Remove', 'responsive-visibility' ) ); ?>;

		function updateRemoveButtons() {
			var rows    = body.querySelectorAll('.rv-breakpoint-row');
			var buttons = body.querySelectorAll('.rv-remove-row');
			buttons.forEach(function (btn) {
				btn.disabled = rows.length <= 1;
			});
		}

		addBtn.addEventListener('click', function () {
			var tr = document.createElement('tr');
			tr.className = 'rv-breakpoint-row';
			tr.innerHTML =
				'<td><input type="text" name="rv_label[]" class="regular-text" required placeholder="' + labelPH + '" /></td>' +
				'<td><input type="number" name="rv_max_width[]" min="1" max="99999" class="small-text" placeholder="∞" value="1400" /> <span style="color:#888;">px</span></td>' +
				'<td><button type="button" class="button rv-remove-row">' + removeTxt + '</button></td>';
			body.appendChild(tr);
			updateRemoveButtons();
			tr.querySelector('input[type="text"]').focus();
		});

		body.addEventListener('click', function (e) {
			if (e.target && e.target.classList.contains('rv-remove-row')) {
				var rows = body.querySelectorAll('.rv-breakpoint-row');
				if (rows.length > 1) {
					e.target.closest('tr').remove();
					updateRemoveButtons();
				}
			}
		});

		updateRemoveButtons();
	})();
	</script>
	<?php
}
