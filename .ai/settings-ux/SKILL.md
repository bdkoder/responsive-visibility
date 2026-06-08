# Skill: Settings Page UX (Range Hints · Visual Bar · Reset · Blank Guard)

> ⚠️ **SUPERSEDED (implementation).** The settings page is now a **React** app — see
> [`../admin-react/SKILL.md`](../admin-react/SKILL.md). The UX *concepts* below (range
> hint, spectrum bar, reset, blank guard, the `dynamic_css` range mirror, 900px table)
> still describe what the page does and remain the spec. But the vanilla-JS/PHP-form
> *implementation* details here are historical — the live code is `src/admin/**` (React)
> + `Rest_Breakpoints`. Read this for the behaviour, the React skill for the code.

> Read before touching `includes/class-admin-settings.php`. This skill upgrades the
> Breakpoints settings page (`wp-admin/options-general.php?page=responsive-visibility`)
> from a plain form into an Elementor-grade breakpoint editor. **Zero changes to
> storage, render, or the block editor — purely the admin UI.** No JS build.

Builds on the data model in [`../custom-breakpoint/SKILL.md`](../custom-breakpoint/SKILL.md)
and the slug/save rules in [`../settings-hardening/SKILL.md`](../settings-hardening/SKILL.md).
Nothing here changes either — same `rv_slug[]` hidden field, same two-pass save.

---

## Why

The page works but users don't understand **what "Max Width" does**. "767" doesn't
read as "this row covers screens up to 767px." They also can't recover from a bad
config, and the "only one blank max-width" rule only surfaces as an error *after*
submit. This skill fixes all four.

## The Four Features

| # | Feature | Type | Risk |
|---|---------|------|------|
| A | **Live range hint** per row — "≤ 767px", "768 – 1024px", "≥ 1025px" | inline JS | none |
| B | **Visual range bar** — Elementor-style stacked spectrum above the table | inline JS/CSS | none |
| C | **Reset to defaults** button — one click back to 767/1024/1025 | PHP + button | none |
| D | **Blank guard** — live red flag + "∞ Largest device" badge, error before submit | inline JS | none |

All four live in `render_page()` markup + `Admin_Settings::enqueue_assets()` + the
admin JS/CSS; C also adds a branch in `settings_page()`. No option-schema change.

## Asset pipeline (how the JS/CSS are built & loaded)

The admin script/style are NOT inline. Source lives in `src/admin/`, built by
**esbuild** to `assets/`, both unminified and minified:

```
src/admin/js/settings.js   ─esbuild─▶  assets/js/settings.js  + settings.min.js
src/admin/css/settings.css ─esbuild─▶  assets/css/settings.css + settings.min.css
```

- Builder: `scripts/build-admin.mjs` (esbuild, devDep). `npm run build:admin`
  one-shot; `npm run watch:admin` / `npm run start:hot` watch.
- `npm run build` runs `wp-scripts build` (blocks) **and** `build:admin`. `zip` /
  `release:check` therefore always package fresh assets.
- **Built `assets/js` + `assets/css` are git-ignored** (generated). They are NOT
  excluded by `.buildignore`/`.distignore`, so they DO ship in the zip. `assets/imgs`
  stays committed (source icons).
- PHP loads them **register-first, then enqueue** in `enqueue_assets()`, gated on the
  exact options-page hook suffix (`self::$hook` from `add_options_page`). Loads
  `.min` by default, unminified when `SCRIPT_DEBUG`. Version = `filemtime()`.
  `class-admin-settings.php` itself is only required on `is_admin()` (main plugin file).
- Strings → `wp_localize_script( 'rv-settings', 'rvSettings', … )`. Static styling →
  `settings.css` classes; only computed values (segment flex-grow + color) are inline
  in JS.

---

## Implementation Spec

### Shared rule — the range math MUST mirror PHP `Breakpoints::dynamic_css()`

The JS that computes ranges has to produce the exact ranges the frontend CSS will
use, or the hint lies. Mirror the PHP loop precisely:

```
sort rows by max_width ascending, blanks (null) last
prevMin = 0
for each row i (sorted):
  isLast = (i === last index)
  if isLast AND count > 1      -> "≥ {prevMin}px"        (largest, no upper limit)
  elif max is blank            -> INVALID "set a max-width" (a non-last blank is illegal)
  elif prevMin === 0           -> "≤ {max}px"            (first/only); prevMin = max+1
  else                         -> "{prevMin} – {max}px"; prevMin = max+1
```

Single row with a value → "≤ {max}px". Single blank row → INVALID. This matches the
`$is_last && $count > 1` guard added in the hardening skill.

### C — `settings_page()` reset branch

Add **before** the existing save branch (distinct submit button name, shares the
nonce). Reset = `delete_option()` so the page falls back to `get_defaults()` —
identical to a fresh install, cleaner than writing defaults back.

```php
$saved = false;
$reset = false;
$error = '';

if ( isset( $_POST['rv_reset_breakpoints'] ) && check_admin_referer( 'rv_save_breakpoints_nonce' ) ) {
    delete_option( 'responsive_visibility_breakpoints' );
    $reset = true;
} elseif ( isset( $_POST['rv_save_breakpoints'] ) && check_admin_referer( 'rv_save_breakpoints_nonce' ) ) {
    $result = self::process_save();
    if ( true === $result ) { $saved = true; } else { $error = $result; }
}
```

Pass `$reset` into `render_page()` and show a "reset to defaults" notice.

### C — Reset button markup

Bundle with the save button. **`formnovalidate`** is required so the browser's
HTML5 `required` on label inputs doesn't block a reset. Native `confirm()` because
it's destructive.

```php
<p>
    <?php submit_button( __( 'Save Breakpoints', 'responsive-visibility' ), 'primary', 'rv_save_breakpoints', false ); ?>
    <button type="submit" name="rv_reset_breakpoints" class="button button-link-delete" formnovalidate
        style="margin-left:8px;"
        onclick="return confirm('<?php echo esc_js( __( 'Reset all breakpoints to the defaults (Mobile 767, Tablet 1024, Desktop)? This cannot be undone.', 'responsive-visibility' ) ); ?>');">
        <?php esc_html_e( 'Reset to defaults', 'responsive-visibility' ); ?>
    </button>
</p>
```

### A/D — table changes

- New column header: `Hidden when` (~30%); shrink Label/Max-Width.
- **Width budget (critical):** the table is `table-layout:fixed`. All four `<th>`
  widths — including the Remove column — must sum to 100%, or the unspecified column
  collapses and its button overflows the table edge. Defined in `settings.css`:
  Label 24% / Max Width 18% / Hidden when 34% / Remove 24%, table + bar
  `max-width:900px` (wide enough that "px" and the "∞ Largest device" badge never
  get squeezed).
- Each row gets `<td class="rv-range-cell"></td>` (filled by JS): range text +,
  for the largest row, an "∞ Largest device" badge.
- The **same `<td>`** must be added to the JS add-row template so new rows match.
- Invalid (illegal blank) → red range text + red border on its number input +
  a `title` tooltip; it is excluded from the bar.

### B — bar markup

One empty flex container above the table, filled by JS:

```php
<div id="rv-range-bar" class="rv-bp-bar"></div>  <!-- styling in settings.css -->
```

Each valid segment: `flex-grow` proportional to its pixel span (`end - start`); the
largest (∞) segment uses `flex-grow ≈ scaleMax * 0.45` and shows a `▶`. `scaleMax` =
the largest finite `end` (fallback 1024). Colors cycle a small palette; the same
palette index is reused for the row's badge so bar ↔ row read as one unit.

### Recompute triggers

A single `recompute()` runs on: `input` (delegated on the tbody), add-row,
remove-row, and initial load. It (1) writes every `.rv-range-cell`, (2) rebuilds the
bar, (3) flags invalid blanks. Keep the existing `updateRemoveButtons()`.

### Submit guard

Keep the ">1 blank → block submit" guard, but **skip it when the reset button is the
submitter** (`e.submitter && e.submitter.name === 'rv_reset_breakpoints'`), and focus
the offending field. Use `escapeHtml()` (textContent trick) for any label echoed into
bar/cell HTML — labels are user input.

---

## Backward Compatibility — Why Nothing Breaks

- **No storage change.** Option schema, `rv_slug[]`, two-pass save, sort — all
  untouched. Save/Reset write the same shape the frontend already reads.
- **No render/editor change.** `class-render.php`, `class-breakpoints.php`,
  `settings.js`, `block-wrapper.js` not touched. Frontend output identical.
- **Reset = `delete_option`** → `get_option` fallback = `get_defaults()` → byte-identical
  to a fresh install's CSS.
- **JS range math mirrors PHP** so the hint can never disagree with the real CSS.
- **Pure progressive enhancement.** If the inline JS fails, the form still submits and
  saves exactly as before — hints/bar are additive, not load-bearing.
- **i18n:** range cells are symbolic (`≤ – ≥ px`, language-neutral); the column header
  + badge + messages are translatable strings.

---

## Testing Checklist

- [ ] Load page → bar renders 3 segments (Mobile/Tablet/Desktop), each row shows
      "≤ 767px" / "768 – 1024px" / "≥ 1025px"; Desktop row shows "∞ Largest device".
- [ ] Type in a Max Width → hint + bar update live, no reload.
- [ ] Add a row "Widescreen 1400" → 4th segment appears, ranges recompute correctly.
- [ ] Blank a second row → that row goes red live (border + tooltip), excluded from bar.
      Submitting is blocked with the message; reset still works.
- [ ] **Reset** → confirm dialog → option deleted → page shows defaults → notice shown.
      Reset works even with an empty/required label field present (`formnovalidate`).
- [ ] Save unchanged defaults → option stores slugs `mobile/tablet/desktop` (hardening
      behavior intact).
- [ ] Single row with a value → "≤ {max}px"; single blank row → red invalid.
- [ ] Frontend CSS for any saved config unchanged vs before this skill.
- [ ] `php -l includes/class-admin-settings.php` clean.

## Files

| File | Role |
|------|------|
| `src/admin/js/settings.js` | Source — recompute (A/D), bar builder (B), reset-confirm + submit guard. |
| `src/admin/css/settings.css` | Source — bar/badge/range styling + table width budget. |
| `scripts/build-admin.mjs` | esbuild builder → `assets/{js,css}/settings(.min).{js,css}`. |
| `includes/class-admin-settings.php` | `settings_page()` reset branch; `render_page()` class-based markup; `enqueue_assets()` register-then-enqueue. |
| `responsive-visibility.php` | `RV_PLUGIN_FILE`/`RV_VERSION` consts; admin-only require of the settings class. |
| `package.json` | `esbuild` devDep; `build`/`build:admin`/`watch:admin`/`start:hot` scripts. |
| `.gitignore` | Ignores generated `assets/js` + `assets/css`. |

After editing `src/admin/*`, run `npm run build:admin` (or `npm run start:hot` to
watch) so `assets/` regenerates. PHP markup edits need no build. Full asset pipeline:
[`../assets/SKILL.md`](../assets/SKILL.md).
