<?php
namespace WowDevs\Responsive_Visibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Settings {

	private static $hook = '';

	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'settings_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function settings_menu() {
		self::$hook = add_options_page(
			__( 'Responsive Visibility', 'responsive-visibility' ),
			__( 'Responsive Visibility', 'responsive-visibility' ),
			'manage_options',
			'responsive-visibility',
			array( __CLASS__, 'settings_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( $hook !== self::$hook ) {
			return;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$dir = plugin_dir_path( RV_PLUGIN_FILE );

		$css_rel = "assets/css/settings{$min}.css";
		$js_rel  = "assets/js/settings{$min}.js";

		$css_path = $dir . $css_rel;
		$js_path  = $dir . $js_rel;

		wp_register_style(
			'rv-settings',
			plugins_url( $css_rel, RV_PLUGIN_FILE ),
			array(),
			file_exists( $css_path ) ? filemtime( $css_path ) : RV_VERSION
		);

		wp_register_script(
			'rv-settings',
			plugins_url( $js_rel, RV_PLUGIN_FILE ),
			array(),
			file_exists( $js_path ) ? filemtime( $js_path ) : RV_VERSION,
			true
		);

		wp_localize_script( 'rv-settings', 'rvSettings', array(
			'labelPH'      => __( 'e.g. Widescreen', 'responsive-visibility' ),
			'removeTxt'    => __( '✕ Remove', 'responsive-visibility' ),
			'needMax'      => __( 'set a max-width', 'responsive-visibility' ),
			'largest'      => __( '∞ Largest device', 'responsive-visibility' ),
			'untitled'     => __( 'Untitled', 'responsive-visibility' ),
			'multiBlank'   => __( 'Only the largest device can have a blank max-width. Set a value here.', 'responsive-visibility' ),
			'resetConfirm' => __( 'Reset all breakpoints to the defaults (Mobile 767, Tablet 1024, Desktop)? This cannot be undone.', 'responsive-visibility' ),
		) );

		wp_enqueue_style( 'rv-settings' );
		wp_enqueue_script( 'rv-settings' );
	}

	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved = false;
		$reset = false;
		$error = '';

		if ( isset( $_POST['rv_reset_breakpoints'] ) && check_admin_referer( 'rv_save_breakpoints_nonce' ) ) {
			delete_option( 'responsive_visibility_breakpoints' );
			$reset = true;
		} elseif ( isset( $_POST['rv_save_breakpoints'] ) && check_admin_referer( 'rv_save_breakpoints_nonce' ) ) {
			$result = self::process_save();
			if ( true === $result ) {
				$saved = true;
			} else {
				$error = $result;
			}
		}

		$breakpoints = get_option( 'responsive_visibility_breakpoints', Breakpoints::get_defaults() );
		self::render_page( $breakpoints, $saved, $error, $reset );
	}

	private static function process_save() {
		$labels     = isset( $_POST['rv_label'] ) ? (array) wp_unslash( $_POST['rv_label'] ) : array();
		$max_widths = isset( $_POST['rv_max_width'] ) ? (array) wp_unslash( $_POST['rv_max_width'] ) : array();
		$slugs_in   = isset( $_POST['rv_slug'] ) ? (array) wp_unslash( $_POST['rv_slug'] ) : array();

		// Pass 1: reserve every existing (locked) slug so a new row can never steal one.
		$slugs_used = array();
		foreach ( $labels as $i => $label ) {
			if ( '' === sanitize_text_field( $label ) ) {
				continue;
			}
			$existing = isset( $slugs_in[ $i ] ) ? sanitize_key( $slugs_in[ $i ] ) : '';
			if ( '' !== $existing ) {
				$slugs_used[] = $existing;
			}
		}

		// Pass 2: build, reusing locked slugs verbatim, minting only for brand-new rows.
		$breakpoints = array();
		foreach ( $labels as $i => $label ) {
			$label = sanitize_text_field( $label );
			if ( '' === $label ) {
				continue;
			}

			$existing = isset( $slugs_in[ $i ] ) ? sanitize_key( $slugs_in[ $i ] ) : '';
			if ( '' !== $existing ) {
				$slug = $existing;
			} else {
				$slug          = sanitize_title( $label );
				$original_slug = $slug;
				$counter       = 2;
				while ( in_array( $slug, $slugs_used, true ) ) {
					$slug = $original_slug . '-' . $counter;
					++$counter;
				}
				$slugs_used[] = $slug;
			}

			$max_width_raw = trim( isset( $max_widths[ $i ] ) ? $max_widths[ $i ] : '' );
			$max_width     = ( '' === $max_width_raw ) ? null : absint( $max_width_raw );

			$breakpoints[] = array(
				'slug'      => $slug,
				'label'     => $label,
				'max_width' => $max_width,
			);
		}

		if ( count( $breakpoints ) < 1 ) {
			return __( 'You must have at least one breakpoint.', 'responsive-visibility' );
		}

		$null_count = count( array_filter( $breakpoints, function( $bp ) {
			return null === $bp['max_width'];
		} ) );
		if ( $null_count > 1 ) {
			return __( 'Only one breakpoint can have no max-width (the largest device). Please set a max-width value for the others.', 'responsive-visibility' );
		}

		update_option( 'responsive_visibility_breakpoints', Breakpoints::sort( $breakpoints ) );

		return true;
	}

	private static function render_page( array $breakpoints, $saved, $error, $reset = false ) {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Responsive Visibility: Breakpoints', 'responsive-visibility' ); ?></h1>
			<p><?php esc_html_e( 'Define breakpoints to control block visibility on different screen sizes. These values apply site-wide to all blocks using Responsive Visibility.', 'responsive-visibility' ); ?></p>

			<?php if ( $reset ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Breakpoints reset to defaults.', 'responsive-visibility' ); ?></p></div>
			<?php elseif ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Breakpoints saved successfully.', 'responsive-visibility' ); ?></p></div>
			<?php endif; ?>
			<?php if ( $error ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<form method="post" id="rv-settings-form">
				<?php wp_nonce_field( 'rv_save_breakpoints_nonce' ); ?>

				<div id="rv-range-bar" class="rv-bp-bar"></div>

				<table class="wp-list-table widefat fixed striped rv-bp-table">
					<thead>
						<tr>
							<th class="rv-col-label"><?php esc_html_e( 'Label', 'responsive-visibility' ); ?></th>
							<th class="rv-col-max">
								<?php esc_html_e( 'Max Width (px)', 'responsive-visibility' ); ?>
								<span class="dashicons dashicons-editor-help rv-help-icon" title="<?php esc_attr_e( 'Leave blank for the largest breakpoint (no upper limit). Example: Desktop has no max-width.', 'responsive-visibility' ); ?>"></span>
							</th>
							<th class="rv-col-range"><?php esc_html_e( 'Hidden when', 'responsive-visibility' ); ?></th>
							<th class="rv-col-actions"></th>
						</tr>
					</thead>
					<tbody id="rv-breakpoints-body">
						<?php foreach ( $breakpoints as $bp ) : ?>
						<tr class="rv-breakpoint-row">
							<td>
								<input type="hidden" name="rv_slug[]" value="<?php echo esc_attr( $bp['slug'] ); ?>" />
								<input type="text" name="rv_label[]" value="<?php echo esc_attr( $bp['label'] ); ?>" class="regular-text" required placeholder="<?php esc_attr_e( 'e.g. Mobile', 'responsive-visibility' ); ?>" />
							</td>
							<td>
								<input type="number" name="rv_max_width[]" value="<?php echo null !== $bp['max_width'] ? esc_attr( $bp['max_width'] ) : ''; ?>" min="1" max="99999" class="small-text rv-max-width" placeholder="∞" />
								<span class="rv-unit">px</span>
							</td>
							<td class="rv-range-cell"></td>
							<td>
								<button type="button" class="button rv-remove-row"><?php esc_html_e( '✕ Remove', 'responsive-visibility' ); ?></button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p style="margin-top:8px;">
					<button type="button" class="button" id="rv-add-breakpoint"><?php esc_html_e( '+ Add Breakpoint', 'responsive-visibility' ); ?></button>
				</p>

				<p class="description rv-settings-desc">
					<?php esc_html_e( "Breakpoints are sorted automatically by max-width. The breakpoint with no max-width becomes the largest device (e.g., Desktop). Tip: match your theme's breakpoints for seamless integration.", 'responsive-visibility' ); ?>
				</p>

				<p>
					<?php submit_button( __( 'Save Breakpoints', 'responsive-visibility' ), 'primary', 'rv_save_breakpoints', false ); ?>
					<button type="submit" name="rv_reset_breakpoints" class="button button-link-delete rv-reset-btn" formnovalidate>
						<?php esc_html_e( 'Reset to defaults', 'responsive-visibility' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
