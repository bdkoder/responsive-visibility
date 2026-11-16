<?php
/**
 * WowDevs Offer banner: one sale banner in wp-admin for every wowDevs plugin.
 *
 * Schedule, messages and links in offer.json (same folder), no remote request. Shipping a change: edit, bump VERSION, the class name and the
 * call at the end of loader.php together, then copy the folder to every plugin.
 * Spec (rules R1 to R5): .ai/features/wowdevs-offer/README.md
 *
 * @package WowDevs_Offer
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace -- Unique global prefix + version; shared by several plugins.

/**
 * Loaded only by wowdevs_offer_boot(), after its class_exists() check. Global name with a unique
 * prefix and the version, so copies in several plugins never collide.
 */
class WowDevs_Offer_1_0_0 {

	const VERSION      = '1.0.0';
	const META         = 'wowdevs_offer';
	const NONCE        = 'wowdevs_offer';
	const LINK_DOMAINS = [ 'skyaddons.com', 'skysliders.com', 'wowdevs.com' ];

	/**
	 * Registered products: product key => args.
	 *
	 * @var array
	 */
	private static $products = [];

	/**
	 * Start. Called once by wowdevs_offer_boot().
	 *
	 * @param array $products Registered products.
	 */
	public static function boot( array $products ) {
		self::$products = $products;

		add_action( 'wp_ajax_wowdevs_offer_close', [ __CLASS__, 'ajax' ] );

		if ( is_admin() && ! wp_doing_ajax() ) {
			add_action( 'admin_notices', [ __CLASS__, 'render' ], 5 );
		}
	}

	/**
	 * Print the banner on the Plugins screen and the plugins' own pages; on the Dashboard only for waves
	 * marked `dashboard` in offer.json.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) {
			return;
		}

		$screen = self::screen();

		if ( '' === $screen || ! self::config() ) {
			return;
		}

		$pick = self::pick();

		if ( ! $pick ) {
			return;
		}

		static $done = false;

		if ( $done ) {
			return;
		}

		$found = self::decide( time(), self::state() );

		if ( ! $found || ( 'dashboard' === $screen && ! $found['dashboard'] ) ) {
			return;
		}

		$link = add_query_arg(
			[
				'utm_source'   => $pick[2],
				'utm_medium'   => 'wp-admin',
				'utm_campaign' => $found['id'],
				'utm_content'  => $screen,
			],
			$pick[1]
		);

		$all = add_query_arg(
			[
				'utm_source'   => $pick[2],
				'utm_medium'   => 'wp-admin',
				'utm_campaign' => $found['id'],
				'utm_content'  => $screen . '-all-deals',
			],
			self::config()['all_deals'][1]
		);

		$done = true;
		self::print_html( $found, $pick[0], $link, $all );
	}

	/**
	 * AJAX: the user closed a wave (`close`) or clicked the button (`claim`).
	 */
	public static function ajax() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$id    = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		$claim = ! empty( $_POST['claim'] );

		// Only ids of waves that exist in offer.json are stored.
		$campaign = self::campaign_of( $id );

		if ( '' === $campaign ) {
			wp_send_json_error( null, 400 );
		}

		$state = self::state();

		if ( $claim ) {
			$state['claimed'][] = $campaign;
		} else {
			$state['closed'][] = $id;
			$state['at']       = time();
		}

		$state['closed']  = array_slice( array_values( array_unique( $state['closed'] ) ), -40 );
		$state['claimed'] = array_slice( array_values( array_unique( $state['claimed'] ) ), -10 );
		update_user_meta( get_current_user_id(), self::META, $state );

		wp_send_json_success();
	}

	/**
	 * Campaign id of a known wave id ('bf26-live' → 'bf26'), or '' when the id is not one of ours.
	 *
	 * @param string $id Wave id.
	 *
	 * @return string
	 */
	private static function campaign_of( $id ) {
		$config = self::config();

		if ( ! $config ) {
			return '';
		}

		foreach ( $config['campaigns'] as $campaign ) {
			foreach ( $config['waves'] as $wave ) {
				if ( $campaign['id'] . '-' . $wave['key'] === $id ) {
					return $campaign['id'];
				}
			}
		}

		return '';
	}

	/**
	 * The rules. Pure: same time + same state + same offer.json = same answer.
	 *
	 * @param int   $now   Current time.
	 * @param array $state User state { closed, at, claimed }.
	 *
	 * @return array|null { id, campaign, wave, title, line, dashboard, countdown, end } or null.
	 */
	public static function decide( $now, array $state ) {
		$config = self::config();

		if ( ! $config ) {
			return null;
		}

		// R1: quiet days after any close. Only a wave marked `breaks_quiet` (the real last day) may break them.
		$quiet = $state['at'] && $now < $state['at'] + $config['rules']['quiet_days'] * DAY_IN_SECONDS;

		foreach ( $config['campaigns'] as $campaign ) {
			$start = self::day( $campaign['start'] );
			$end   = self::day( $campaign['end'] ) + DAY_IN_SECONDS;

			if ( $now < $start || $now >= $end ) {
				continue;
			}

			// R4: clicked the button. R5: closed too often.
			$closes = 0;

			foreach ( $state['closed'] as $closed ) {
				if ( 0 === strpos( $closed, $campaign['id'] . '-' ) ) {
					++$closes;
				}
			}

			if ( in_array( $campaign['id'], $state['claimed'], true ) || $closes >= $config['rules']['max_closes'] ) {
				return null;
			}

			$best  = null;
			$times = self::schedule( $campaign );

			foreach ( $config['waves'] as $wave ) {
				$id   = $campaign['id'] . '-' . $wave['key'];
				$from = $times[ $wave['key'] ];

				// R2: a closed wave never returns. R3: the latest started wave wins (same date: first listed).
				if ( ( $quiet && ! $wave['breaks_quiet'] ) || $from > $now || in_array( $id, $state['closed'], true ) || ( $best && $from <= $best['from'] ) ) {
					continue;
				}

				$best = $wave + [
					'id'   => $id,
					'from' => $from,
				];
			}

			if ( ! $best ) {
				return null;
			}

			return [
				'id'        => $best['id'],
				'campaign'  => $campaign,
				'wave'      => $best['key'],
				'title'     => $best['title'],
				'line'      => $best['line'],
				'dashboard' => $best['dashboard'],
				'countdown' => $best['countdown'],
				'end'       => $end,
			];
		}

		return null;
	}

	/**
	 * Start time of each wave of a campaign: key => timestamp.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return int[]
	 */
	public static function schedule( array $campaign ) {
		$config = self::config();
		$start  = self::day( $campaign['start'] );
		$end    = self::day( $campaign['end'] ) + DAY_IN_SECONDS;
		$out    = [];

		foreach ( $config ? $config['waves'] : [] as $wave ) {
			$out[ $wave['key'] ] = $wave['offset'] >= 0 ? $start + $wave['offset'] * DAY_IN_SECONDS : $end + $wave['offset'] * DAY_IN_SECONDS;
		}

		return $out;
	}

	/**
	 * The checked offer.json, or null when there is none or it is broken (then no banner, no error).
	 *
	 * Every plugin ships a copy; the copy with the highest `revision` wins, so a newer offer in any
	 * plugin is used even when the class itself did not change.
	 *
	 * @return array|null
	 */
	public static function config() {
		static $config = false;

		if ( false !== $config ) {
			return $config;
		}

		$config = null;
		$dirs   = [ __DIR__ ];

		if ( ! empty( $GLOBALS['wowdevs_offer']['versions'] ) && is_array( $GLOBALS['wowdevs_offer']['versions'] ) ) {
			foreach ( $GLOBALS['wowdevs_offer']['versions'] as $entry ) {
				$dirs[] = dirname( (string) $entry[0] );
			}
		}

		foreach ( array_unique( $dirs ) as $dir ) {
			$file = $dir . '/offer.json';

			if ( ! is_readable( $file ) || filesize( $file ) > 32768 ) {
				continue;
			}

			$checked = self::check( json_decode( (string) file_get_contents( $file ), true ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file in this folder.

			if ( $checked && ( ! $config || $checked['revision'] > $config['revision'] ) ) {
				$config = $checked;
			}
		}

		return $config;
	}

	/**
	 * Check offer.json. Anything required missing or wrong → null.
	 *
	 * @param mixed $raw Decoded JSON.
	 *
	 * @return array|null
	 */
	private static function check( $raw ) {
		if ( ! is_array( $raw ) || empty( $raw['campaigns'] ) || empty( $raw['waves'] ) || empty( $raw['products'] ) || ! is_array( $raw['campaigns'] ) || ! is_array( $raw['waves'] ) || ! is_array( $raw['products'] ) ) {
			return null;
		}

		$rules  = isset( $raw['rules'] ) && is_array( $raw['rules'] ) ? $raw['rules'] : [];
		$labels = isset( $raw['labels'] ) && is_array( $raw['labels'] ) ? $raw['labels'] : [];
		$out    = [
			'revision'  => isset( $raw['revision'] ) ? absint( $raw['revision'] ) : 0,
			'rules'     => [
				'quiet_days' => isset( $rules['quiet_days'] ) ? min( 30, absint( $rules['quiet_days'] ) ) : 3,
				'max_closes' => isset( $rules['max_closes'] ) ? max( 1, min( 10, absint( $rules['max_closes'] ) ) ) : 3,
			],
			'labels'    => [
				'button'  => self::text( $labels, 'button', 'Claim Deal' ),
				'trust'   => self::text( $labels, 'trust', '' ),
				'ends_in' => self::text( $labels, 'ends_in', 'Ends in' ),
			],
			'campaigns' => [],
			'waves'     => [],
			'products'  => [],
			'bundle'    => isset( $raw['bundle'] ) ? self::target( $raw['bundle'] ) : null,
			'all_deals' => self::all_deals( isset( $raw['all_deals'] ) ? $raw['all_deals'] : null ),
		];

		foreach ( $raw['campaigns'] as $item ) {
			if ( ! is_array( $item ) || empty( $item['id'] ) || ! is_string( $item['id'] ) || ! preg_match( '/^[a-z0-9]{1,20}$/', $item['id'] ) ) {
				return null;
			}

			$start = isset( $item['start'] ) && is_string( $item['start'] ) ? $item['start'] : '';
			$end   = isset( $item['end'] ) && is_string( $item['end'] ) ? $item['end'] : '';
			$pct   = isset( $item['max_pct'] ) ? absint( $item['max_pct'] ) : 0;

			if ( ! self::is_date( $start ) || ! self::is_date( $end ) || $end < $start || $pct < 1 || $pct > 90 ) {
				return null;
			}

			$accent = isset( $item['accent'] ) && is_string( $item['accent'] ) ? sanitize_hex_color( $item['accent'] ) : '';

			$out['campaigns'][] = [
				'id'      => $item['id'],
				'name'    => self::text( $item, 'name', 'Holiday' ),
				'start'   => $start,
				'end'     => $end,
				'max_pct' => $pct,
				'accent'  => $accent ? $accent : '#00ffff',
				// Optional code with a copy button; empty = no code box (same field as the website banner).
				'coupon'  => isset( $item['coupon'] ) && is_string( $item['coupon'] ) && preg_match( '/^[A-Za-z0-9_-]{1,30}$/', $item['coupon'] ) ? $item['coupon'] : '',
			];
		}

		foreach ( $raw['waves'] as $item ) {
			if ( ! is_array( $item ) || empty( $item['key'] ) || ! is_string( $item['key'] ) || ! preg_match( '/^[a-z]{1,12}$/', $item['key'] ) || ! isset( $item['offset'] ) || ! is_int( $item['offset'] ) || abs( $item['offset'] ) > 60 ) {
				return null;
			}

			$out['waves'][] = [
				'key'          => $item['key'],
				'offset'       => $item['offset'],
				'title'        => self::text( $item, 'title', '' ),
				'line'         => self::text( $item, 'line', '' ),
				'dashboard'    => ! empty( $item['dashboard'] ),
				// Honest by code: a countdown only on the last day, so it never shows more than 24 hours.
				'countdown'    => ! empty( $item['countdown'] ) && -1 === $item['offset'],
				'breaks_quiet' => ! empty( $item['breaks_quiet'] ),
			];
		}

		foreach ( $raw['products'] as $key => $item ) {
			$target = self::target( $item );

			if ( $target && is_string( $key ) && preg_match( '/^[a-z0-9-]{1,40}$/', $key ) ) {
				$out['products'][ $key ] = $target;
			}
		}

		return $out['products'] ? $out : null;
	}

	/**
	 * The "all deals" link: always on wowdevs.com, falls back to its home page.
	 *
	 * @param mixed $item Raw item.
	 *
	 * @return array [ label, url ].
	 */
	private static function all_deals( $item ) {
		$target = self::target( $item );
		$host   = $target ? strtolower( (string) wp_parse_url( $target[1], PHP_URL_HOST ) ) : '';
		$label  = $target && '' !== $target[0] ? $target[0] : 'View all deals';

		return in_array( $host, [ 'wowdevs.com', 'www.wowdevs.com' ], true ) ? [ $label, $target[1] ] : [ $label, 'https://wowdevs.com/' ];
	}

	/**
	 * True for a real Y-m-d date.
	 *
	 * @param string $date Date.
	 *
	 * @return bool
	 */
	private static function is_date( $date ) {
		$time = date_create_immutable_from_format( '!Y-m-d', $date );

		return $time && $time->format( 'Y-m-d' ) === $date;
	}

	/**
	 * A [ name, url ] pair with an https link on one of our domains, or null.
	 *
	 * @param mixed $item Raw item.
	 *
	 * @return array|null
	 */
	private static function target( $item ) {
		if ( ! is_array( $item ) || empty( $item['url'] ) || ! is_string( $item['url'] ) ) {
			return null;
		}

		$url  = esc_url_raw( $item['url'], [ 'https' ] );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		foreach ( self::LINK_DOMAINS as $domain ) {
			if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return [ self::text( $item, 'name', '' ), $url ];
			}
		}

		return null;
	}

	/**
	 * Plain text field, at most 90 characters.
	 *
	 * @param array  $raw      Source.
	 * @param string $key      Field.
	 * @param string $fallback Default.
	 *
	 * @return string
	 */
	private static function text( array $raw, $key, $fallback ) {
		$value = isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
		$value = '' === $value ? $fallback : $value;

		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 90 ) : substr( $value, 0, 90 );
	}

	/**
	 * Claim Deal target: [ name, url, utm source ], or null.
	 *
	 * @return array|null
	 */
	private static function pick() {
		$config = self::config();
		$known  = array_intersect_key( $config['products'], self::$products );

		// One wowDevs plugin installed → its own pricing page. Two or more → wowdevs.com, on every screen.
		if ( 1 === count( $known ) ) {
			$key = key( $known );

			return [ $known[ $key ][0], $known[ $key ][1], $key ];
		}

		return $known && $config['bundle'] ? [ $config['bundle'][0], $config['bundle'][1], 'bundle' ] : null;
	}

	/**
	 * 'dashboard', 'plugins', the product key of a plugin's own page, or '' elsewhere.
	 *
	 * @return string
	 */
	private static function screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && in_array( $screen->id, [ 'dashboard', 'plugins' ], true ) ) {
			return $screen->id;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( '' === $page ) {
			return '';
		}

		foreach ( self::$products as $key => $args ) {
			if ( ! empty( $args['page'] ) && $page === $args['page'] ) {
				return (string) $key;
			}
		}

		return '';
	}

	/**
	 * User state { closed: wave ids, at: last close time, claimed: campaign ids }.
	 *
	 * @return array
	 */
	private static function state() {
		$state = get_user_meta( get_current_user_id(), self::META, true );

		$strings = function ( $key ) use ( $state ) {
			return isset( $state[ $key ] ) && is_array( $state[ $key ] ) ? array_values( array_filter( $state[ $key ], 'is_string' ) ) : [];
		};

		return [
			'closed'  => $strings( 'closed' ),
			'at'      => isset( $state['at'] ) && is_numeric( $state['at'] ) ? (int) $state['at'] : 0,
			'claimed' => $strings( 'claimed' ),
		];
	}

	/**
	 * Midnight of a Y-m-d date in the site's timezone.
	 *
	 * @param string $date Y-m-d.
	 *
	 * @return int
	 */
	private static function day( $date ) {
		$time = date_create_immutable( $date . ' 00:00:00', wp_timezone() );

		return $time ? $time->getTimestamp() : PHP_INT_MAX;
	}

	/**
	 * Banner markup, styles and script.
	 *
	 * @param array  $found Result of decide().
	 * @param string $name  Product or bundle name.
	 * @param string $link  Main button link (the product or the bundle).
	 * @param string $all   "All deals" link (wowdevs.com).
	 */
	private static function print_html( array $found, $name, $link, $all ) {
		$campaign = $found['campaign'];
		$labels   = self::config()['labels'];
		$accent   = sanitize_hex_color( $campaign['accent'] ) ? $campaign['accent'] : '#00ffff';
		$tokens   = [ '{product}', '{name}' ];
		$values   = [ $name, $campaign['name'] ];
		$title    = str_replace( $tokens, $values, $found['title'] );
		$subtitle = str_replace( $tokens, $values, $found['line'] );
		?>
<div class="notice wdo wdo--<?php echo esc_attr( $found['wave'] ); ?>" style="--wdo-accent:<?php echo esc_attr( $accent ); ?>" data-end="<?php echo esc_attr( $found['end'] ); ?>" data-id="<?php echo esc_attr( $found['id'] ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( self::NONCE ) ); ?>">
	<div class="wdo__glow"></div>
	<div class="wdo__content">
		<div class="wdo__sale">
			<div class="wdo__pct"><span class="wdo__upto">Up to</span><?php echo esc_html( $campaign['max_pct'] ); ?>%<span class="wdo__off">OFF</span></div>
			<div class="wdo__info">
				<div class="wdo__title"><?php echo esc_html( $title ); ?></div>
				<div class="wdo__sub"><?php echo esc_html( $subtitle ); ?></div>
			</div>
		</div>
		<?php if ( $found['countdown'] ) : ?>
			<div class="wdo__count">
				<span class="wdo__count-lbl"><?php echo esc_html( $labels['ends_in'] ); ?></span>
				<div class="wdo__timer" aria-hidden="true"></div>
			</div>
		<?php endif; ?>
		<div class="wdo__cta">
			<div class="wdo__actions">
				<?php if ( '' !== $campaign['coupon'] ) : ?>
					<button type="button" class="wdo__code" data-code="<?php echo esc_attr( $campaign['coupon'] ); ?>" aria-label="<?php echo esc_attr( 'Copy code ' . $campaign['coupon'] ); ?>"><span class="wdo__code-lbl">Use code</span><span class="wdo__code-val"><?php echo esc_html( $campaign['coupon'] ); ?></span></button>
				<?php endif; ?>
				<a class="wdo__btn" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $labels['button'] ); ?> <span class="wdo__arrow" aria-hidden="true">&rarr;</span></a>
				<a class="wdo__btn wdo__btn--ghost" href="<?php echo esc_url( $all ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( self::config()['all_deals'][0] ); ?></a>
			</div>
			<?php if ( '' !== $labels['trust'] ) : ?>
				<span class="wdo__trust"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg><?php echo esc_html( $labels['trust'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	<button type="button" class="wdo__close" aria-label="Dismiss this offer">&times;</button>
</div>
<style>
.wdo{--wdo-accent:#0ff;position:relative;margin:16px 20px 16px 0;padding:0;border:1px solid rgba(255,255,255,.08);border-radius:12px;background:linear-gradient(135deg,#1c1c38 0%,#121226 100%);overflow:hidden;box-shadow:0 6px 24px rgba(18,18,38,.18)}
.wrap .wdo{margin:16px 0}
.wdo__glow{position:absolute;top:-60%;inset-inline-start:-10%;width:45%;height:220%;z-index:0;opacity:.18;filter:blur(40px);background:var(--wdo-accent);border-radius:50%;animation:wdoGlow 4s infinite ease-in-out;pointer-events:none}
.wdo__content{position:relative;z-index:1;display:flex;align-items:center;gap:28px;padding-block:18px;padding-inline:28px 56px}
.wdo__sale{display:flex;flex:1;align-items:center;gap:24px;min-width:0}
.wdo__pct{position:relative;flex:none;padding-top:10px;font-size:44px;font-weight:800;line-height:1;letter-spacing:-.02em;color:var(--wdo-accent);text-shadow:0 0 18px color-mix(in srgb,var(--wdo-accent) 45%,transparent)}
.wdo__upto{position:absolute;top:0;left:1px;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;text-shadow:none;opacity:.75}
.wdo__off{margin-left:5px;font-size:15px;font-weight:800;letter-spacing:.06em}
.wdo__info{min-width:0;padding-inline-start:24px;border-inline-start:1px solid rgba(255,255,255,.12);line-height:1.35}
.wdo__title{font-size:22px;font-weight:800;line-height:1.25;letter-spacing:-.01em;color:#fff}
.wdo__sub{margin-top:3px;font-size:13.5px;color:rgba(255,255,255,.7)}
.wdo__count{display:flex;flex:none;flex-direction:column;align-items:center;gap:6px}
.wdo__count-lbl{font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--wdo-accent)}
.wdo__timer{display:flex;gap:6px}
.wdo__seg{min-width:46px;padding:7px 4px 6px;border:1px solid color-mix(in srgb,var(--wdo-accent) 55%,transparent);border-radius:8px;background:rgba(255,255,255,.05);text-align:center;animation:wdoBeat 1.2s infinite ease-in-out}
.wdo__seg b{display:block;font-size:18px;font-weight:800;line-height:1;color:#fff;font-variant-numeric:tabular-nums}
.wdo__seg i{display:block;margin-top:4px;font-size:9px;font-style:normal;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.55)}
.wdo__cta{display:flex;flex:none;flex-direction:column;align-items:center;gap:8px}
.wdo__actions{display:flex;align-items:center;gap:8px}
.wdo__code{display:inline-flex;flex-direction:column;align-items:center;gap:2px;padding:6px 12px;border:1.5px dashed var(--wdo-accent);border-radius:8px;background:rgba(255,255,255,.05);color:var(--wdo-accent);font:inherit;line-height:1.1;cursor:pointer}
.wdo__code:hover,.wdo__code:focus-visible{background:rgba(255,255,255,.1);outline:0}
.wdo__code-lbl{font-size:9px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:rgba(255,255,255,.6)}
.wdo__code-val{font-size:14px;font-weight:800;letter-spacing:.12em}
.wdo__btn{display:inline-block;padding:11px 22px;border-radius:8px;background:var(--wdo-accent);color:#121226!important;font-size:14px;font-weight:700;line-height:1.2;text-decoration:none!important;box-shadow:0 4px 16px color-mix(in srgb,var(--wdo-accent) 35%,transparent);transition:transform .15s,box-shadow .15s}
.wdo__btn:hover,.wdo__btn:focus-visible{box-shadow:0 6px 22px color-mix(in srgb,var(--wdo-accent) 55%,transparent);outline:0}
.wdo__btn:focus-visible{outline:2px solid #fff;outline-offset:2px}
.wdo__arrow{display:inline-block}
[dir=rtl] .wdo__arrow{transform:scaleX(-1)}
.wdo__btn--ghost{background:transparent;color:#fff!important;font-weight:600;border:1px solid rgba(255,255,255,.28);box-shadow:none;padding:10px 16px}
.wdo__btn--ghost:hover,.wdo__btn--ghost:focus-visible{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.5);box-shadow:none}
.wdo__trust{display:flex;align-items:center;gap:5px;font-size:11.5px;color:rgba(255,255,255,.6)}
.wdo__trust svg{flex:none;color:var(--wdo-accent)}
.wdo__close{position:absolute;top:50%;inset-inline-end:14px;z-index:2;width:30px;height:30px;margin-top:-15px;padding:0;border:0;border-radius:50%;background:transparent;color:rgba(255,255,255,.45);font-size:22px;line-height:30px;cursor:pointer;transition:background-color .15s,color .15s}
.wdo__close:hover,.wdo__close:focus-visible{background:rgba(255,255,255,.1);color:#fff;outline:0}
@keyframes wdoGlow{0%,100%{opacity:.14}50%{opacity:.24}}
@keyframes wdoBeat{0%,100%{box-shadow:0 0 0 transparent}50%{box-shadow:0 0 12px color-mix(in srgb,var(--wdo-accent) 50%,transparent)}}
@media (prefers-reduced-motion:reduce){.wdo__glow,.wdo__seg{animation:none}}
@media (max-width:1100px){.wdo__content{flex-wrap:wrap;gap:16px 24px}.wdo__sale{flex-basis:100%}}
@media (max-width:782px){.wdo{margin:12px 10px 12px 0}.wdo__content{padding-block:16px;padding-inline:16px 44px}.wdo__sale{gap:14px}.wdo__pct{font-size:34px}.wdo__info{padding-inline-start:14px}.wdo__title{font-size:18px}.wdo__close{top:10px;margin-top:0}}
</style>
<script>
(function(){
	var boxes=document.querySelectorAll('.wdo:not([data-ready])');
	function pad(n){return(n<10?'0':'')+n;}
	function seg(v,l){return'<div class="wdo__seg"><b>'+pad(v)+'</b><i>'+l+'</i></div>';}
	Array.prototype.forEach.call(boxes,function(box){
		box.setAttribute('data-ready','1');
		var end=parseInt(box.getAttribute('data-end'),10)*1000,timer=box.querySelector('.wdo__timer');
		function remove(){if(box.parentNode){box.parentNode.removeChild(box);}}
		function tick(){
			var d=end-Date.now();
			if(d<=0){remove();return false;}
			if(!timer){return false;}
			var h=Math.floor(d/36e5),m=seg(Math.floor(d%36e5/6e4),'Min')+seg(Math.floor(d%6e4/1e3),'Sec');
			// Shown on the last day only, so at most 24 hours.
			timer.innerHTML=seg(h,'Hrs')+m;
			return true;
		}
		if(tick()){var t=setInterval(function(){if(!tick()){clearInterval(t);}},1000);}
		function send(claim){
			if(!window.ajaxurl||box.hasAttribute('data-preview')){return;}
			var body=new FormData();
			body.append('action','wowdevs_offer_close');
			body.append('id',box.getAttribute('data-id'));
			body.append('nonce',box.getAttribute('data-nonce'));
			if(claim){body.append('claim','1');}
			if(navigator.sendBeacon){navigator.sendBeacon(window.ajaxurl,body);}else if(window.fetch){fetch(window.ajaxurl,{method:'POST',credentials:'same-origin',body:body});}
		}
		var code=box.querySelector('.wdo__code');
		if(code){code.addEventListener('click',function(){var l=code.querySelector('.wdo__code-lbl');if(!navigator.clipboard){return;}navigator.clipboard.writeText(code.getAttribute('data-code')).then(function(){l.textContent='Copied!';setTimeout(function(){l.textContent='Use code';},2000);});});}
		Array.prototype.forEach.call(box.querySelectorAll('.wdo__btn'),function(btn){btn.addEventListener('click',function(){send(true);});});
		box.querySelector('.wdo__close').addEventListener('click',function(){send(false);remove();});
	});
})();
</script>
		<?php
	}
}
