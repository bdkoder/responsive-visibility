# Skill: Asset Pipeline (src → assets / build, enqueue, packaging)

> ⚠️ **PARTLY SUPERSEDED.** The **esbuild admin pipeline** (`src/admin/{js,css}` →
> `assets/`) described here was **removed**. The admin settings page now builds with
> wp-scripts to `build/admin` (React) — see [`../admin-react/SKILL.md`](../admin-react/SKILL.md).
> The **blocks pipeline** (`src/extentions` → `build/`), the git-vs-zip rules, and the
> general "never edit generated output / page-scoped enqueue" principles below still
> apply. Ignore the esbuild-specific and `assets/js`,`assets/css` parts.

> Read before adding or editing any JS/CSS, changing build scripts, or touching how
> assets are enqueued. This plugin has **two independent asset pipelines** — do not
> mix them up. Getting this wrong ships broken or missing files to wordpress.org.

---

## The two pipelines

| Pipeline | Source | Tool | Output | Used by |
|----------|--------|------|--------|---------|
| **Blocks** | `src/extentions/**` | `wp-scripts` (webpack) | `build/extentions/**` | the block editor extension (Inspector toggles, editor preview) |
| **Admin** | `src/admin/{js,css}` | `esbuild` (`scripts/build-admin.mjs`) | `assets/{js,css}/settings(.min).{js,css}` | the Settings → Responsive Visibility page |

Both are compiled. **Never hand-edit `build/` or `assets/js` / `assets/css`** — they are
generated and overwritten on the next build.

```
src/extentions/responsive-visibility/**  ──wp-scripts──▶  build/extentions/responsive-visibility/**
src/admin/js/settings.js                 ──esbuild────▶  assets/js/settings.js  + settings.min.js
src/admin/css/settings.css               ──esbuild────▶  assets/css/settings.css + settings.min.css
```

`assets/imgs/**` is **source** (plugin icons) — committed, never generated.

---

## npm scripts

| Script | Does |
|--------|------|
| `npm run build` | `wp-scripts build` (blocks) **&&** `build:admin` (admin). The canonical full build. |
| `npm run build:admin` | esbuild one-shot: admin JS+CSS, min + unminified. |
| `npm run watch:admin` | esbuild watch — rebuild admin assets on save. |
| `npm run start` | `wp-scripts start` — watch the **blocks** only. |
| `npm run start:hot` | admin esbuild `--watch` **&** `wp-scripts start` — watch BOTH at once. |
| `npm run zip` / `release:check` | run `build` first, then package. So assets are always fresh in the zip. |

**Rule:** any change under `src/` requires a build. `src/extentions` → `npm run build`
(or `start`). `src/admin` → `npm run build:admin` (or `start:hot` / `watch:admin`).

---

## esbuild builder — `scripts/build-admin.mjs`

- Plain ESM Node script (`.mjs`), no webpack. For each source file it emits **two**
  outfiles: unminified (`settings.js`) and minified (`settings.min.js`); same for CSS.
- `--watch` switches to esbuild `context().watch()` (used by `watch:admin`/`start:hot`).
- To add another admin asset: add an entry to the `sources` array
  (`{ in, outDir, name, ext }`). It auto-gets both min + unminified targets.
- `target: es2017`, `bundle: true`, `legalComments: 'none'`. Keep admin JS framework-free
  (vanilla DOM) — it is enqueued as a plain browser script, not a webpack/React bundle.

---

## Enqueue — register first, then enqueue (`Admin_Settings::enqueue_assets`)

Pattern is mandatory here (matches the main plugin's block enqueue):

```php
wp_register_style(  'rv-settings', $css_url, array(), $css_ver );
wp_register_script( 'rv-settings', $js_url,  array(), $js_ver, true );
wp_localize_script( 'rv-settings', 'rvSettings', array( /* i18n strings */ ) );
wp_enqueue_style(  'rv-settings' );
wp_enqueue_script( 'rv-settings' );
```

Non-negotiables:
- **Page-scoped.** Hooked on `admin_enqueue_scripts`, gated by
  `if ( $hook !== self::$hook ) return;` where `self::$hook` is the return value of
  `add_options_page()`. Never load on every admin screen, never on the frontend.
- **`.min` by default, unminified when `SCRIPT_DEBUG`:**
  `$min = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '' : '.min';`
- **Cache-bust with `filemtime()`** of the actual file (fallback `RV_VERSION`).
- **URLs/paths via `RV_PLUGIN_FILE`** (defined in the main plugin file):
  `plugins_url( $rel, RV_PLUGIN_FILE )` + `plugin_dir_path( RV_PLUGIN_FILE ) . $rel`.
- The settings class is only `require`d inside `is_admin()` in the main file — so this
  method never even loads on the frontend.
- Strings go through `wp_localize_script` (`window.rvSettings`), never echoed inline.

---

## Git vs zip — what is committed vs shipped (critical, easy to get backwards)

| Path | Committed to git? | Shipped in zip? | Why |
|------|-------------------|-----------------|-----|
| `src/**` | ✅ yes | ❌ no | source; excluded by `.buildignore`/`.distignore` |
| `build/**` | ❌ no (`.gitignore`) | ✅ yes | generated; needed at runtime |
| `assets/js`, `assets/css` | ❌ no (`.gitignore`) | ✅ yes | generated; needed at runtime |
| `assets/imgs` | ✅ yes | ✅ yes | source icons |
| `scripts/`, `package.json`, `node_modules` | scripts+pkg yes / modules no | ❌ no | build-time only |

How it stays consistent:
- `.gitignore` ignores `/build`, `/assets/css`, `/assets/js` → generated output never
  committed.
- `.buildignore` (zip) and `.distignore` (wordpress.org) **exclude `src`, `scripts`,
  `node_modules`, `package.json`** but **do NOT exclude `assets` or `build`** → those
  ship.
- `zip` runs `npm run build` first, so the (git-ignored) assets are regenerated right
  before packaging. A clean checkout with no `assets/` still produces a complete zip.

**If you add a new generated asset dir, you must update BOTH** `.gitignore` (to ignore it)
**and confirm** `.buildignore`/`.distignore` do not exclude it (so it ships).

---

## Checklist when changing assets

- [ ] Edited only `src/` — never `build/` or `assets/js`,`assets/css` directly.
- [ ] Ran the right build (`build:admin` for admin, `build` for blocks/full).
- [ ] Admin JS stays vanilla (no React/webpack runtime); strings via `rvSettings`.
- [ ] Enqueue is register→localize→enqueue, page-scoped, `.min`+`SCRIPT_DEBUG`, `filemtime`.
- [ ] New generated path added to `.gitignore`; verified NOT in `.buildignore`/`.distignore`.
- [ ] `npm run zip` → `unzip -l` shows the asset under `responsive-visibility/assets/…`
      and shows **no** `src/`.
- [ ] `node -c assets/js/settings.js` + `settings.min.js` clean; `php -l` clean.

Related: [`../settings-ux/SKILL.md`](../settings-ux/SKILL.md) (what the admin JS/CSS
actually does), [`../custom-breakpoint/SKILL.md`](../custom-breakpoint/SKILL.md) (block
pipeline + data flow).
