# Responsive Visibility for Blocks Editor — AI Context

This file provides context for AI assistants (Claude, Gemini, Copilot, etc.) working on this plugin.

## Plugin Summary

**Name:** Responsive Visibility for Blocks Editor
**Author:** bdkoder / WowDevs (wowdevs.com)
**Purpose:** Extends every Gutenberg block with hide/show toggles per device breakpoint. Uses CSS media queries + CSS classes. No custom block type — extends all existing blocks via WordPress filter hooks.

## Tech Stack

- **PHP** 7.0+ / WordPress 6.1+
- **JavaScript** (React/JSX via `@wordpress/scripts` webpack build)
- **SCSS** → compiled CSS
- **Build command:** `npm run build` (from plugin root)
- **Source:** `src/extentions/responsive-visibility/`
- **Compiled output:** `build/extentions/responsive-visibility/`

## File Structure

```
responsive-visibility/
├── responsive-visibility.php        # Main plugin file — hooks, helpers, render filter
├── includes/
│   └── admin-settings.php           # Settings page: Settings → Responsive Visibility
├── src/extentions/responsive-visibility/
│   ├── index.js                     # Entry point — registers all addFilter hooks
│   ├── block.json                   # Block metadata
│   ├── editor.scss                  # Editor-only styles (stripe overlay for hidden blocks)
│   ├── style.scss                   # Frontend styles (default fallback breakpoints)
│   └── components/
│       ├── attributes.js            # Block attribute definitions (hideOnDesktop/Tablet/Mobile + hiddenBreakpoints)
│       ├── settings.js              # Inspector panel UI — dynamic toggles + settings link
│       └── block-wrapper.js         # Editor preview — adds CSS classes based on device type
├── build/extentions/responsive-visibility/
│   ├── index.js                     # Compiled JS (DO NOT edit directly)
│   ├── index.css                    # Compiled editor CSS
│   ├── style-index.css              # Compiled frontend CSS (default breakpoint fallback)
│   └── index.asset.php              # Dependency manifest (auto-generated)
├── dci/                             # WowDevs analytics SDK — do not modify
└── assets/imgs/                     # Plugin icons
```

## Architecture: How It Works

### Frontend (PHP + CSS)
1. `responsive_visibility_render_block()` — `render_block` filter adds CSS classes to block wrapper:
   - New system: reads `hiddenBreakpoints` array (slugs like `"mobile"`, `"widescreen"`)
   - Legacy: reads `hideOnDesktop`, `hideOnTablet`, `hideOnMobile` boolean attrs (backward compat)
2. `responsive_visibility_dynamic_css()` — `wp_head` hook (priority 99) outputs inline `<style>` with media queries built from saved breakpoints. Overrides compiled CSS with `!important`.

### Block Editor (JavaScript)
1. `attributes.js` — adds attributes to every block via `blocks.registerBlockType` filter
2. `settings.js` — adds Inspector Controls panel via `editor.BlockEdit` filter. Reads breakpoints from `window.rvBreakpoints` (localized from PHP). Auto-migrates old boolean attrs on first block open.
3. `block-wrapper.js` — adds CSS classes in editor for live preview via `editor.BlockListBlock` filter. Maps slugs to WordPress device types (Desktop/Tablet/Mobile).

### Breakpoints System
- **Storage:** `wp_options` key `responsive_visibility_breakpoints` — PHP array of `{ slug, label, max_width }` objects
- **Default breakpoints:** Mobile (≤767px), Tablet (768–1024px), Desktop (≥1025px)
- **CSS class mapping:** `mobile`→`mobile-hidden`, `tablet`→`tablet-hidden`, `desktop`→`desktop-hidden`, custom slugs→`rv-hidden--{slug}`
- **Sorting:** Ascending by `max_width`; `null` = no upper limit, sorted last (largest device)
- **Helper functions:** `rv_default_breakpoints()`, `rv_class_for_slug($slug)` in main PHP file

## Key Conventions

- **Never edit files in `build/`** — always edit `src/` then run `npm run build`
- **Backward compatibility is non-negotiable** — old block attributes (`hideOnDesktop/Tablet/Mobile`) must always be supported in the PHP render filter
- **Settings under Settings menu** — never add a top-level admin menu (`add_menu_page`)
- **No raw SQL** — always use WordPress Settings API / `get_option` / `update_option`
- **Dynamic CSS via `wp_head`** — user-configurable values must be output as inline CSS, not hardcoded in compiled files
- **`sanitize_key()`** all slug values from block attrs before using in CSS class names

## Block Attributes

```js
hideOnDesktop:     boolean (legacy, default: false)
hideOnTablet:      boolean (legacy, default: false)
hideOnMobile:      boolean (legacy, default: false)
hiddenBreakpoints: string[] (new system, default: [])
```

## PHP Script Handle

Editor script: `responsive-visibility-editor-script`
Localized data: `window.rvBreakpoints = { breakpoints: [...], settingsUrl: "..." }`

## Development Workflow

```bash
# Install dependencies (first time)
npm install

# Build for production
npm run build

# Watch for changes during development
npm run start
```

Always run `npm run build` after editing any file in `src/`.

## Planned / Future Features

- `theme.json` integration — read breakpoints from active theme's custom settings
- Visibility conditions beyond device: user role (logged-in/out), date range, URL parameter
- Export/Import breakpoints JSON between sites
- Breakpoint indicator badge on blocks in editor (show at a glance which breakpoints a block is hidden on)
- WP CLI commands: `wp rv breakpoints list/set`
- "Reset to defaults" button on settings page
- Overlap/gap validation with admin notice on settings save

## Settings Page

URL: `wp-admin/options-general.php?page=responsive-visibility`
Option key: `responsive_visibility_breakpoints`
Schema: `[{ "slug": string, "label": string, "max_width": number|null }, ...]`
