# Skill: React Admin (Breakpoints settings page)

> Read before touching the settings page UI, its REST endpoint, or the admin build.
> The page is a **small React app** built with `wp-scripts`, talking to **one REST
> endpoint**, rendering with `@wordpress/components`. Conventions follow `blockish`
> (`blockish/src/dashboard`) but intentionally minimal — **no router, no Redux store,
> one controller**. Migration plan + rationale: [`ROADMAP.md`](./ROADMAP.md).

Golden rule: **storage schema, slug-lock, validation, and frontend output never change.**
This skill only swaps the admin UI tech. See [`../settings-hardening/SKILL.md`](../settings-hardening/SKILL.md)
(slug-lock) and [`../custom-breakpoint/SKILL.md`](../custom-breakpoint/SKILL.md) (data flow).

---

## Architecture (one screen)

```
PHP  Admin_Settings           add_options_page → renders <div id="rv-admin-root">,
                              enqueues build/admin, injects window.rvAdmin bootstrap.
PHP  Rest_Breakpoints         GET/POST/DELETE /responsive-visibility/v1/breakpoints
                              (the ONLY writer of the option; ports the slug-lock).
JS   src/admin/index.js       createRoot( #rv-admin-root ).render(<App/>)
JS   components/App.js         useState(breakpoints) + apiFetch save/reset
JS   components/BreakpointRow  TextControl (label) + TextControl[type=number] (max) + remove
JS   components/ErrorBoundary   catches render errors → fallback notice (never blank-screens)
JS   components/RangeBar       spectrum bar (mirrors dynamic_css ranges)
JS   lib/ranges.js            pure computeRanges() — shared, mirror of PHP
SCSS src/admin/style.scss     bar/badge/range/table styling (900px table)
```

No `@wordpress/data`, no `history`, no extra npm deps beyond what `wp-scripts` provides
(`@wordpress/components`, `@wordpress/element`, `@wordpress/api-fetch`, `@wordpress/i18n`).

---

## REST contract — `responsive-visibility/v1/breakpoints`

Registered on `rest_api_init`. `permission_callback = current_user_can('manage_options')`
for every method. apiFetch auto-sends the `wp_rest` nonce.

> **`Rest_Breakpoints::register()` must be called UNCONDITIONALLY** in the main plugin
> file — NOT inside the `is_admin()` block. A REST request to `/wp-json/...` is not an
> admin request (`is_admin()` is false there), so gating it behind `is_admin()` would
> 404 the endpoint. It only adds a `rest_api_init` hook (cheap on non-REST loads).

| Method | Body | Returns |
|--------|------|---------|
| `GET` | — | `{ breakpoints: BP[], defaults: BP[] }` |
| `POST` | `{ breakpoints: BP[] }` | `{ breakpoints: BP[] }` (saved, sorted) or `WP_Error` 400 |
| `DELETE` | — | `{ breakpoints: BP[] }` (defaults, after `delete_option`) |

`BP = { slug?: string, label: string, max_width: number|null }`. `slug` is optional on
input — present for existing rows (locked), omitted/empty for new rows.

**The POST handler IS the slug-lock.** It ports `Admin_Settings::process_save()` exactly:
two passes — pass 1 reserves every incoming non-empty slug; pass 2 reuses locked slugs
verbatim and mints `sanitize_title(label)` (with `-2` collision bump) only for rows with
no slug. Then the same validation (`≥1`, only one `null` max_width), `Breakpoints::sort`,
`update_option`. **This logic must live in exactly one place** — move it into the REST
controller (the form is gone) so it cannot drift.

Sanitize on the server regardless of client: `sanitize_text_field` (label, with
`wp_unslash`), `absint`/null (max_width), `sanitize_key`/`sanitize_title` (slug).

---

## PHP enqueue (register-first, page-scoped) — same discipline as before

```php
public static function enqueue_assets( $hook ) {
    if ( $hook !== self::$hook ) { return; }                 // options-page hook only

    $asset = require plugin_dir_path( RV_PLUGIN_FILE ) . 'build/admin/index.asset.php';

    wp_register_script( 'rv-admin', plugins_url( 'build/admin/index.js', RV_PLUGIN_FILE ),
        $asset['dependencies'], $asset['version'], true );
    // wp-scripts emits `style.scss` imports as style-index.css (NOT index.css).
    wp_register_style( 'rv-admin', plugins_url( 'build/admin/style-index.css', RV_PLUGIN_FILE ),
        array( 'wp-components' ), $asset['version'] );

    wp_add_inline_script( 'rv-admin',
        'window.rvAdmin = ' . wp_json_encode( array(
            // Pass the REST PATH; apiFetch prepends the root URL and the wp_rest nonce
            // automatically (the wp-api-fetch dep). No manual nonce needed.
            'restPath'    => '/' . Rest_Breakpoints::REST_NAMESPACE . Rest_Breakpoints::REST_ROUTE,
            // Current saved breakpoints so React paints without a GET round-trip.
            'breakpoints' => get_option( 'responsive_visibility_breakpoints', Breakpoints::get_defaults() ),
            'defaults'    => Breakpoints::get_defaults(),
            'strings'     => array( /* i18n */ ),
        ) ) . ';',
        'before'
    );

    wp_enqueue_script( 'rv-admin' );
    wp_enqueue_style( 'rv-admin' );
}
```

- `index.asset.php` is generated by wp-scripts (DependencyExtractionWebpackPlugin) — it
  lists `wp-element`, `wp-components`, `wp-api-fetch`, `wp-i18n` etc. so core loads them;
  our bundle stays small. **Never hardcode the deps array** — read it from the asset file.
- `render_page()` → just `<div id="rv-admin-root"></div>` plus a `<noscript>` notice.
- Settings class still required only under `is_admin()` in the main plugin file.
- Bootstrap via `window.rvAdmin`; strings via that object (or `@wordpress/i18n`).

---

## Build (wp-scripts, not esbuild)

React + `@wordpress/components` require wp-scripts' externals + JSX. The esbuild admin
pipeline is **removed**. Add an admin entry to `webpack.config.js`:

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const entry = typeof defaultConfig.entry === 'function' ? defaultConfig.entry() : defaultConfig.entry;
module.exports = {
    ...defaultConfig,
    entry: { ...entry, 'admin/index': path.resolve( process.cwd(), 'src/admin/index.js' ) },
};
```

- `npm run build` → `build/admin/index.js` + `index.css` + `index.asset.php` (plus the
  existing block build under `build/extentions/`).
- `npm run start` watches both. (`start:hot`/`build:admin`/`watch:admin` esbuild scripts
  are removed.)
- `build/` is git-ignored, shipped in the zip (`.buildignore`/`.distignore` exclude
  `src`, not `build`). `assets/imgs` stays; `assets/js`,`assets/css` (+ their gitignore
  lines) are deleted with the esbuild pipeline.

---

## React conventions (keep it boring)

- `createRoot` from `@wordpress/element`, mount on `window.addEventListener('load')` if
  the root exists.
- State: `const [rows, setRows] = useState(window.rvAdmin.breakpoints ?? window.rvAdmin.defaults)`.
  No store. Each row is `{ _id, slug, label, maxWidth }` (camelCase in JS; the snake_case
  `max_width` wire shape is mapped only at the apiFetch boundary in `toRows`/`toPayload`).
  New rows have `slug: ''`.
- UI: `@wordpress/components` — `Card`/`CardBody`, `TextControl` (label AND max-width via
  `type="number"` — stable, avoids the experimental NumberControl), `Button`
  (`variant`, `isDestructive`),
  `Notice`/`Snackbar` for feedback. Reset = `Button isDestructive` + a `confirm()` or a
  `ConfirmDialog`.
- Save: `apiFetch({ path/url, method:'POST', data:{ breakpoints } })`; on success replace
  local state with the server's canonical (sorted, slug-locked) response. On `WP_Error`,
  show the message.
- Ranges: `lib/ranges.js` `computeRanges(rows)` — pure, the JS mirror of
  `Breakpoints::dynamic_css()` (first→`≤max`, middle→`min–max`, last(count>1)→`≥min`,
  lone-blank→invalid). Used by `RangeBar` and the per-row "Hidden when" hint. Keep this in
  lockstep with the PHP.
- i18n via `@wordpress/i18n` `__()` (text domain `responsive-visibility`).

---

## Robustness — why the dashboard can't break

- **ErrorBoundary** wraps `<App/>` — any render exception shows a recoverable notice, not
  a blank screen. Saved data is never touched by a UI crash.
- **Missing/garbage bootstrap** is safe: `window.rvAdmin || {}`, `strings || {}`,
  `toRows( list || [] )`, `computeRanges([])` all no-op gracefully (empty table, still
  addable).
- **apiFetch failures** (network/403/500/`WP_Error`) are caught → error notice; `busy`
  always cleared in `finally`; buttons disabled while busy (no double-submit).
- **Bad response shape** can't crash: `toRows( response?.breakpoints )` tolerates undefined.
- **Server is the gate.** Even if the client sends junk, `Rest_Breakpoints` re-sanitizes,
  re-validates, and re-sorts. The option can only ever hold a clean, slug-locked, sorted array.
- **Frontend isolation.** None of this loads on the public site; a dashboard bug can't
  affect rendered pages — `class-render.php`/`class-breakpoints.php` are untouched.

## Backward-compatibility checklist (run every change)

- [ ] Option schema/key unchanged; GET and POST round-trip the exact `{slug,label,max_width}` shape.
- [ ] Slug-lock: rename a label → POST keeps the old slug; saved blocks stay hidden.
- [ ] Reset → `delete_option` → defaults; frontend CSS byte-identical to fresh install.
- [ ] `class-render.php` / `class-breakpoints.php` untouched; frontend output identical.
- [ ] REST writes require manage_options + nonce (403 otherwise).
- [ ] React + REST load ONLY on the settings hook; public site ships no new JS.
- [ ] `php -l`, `npm run build`, `npm run zip` (build/admin present, src absent) all green.

## Files

| File | Role |
|------|------|
| `includes/class-rest-breakpoints.php` | REST GET/POST/DELETE; the slug-lock sanitizer (single source). |
| `includes/class-admin-settings.php` | `add_options_page`, root div, enqueue `build/admin` + `window.rvAdmin`. |
| `src/admin/index.js` | mount React. |
| `src/admin/components/App.js` | state, save/reset via apiFetch, notices. |
| `src/admin/components/ErrorBoundary.js` | wraps App; render errors → recoverable notice, never a blank page. |
| `src/admin/components/BreakpointRow.js` | one row (label/max/remove). |
| `src/admin/components/RangeBar.js` | spectrum bar. |
| `src/admin/lib/ranges.js` | pure `computeRanges()` (PHP mirror). |
| `src/admin/style.scss` | bar/badge/range/table styles (900px). |
| `webpack.config.js` | adds `admin/index` entry. |
| `responsive-visibility.php` | requires REST class (admin + REST context). |
