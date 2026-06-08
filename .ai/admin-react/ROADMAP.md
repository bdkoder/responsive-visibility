# Roadmap: React Admin (Breakpoints settings page)

> Migrate the Settings → Responsive Visibility page from a PHP form + vanilla JS to a
> small React app, following the **blockish** conventions but **scaled down** — this is
> a one-page, low-feature screen, not a multi-route dashboard. Reference standard:
> `blockish/src/dashboard` (see `.ai/admin-react/SKILL.md`). Hard rule: **no data or
> frontend behavior changes** for existing or new users.

---

## What we borrow from blockish vs what we deliberately DROP

| blockish has | We do | Why |
|--------------|-------|-----|
| `add_menu_page` top-level menu | **Keep `add_options_page`** (Settings submenu) | RV rule #4 — never a top-level menu. |
| `history` router + multiple routes | **No router** — single screen | One page, no tabs. |
| `@wordpress/data` Redux store (actions/selectors/controls) | **Plain `useState` + `@wordpress/api-fetch`** | One screen of state; Redux is overkill. |
| Several REST controllers | **One controller**, GET+POST `/responsive-visibility/v1/breakpoints` | Only breakpoints to read/write. |
| Auto-save (500ms debounce) | **Explicit "Save" button** | Settings need validation + intent; safer. |
| `react-select`, `html-react-parser`, `clsx` deps | **None** beyond `@wordpress/components` | Keep dep surface tiny. |

What we DO adopt: `createRoot` mount into a root div, `@wordpress/components` UI,
`@wordpress/api-fetch` with the `wp_rest` nonce, REST controller with
`permission_callback = current_user_can('manage_options')`, `wp_add_inline_script`
bootstrap (`window.rvAdmin`), SCSS, wp-scripts build → `build/admin/`.

---

## Backward-compatibility contract (must hold — verify each)

1. **Same storage.** Option key `responsive_visibility_breakpoints`, same
   `{slug,label,max_width}` schema, same sort. React reads/writes the identical shape.
2. **Slug-lock preserved.** The REST POST handler ports the two-pass slug-preserving
   logic from `Admin_Settings::process_save()` verbatim (existing rows keep their slug;
   new rows mint+collision-bump). Renaming a label never orphans saved blocks.
3. **Frontend untouched.** `class-render.php` + `class-breakpoints.php` are NOT touched.
   Generated CSS for any saved config is byte-identical.
4. **Defaults + reset.** No option saved → `Breakpoints::get_defaults()`. Reset =
   `delete_option` (fresh-install state).
5. **Validation parity.** "≥1 breakpoint", "only one blank max-width" enforced in REST
   (server is the source of truth), mirrored in the UI for instant feedback.
6. **Capability + nonce.** manage_options + `wp_rest` nonce on every write (apiFetch
   auto-sends it). Same security posture as the current nonce'd form.
7. **No frontend JS added.** React loads ONLY on the settings page hook; the public site
   ships nothing new.

**Accepted change:** the page now requires JS (admin context — acceptable; blockish does
the same). The old PHP form fallback is removed. No user data is affected.

---

## Phases

### Phase 0 — REST layer (server is source of truth) ✅ first
- New `includes/class-rest-breakpoints.php` → `Rest_Breakpoints::register()` on
  `rest_api_init`. Namespace `responsive-visibility/v1`, route `/breakpoints`.
  **Registered unconditionally** in the main file (NOT under `is_admin()` — REST
  requests are not admin requests).
- `GET` → `{ breakpoints: [...], defaults: [...] }` from option (defaults fallback).
- `POST` → body `{ breakpoints: [{slug?,label,max_width}] }` → **port `process_save`
  two-pass slug-lock + validation** → `update_option` → return saved list or error.
- `DELETE` (or POST `{reset:true}`) → `delete_option` → return defaults.
- `permission_callback` → `current_user_can('manage_options')`.
- Keep `Admin_Settings::process_save()` logic as the shared sanitizer (extract to a
  static helper both can call, or move it into the REST controller and have it be the
  one place). One implementation, no drift.

### Phase 1 — PHP page + enqueue (replace the form)
- `Admin_Settings`: keep `add_options_page`. `render_page()` becomes just
  `<div id="rv-admin-root"></div>` (+ a `<noscript>` line).
- Enqueue `build/admin/index.js` + `style-index.css` from `index.asset.php` deps, gated
  on the options-page hook. `wp_add_inline_script` → `window.rvAdmin = { restUrl, nonce,
  defaults, strings }` (nonce via `wp_create_nonce('wp_rest')`).
- Delete the esbuild admin pipeline: `scripts/build-admin.mjs`, `src/admin/js`,
  `src/admin/css`, `assets/js`, `assets/css`, the `esbuild` dep + `build:admin`/
  `watch:admin`/`start:hot` scripts, and the `/assets/js`,`/assets/css` gitignore lines.

### Phase 2 — React app (minimal)
- `src/admin/index.js` — `createRoot(#rv-admin-root).render(<App/>)`.
- `src/admin/components/App.js` — `useState(breakpoints)`, load is bootstrapped from
  `window.rvAdmin` (no fetch needed on first paint; optionally refetch). Save/reset via
  `apiFetch`. Notices via `@wordpress/components` `Snackbar`/`Notice`.
- `src/admin/components/BreakpointRow.js` — `TextControl` (label) +
  `__experimentalNumberControl` (max width) + remove `Button`.
- `src/admin/components/RangeBar.js` — the spectrum bar (port the existing compute +
  colors; same `Breakpoints::dynamic_css()` mirror).
- `src/admin/lib/ranges.js` — pure `computeRanges()` (shared by RangeBar + row hints).
- `src/admin/style.scss` — port bar/badge/range/table styles (the 900px width fix).
- `webpack.config.js` — add `admin/index` entry alongside the default block entries.

### Phase 3 — verify + docs
- `npm run build` → `build/admin/index.js` + `.asset.php` + `index.css`.
- Backward-compat checks (below). Update `.ai` skills + `CLAUDE.md`.

---

## Verification checklist (Phase 3)

- [ ] Fresh install (no option) → React shows mobile/tablet/desktop defaults; GET returns defaults.
- [ ] Save unchanged → option stores slugs `mobile/tablet/desktop`.
- [ ] **Rename test**: add "Widescreen", hide a block on it, rename label → save → block still hidden (slug `widescreen` preserved). REST proves slug-lock ported correctly.
- [ ] >1 blank max-width → REST rejects + UI shows error before send.
- [ ] Reset → `delete_option` → defaults; frontend CSS identical to fresh install.
- [ ] Non-admin / bad nonce → REST 403.
- [ ] Frontend page source unchanged; no admin JS on the public site.
- [ ] `php -l` all PHP; `npm run build` clean; `npm run zip` ships `build/admin/*`, excludes `src/`.

---

## Risk register

| Risk | Mitigation |
|------|-----------|
| Slug-lock logic drifts between PHP form & REST | Single shared sanitizer — one implementation. The PHP form is being removed anyway, so REST becomes the only writer. |
| React bundle bloat | wp-scripts externalizes `wp-element`/`wp-components` (loaded by core) → small `index.js`; only our code ships. |
| JS-off admins | Accepted; `<noscript>` message. Data never at risk. |
| Build entry misconfig (blocks vs admin) | Add `admin/index` to webpack `entry`, keep default block entries; verify both build. |
| Throwaway of the esbuild work | Intentional — React needs wp-scripts externals; esbuild admin pipeline is superseded. |
