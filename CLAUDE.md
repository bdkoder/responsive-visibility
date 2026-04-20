# Responsive Visibility — Project Brain

> Read this entire file before touching any code. This is the senior developer's knowledge base for this project. It tells you not just WHAT the code does, but WHY every decision was made.

---

## Who You Are Working With

**Developer:** Shahidul Islam (bdkoder) — WowDevs (wowdevs.com)
**Role:** WordPress plugin developer who builds Gutenberg block plugins for the WordPress.org ecosystem
**Working style:** Practical, client-driven. He brings real client feedback directly into features. Prefers clean conventional WordPress patterns over clever over-engineering. Respects backward compatibility above all else.

---

## What This Plugin Is

**Responsive Visibility for Blocks Editor** — lets content editors hide or show any Gutenberg block per device breakpoint, using CSS media queries. It is NOT a block type. It is a **block extension** — it adds controls to every existing block via WordPress filter hooks.

**The core user story:** A content editor adds a paragraph block. They want it hidden on mobile. They toggle "Hide on Mobile" in the block's sidebar panel. Done. No shortcodes, no custom CSS, no theme dependency.

---

## Current Feature Set (as of v1.0.6 + custom breakpoints)

### What exists today:
1. **Per-block visibility toggles** — every block gets "Hide on Mobile/Tablet/Desktop" toggles in the Inspector panel (right sidebar)
2. **Dynamic custom breakpoints** — Settings → Responsive Visibility page where users define their own breakpoint values (add/remove rows, Elementor-style)
3. **Dynamic CSS generation** — PHP outputs inline `<style>` at `wp_head` priority 99 based on saved breakpoints
4. **Backward compatible** — old blocks with `hideOnDesktop/Tablet/Mobile` boolean attrs still work forever
5. **Settings link in Inspector** — "⚙ Customize breakpoints →" link at bottom of the panel
6. **Auto-migration** — when an old block is opened in the editor, `useEffect` migrates old boolean attrs to the new `hiddenBreakpoints` array automatically

### Block attributes (two systems, both supported):
```
// Legacy (never remove these):
hideOnDesktop: boolean  (default: false)
hideOnTablet:  boolean  (default: false)
hideOnMobile:  boolean  (default: false)

// New system:
hiddenBreakpoints: string[]  (default: [])
// e.g. ["mobile", "widescreen"] — array of breakpoint slugs
```

---

## Architecture — The Full Picture

### How a block gets hidden (follow this flow):

**In the editor (live preview):**
1. User toggles "Hide on Mobile" → `settings.js` updates `hiddenBreakpoints: ["mobile"]` on the block
2. `block-wrapper.js` reads `hiddenBreakpoints`, maps slug `"mobile"` → CSS class `"mobile-hidden"`, checks if current device type is `"Mobile"` → adds class to block wrapper in editor
3. `editor.scss` makes blocks with hidden classes show a diagonal stripe pattern (visual feedback)

**On the frontend:**
1. WordPress calls `render_block` filter → `responsive_visibility_render_block()` in `responsive-visibility.php`
2. PHP reads `hiddenBreakpoints` array from block attrs → maps each slug to a CSS class → adds to block's wrapper element
3. ALSO checks legacy boolean attrs for backward compat
4. Browser loads page → `<style id="rv-dynamic-breakpoints">` in `<head>` has the media queries → correct class gets `display:none`

### CSS class naming rules (critical — never change these):
```
"mobile"  slug → "mobile-hidden"   (legacy class name, kept forever)
"tablet"  slug → "tablet-hidden"   (legacy class name, kept forever)
"desktop" slug → "desktop-hidden"  (legacy class name, kept forever)
any other slug → "rv-hidden--{slug}"  (e.g. "widescreen" → "rv-hidden--widescreen")
```
This lives in `rv_class_for_slug()` in `responsive-visibility.php` and `rvClassForSlug()` in `block-wrapper.js`. Both must always stay in sync.

### Where breakpoints are stored:
```php
// wp_options key: responsive_visibility_breakpoints
// Value: PHP array of objects, e.g.:
[
  ["slug" => "mobile",  "label" => "Mobile",  "max_width" => 767],
  ["slug" => "tablet",  "label" => "Tablet",  "max_width" => 1024],
  ["slug" => "desktop", "label" => "Desktop", "max_width" => null],  // null = no upper limit
]
// Sorted ascending by max_width. null always goes last (= largest device).
```

### How dynamic CSS is generated:
`responsive_visibility_dynamic_css()` runs on `wp_head` priority 99. It:
1. Gets breakpoints from `get_option()` with `rv_default_breakpoints()` as fallback
2. Sorts them (nulls last)
3. Iterates: first breakpoint → `max-width` only, middle breakpoints → `min-width AND max-width`, last breakpoint → `min-width` only (no upper limit)
4. Outputs `<style id="rv-dynamic-breakpoints">` with `!important` to override compiled CSS

### How JS gets the breakpoints:
`wp_localize_script()` passes them as `window.rvBreakpoints = { breakpoints: [...], settingsUrl: "..." }`.
The `settings.js` reads this. Fallback is the hardcoded `DEFAULT_BREAKPOINTS` array in `settings.js` itself.

---

## Key Decisions — WHY Things Are the Way They Are

### Why `wp_options` and not `block.json` / `attributes.js` for breakpoints?
Breakpoints are **global site-wide settings**, not per-block. Storing them in block attributes would require every block to have its own breakpoint config — absurd. `wp_options` is the correct WordPress pattern for site-wide plugin settings. The Settings API handles sanitization, nonces, and permissions automatically.

### Why dynamic PHP CSS instead of compiled SCSS?
Compiled SCSS has hardcoded values — you can't change 767px from the admin panel if it's baked into a CSS file. Dynamic PHP output at `wp_head` (priority 99, after styles enqueue) reads the live option value every page load. The `!important` flag + `body .class` selector ensures the dynamic CSS always wins over the compiled fallback.

### Why keep old `hideOnDesktop/Tablet/Mobile` boolean attrs AND add `hiddenBreakpoints` array?
**Backward compatibility is non-negotiable.** There are real sites with posts containing `<!-- wp:paragraph {"hideOnMobile":true} -->` in the database. If we remove those attributes, those blocks break silently on the frontend. The PHP render filter checks BOTH systems. The JS auto-migrates on first open. Over time, old blocks naturally migrate as editors open them.

### Why `!important` in the dynamic CSS?
The compiled `style-index.css` uses `body .mobile-hidden` (specificity: 0,0,1,1). Without `!important`, if a theme outputs high-specificity CSS, it could accidentally override ours. `!important` is the correct tool here — this is user-intent CSS (the user explicitly said "hide this"), not decorative styling.

### Why Settings under Settings menu and NOT a top-level admin menu?
Client explicitly said: no main menu. Standard WordPress convention for small/medium plugins is `add_options_page()` under Settings. Only large multipurpose plugins (WooCommerce, etc.) earn a top-level menu. Keeps the admin clean.

### Why the last sorted breakpoint always gets `min-width` only (no upper limit)?
The largest device (Desktop/Widescreen) should cover all viewports above the previous breakpoint — including ultrawide monitors, TVs, future devices. Capping it at e.g. 3200px would mean a 4K monitor sees content that should be hidden. Last breakpoint = infinite upper range.

---

## Files — What Each One Does

| File | Purpose |
|------|---------|
| `responsive-visibility.php` | Plugin entry: helper functions (`rv_default_breakpoints`, `rv_class_for_slug`), `wp_localize_script`, dynamic CSS hook, `render_block` filter |
| `includes/admin-settings.php` | Settings page UI + form processing. `rv_settings_menu()` + `rv_settings_page()`. Inline JS for add/remove rows. |
| `src/.../index.js` | Entry point: registers the 3 `addFilter` hooks |
| `src/.../components/attributes.js` | Adds block attributes to every block. Has both old booleans AND new `hiddenBreakpoints` array. |
| `src/.../components/settings.js` | Inspector panel UI. Reads `window.rvBreakpoints`. Dynamic toggles. Auto-migrates old attrs. Has settings link. |
| `src/.../components/block-wrapper.js` | Editor preview. Maps `hiddenBreakpoints` slugs → CSS classes. Checks device type for standard slugs. Legacy attrs fallback. |
| `src/.../style.scss` | Default fallback frontend CSS (hardcoded 767/1024/1025 breakpoints). Overridden at runtime by dynamic CSS. |
| `src/.../editor.scss` | Editor styles: diagonal stripe pattern on hidden blocks. |
| `build/` | Compiled output. **Never edit directly.** Run `npm run build` after any `src/` change. |
| `dci/` | WowDevs analytics SDK. Do not modify. |

---

## Rules — What You Must NEVER Do

1. **Never edit files in `build/`** — they are compiled output, overwritten on next build
2. **Never remove `hideOnDesktop`, `hideOnTablet`, `hideOnMobile` attributes** — old sites depend on them
3. **Never change the legacy CSS class names** (`mobile-hidden`, `tablet-hidden`, `desktop-hidden`) — existing CSS in themes may already reference these
4. **Never add a top-level admin menu** (`add_menu_page`) — settings go under Settings only
5. **Never use raw SQL** — always `get_option` / `update_option` / Settings API
6. **Never skip `sanitize_key()`** on slugs before using in CSS class names — XSS risk
7. **Never add comments that explain WHAT the code does** — names explain that. Only comment WHY when it's non-obvious.
8. **Never add features without checking backward compatibility first**

---

## Rules — What You Must ALWAYS Do

1. **Run `npm run build`** after editing anything in `src/`
2. **Check both `hiddenBreakpoints` AND legacy boolean attrs** in any new PHP render logic
3. **Use `wp_parse_args()` with `rv_default_breakpoints()`** as fallback whenever reading the breakpoints option
4. **Sort breakpoints** (nulls last) before generating CSS or processing them
5. **Keep `rv_class_for_slug()` in PHP and `rvClassForSlug()` in JS in sync** — they must produce identical output
6. **Test that default state (no settings saved) produces identical CSS to the original plugin** — new installs must work exactly as before

---

## Development Workflow

```bash
# After editing any src/ file:
npm run build

# Verify PHP syntax after PHP changes:
php -l responsive-visibility.php
php -l includes/admin-settings.php
```

Settings page URL: `wp-admin/options-general.php?page=responsive-visibility`

---

## Planned Features (in priority order)

1. **"Reset to Defaults" button** on settings page — one click back to 767/1024/1025
2. **Overlap/gap validation** — warn if breakpoints have gaps or overlaps on save
3. **`theme.json` integration** — read breakpoints from active theme's `settings.custom.responsiveVisibility` if set; fall back to plugin settings
4. **Visibility by user role** — new condition type: hide/show for logged-in, logged-out, admin, etc.
5. **Visibility by date range** — show/hide between two dates (useful for promotions)
6. **Block badge in editor** — small indicator showing which breakpoints a block is hidden on, visible without opening Inspector
7. **Export/Import breakpoints** — JSON export to share config between sites
8. **WP CLI** — `wp rv breakpoints list|set|reset`

---

## Client Feedback History (shapes how you should work)

- **"Don't create a main menu"** — always use Settings submenu
- **"Backward compat is critical"** — never break old blocks, ever
- **"Dynamic CSS, not hardcoded"** — user-configurable values must be runtime, not compiled
- **"Elementor-style"** — the breakpoints feature was inspired by Elementor's breakpoint system (add/remove, named devices)
- **"Smooth integration with any theme"** — that's WHY custom breakpoints exist, themes have different breakpoint values
- **"Multiple elements on same page must have own settings"** — per-block attributes, not per-page
