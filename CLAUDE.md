# Responsive Visibility — Project Brain

> Read before touching code. WHAT the code does and WHY the decisions were made.

## Developer

Shahidul Islam (bdkoder) — WowDevs. Builds Gutenberg block plugins for WordPress.org.
Practical, client-driven, brings real client feedback into features. Prefers clean
conventional WordPress patterns over clever over-engineering. Backward compatibility
above all else.

## What This Plugin Is

**Responsive Visibility for Blocks Editor** — hide/show any Gutenberg block per device
breakpoint via CSS media queries. NOT a block type — a **block extension**: it adds
controls to every existing block through WordPress filter hooks.

Core story: editor adds a paragraph, wants it hidden on mobile, toggles "Hide on
Mobile" in the sidebar. Done. No shortcodes, no custom CSS, no theme dependency.

## Feature Set (v1.1.0)

1. Per-block visibility toggles in the Inspector panel
2. Dynamic custom breakpoints — Settings page, add/remove/rename (Elementor-style)
3. Dynamic CSS — PHP outputs inline `<style>` at `wp_head` priority 99
4. Backward compatible — legacy `hideOnDesktop/Tablet/Mobile` booleans still work
5. "⚙ Customize breakpoints" link in the Inspector
6. Auto-migration — opening an old block migrates booleans → `hiddenBreakpoints` array

### Block attributes (two systems, both supported)

```
// Legacy (NEVER remove):
hideOnDesktop / hideOnTablet / hideOnMobile : boolean (default false)

// Current:
hiddenBreakpoints : string[]  (default [])   e.g. ["mobile","widescreen"]
```

## Architecture (OOP — namespace `WowDevs\Responsive_Visibility`)

Entry `responsive-visibility.php` requires four classes and calls `::register()`
(`Rest_Breakpoints` unconditionally — REST requests aren't `is_admin()`; `Admin_Settings`
only under `is_admin()`):

- `Breakpoints` (`includes/class-breakpoints.php`) — defaults, slug→class map, sort,
  dynamic CSS on `wp_head` 99
- `Render` (`includes/class-render.php`) — `render_block` filter, adds CSS classes
- `Rest_Breakpoints` (`includes/class-rest-breakpoints.php`) — REST GET/POST/DELETE
  `responsive-visibility/v1/breakpoints`; the ONLY option writer; owns the slug-lock sanitizer
- `Admin_Settings` (`includes/class-admin-settings.php`) — Settings page: mounts the React
  app (`build/admin`) + `window.rvAdmin` bootstrap

### Admin settings page = React

The Settings → Responsive Visibility page is a small React app (`src/admin`, built by
wp-scripts → `build/admin`) using `@wordpress/components`, talking to the REST endpoint.
No router, no Redux — `useState` + `@wordpress/api-fetch`. Saving/reset/validation all go
through `Rest_Breakpoints` (slug-lock lives there now, not a PHP form). Block editor JS
(`src/extentions`) is unchanged. See `.ai/admin-react/SKILL.md`.

### How a block gets hidden

**Editor:** toggle → `settings.js` sets `hiddenBreakpoints` → `block-wrapper.js` maps
slug to class, checks device type → `editor.scss` shows a diagonal stripe.

**Frontend:** `Render::render_block()` reads `hiddenBreakpoints` (and legacy booleans),
maps each slug to a class, adds it to the block wrapper → `Breakpoints::dynamic_css()`
emits media queries in `<style id="rv-dynamic-breakpoints">` → matching class gets
`display:none`.

### Slug ↔ class map (CRITICAL — never change)

```
mobile  → mobile-hidden     (legacy, forever)
tablet  → tablet-hidden      (legacy, forever)
desktop → desktop-hidden     (legacy, forever)
other   → rv-hidden--{slug}  (e.g. widescreen → rv-hidden--widescreen)
```

Lives in `Breakpoints::class_for_slug()` (PHP) and `rvClassForSlug()`
(`block-wrapper.js`). Both must produce identical output.

### Breakpoints storage

`wp_options` key `responsive_visibility_breakpoints` — array of
`{slug, label, max_width}`. `max_width:null` = no upper limit. Sorted ascending by
max_width, nulls last (largest device). Fallback: `Breakpoints::get_defaults()`
(mobile 767 / tablet 1024 / desktop null).

### slug vs label

`label` = display text the user types ("Mobile"). `slug` = internal id blocks store
(`mobile`). Slug is the contract between a saved block and its breakpoint — **locked
on creation, never regenerated from the label.** See `.ai/settings-hardening/SKILL.md`.

### Dynamic CSS generation

`Breakpoints::dynamic_css()` on `wp_head` 99: get option (defaults fallback), sort,
iterate — first → `max-width` only, middle → `min-width AND max-width`, last →
`min-width` only (no cap). `!important` overrides compiled CSS. Single breakpoint is
guarded so it doesn't hide on every screen.

### Breakpoints → JS

`wp_localize_script` → `window.rvBreakpoints = { breakpoints, settingsUrl }`.
`settings.js` reads it; fallback is its hardcoded `DEFAULT_BREAKPOINTS`.

## Key Decisions (WHY)

- **`wp_options`, not block attrs, for breakpoints** — they're global site settings,
  not per-block. Settings API gives sanitization/nonces/permissions.
- **Dynamic PHP CSS, not compiled SCSS** — admin-editable px values must be runtime.
  `wp_head` 99 reads the live option each load; `!important` + `body .class` wins.
- **Keep legacy booleans AND the array** — real sites have `{"hideOnMobile":true}` in
  post DB. Removing the attrs breaks them silently. Render checks both; JS auto-migrates.
- **Settings submenu, not top-level menu** — client said no main menu; `add_options_page`
  is the convention for small/medium plugins.
- **Last breakpoint is `min-width` only** — largest device covers all viewports above
  the previous one, including ultrawide/4K/future. Capping it would leak content.
- **Slug locked on creation** — blocks reference breakpoints by slug; regenerating it
  from the label on rename orphans saved blocks (the bug fixed in v1.1.x hardening).

## Files

| File | Purpose |
|------|---------|
| `responsive-visibility.php` | Entry: requires classes, `::register()`, asset enqueue + `wp_localize_script`, DCI SDK + review-prompt init |
| `includes/class-breakpoints.php` | `get_defaults`, `class_for_slug`, `sort`, `dynamic_css` |
| `includes/class-render.php` | `render_block` filter — adds classes (new array + legacy) |
| `includes/class-admin-settings.php` | Options page; mounts React (`build/admin`) + `window.rvAdmin` bootstrap |
| `includes/class-rest-breakpoints.php` | REST GET/POST/DELETE; slug-lock sanitizer (single writer of the option) |
| `includes/feedbacks/` | Review-request prompt (rc) SDK. Do not modify. |
| `dci/` | WowDevs analytics SDK. Do not modify. |
| `src/.../index.js` | Registers the 3 `addFilter` hooks |
| `src/.../components/attributes.js` | Adds block attributes (legacy booleans + array) |
| `src/.../components/settings.js` | Inspector toggles; reads `window.rvBreakpoints`; auto-migrates |
| `src/.../components/block-wrapper.js` | Editor preview; slug→class; device-type check |
| `src/.../style.scss` | Fallback frontend CSS (hardcoded 767/1024/1025) |
| `src/.../editor.scss` | Diagonal stripe on hidden blocks |
| `src/admin/**` | React admin source (`index.js`, `components/*`, `lib/ranges.js`, `style.scss`). Built by wp-scripts. |
| `webpack.config.js` | Adds the `admin/index` entry to the wp-scripts default config. |
| `build/` | Compiled output (blocks + `admin`). **Never edit.** Git-ignored, shipped in zip. |
| `assets/imgs/` | Source plugin icons (committed). |
| `.ai/*/SKILL.md` | Per-feature deep docs — read the relevant one before editing that area. Asset pipeline: `.ai/assets/SKILL.md`. |

## NEVER

1. Edit `build/` — compiled output, overwritten on next build (edit `src/`)
2. Remove `hideOnDesktop/Tablet/Mobile` attrs — old sites depend on them
3. Change legacy class names `mobile-hidden/tablet-hidden/desktop-hidden`
4. Add a top-level admin menu (`add_menu_page`) — Settings submenu only
5. Use raw SQL — `get_option`/`update_option`/Settings API only
6. Skip `sanitize_key()` on slugs before using in CSS class names (XSS)
7. Regenerate a breakpoint slug from its label on save — locks break (see hardening skill)
8. Comments that explain WHAT — names do that. Comment WHY when non-obvious.
9. Add features without checking backward compatibility first

## ALWAYS

1. `npm run build` after editing `src/`
2. Check both `hiddenBreakpoints` AND legacy booleans in render logic
3. `Breakpoints::get_defaults()` as the `get_option` fallback
4. Sort breakpoints (nulls last) before generating CSS
5. Keep `Breakpoints::class_for_slug()` (PHP) and `rvClassForSlug()` (JS) in sync
6. Default state (no option saved) must produce CSS identical to the original plugin

## Workflow

```bash
npm run build                                   # after any src/ change
php -l responsive-visibility.php
find includes -name '*.php' -print0 | xargs -0 -n1 php -l
npm run release:check                           # build + lint PHP + zip
```

Settings: `wp-admin/options-general.php?page=responsive-visibility`

## Roadmap

Reset-to-defaults button · overlap/gap validation · `theme.json` integration ·
visibility by user role · visibility by date range · in-editor block badge ·
export/import breakpoints · WP-CLI (`wp rv breakpoints …`).

## Client Feedback (shapes the work)

No main menu · backward compat is critical · dynamic CSS not hardcoded · Elementor-style
breakpoints · smooth integration with any theme · per-block settings (not per-page).
