# Feature Skill — Block Conditions Engine (RV)

> The BUILD design for an extensible conditions engine in Responsive Visibility, ported from
> the Sky Elementor "Display Conditions" architecture (analysis: `.ai/conditions-rnd/SKILL.md`).
> Status: **Tier-1 BUILT (v1.2.0).** Engine + 7 conditions shipped: authentication, role,
> user, post, post_type, static_page, shortcode. Login-status is now the `authentication`
> condition (the standalone `loginVisibility` attr was replaced before release).
> Read with `.ai/login-status/SKILL.md` (server-removal + cache groundwork).
>
> **Built layout:** PHP — `includes/class-conditions.php` (registry/evaluator/JS schema) +
> `includes/conditions/{abstract-condition,class-*}.php`. JS — `src/extentions/
> visibility-conditions/` (`lib/conditions.js`, `components/{ConditionsPanel,ConditionRow,
> ConditionValue,settings,attributes}.js`, `editor.scss`). Storage attr `rvConditions`.
> Stable @wordpress/components only (no experimental); `__nextHasNoMarginBottom` on controls;
> `TextControl type=number` (not experimental NumberControl). Evaluator returns `''` in
> `Render::render_block`. **Tier-2 partial:** `url_param` shipped (see plan:
> `.ai/conditions-engine/url-query-params.md`). Date/time/system/country not built.

## Goal

Per-block, unlimited **conditions** (login, role, date/time, URL, post, system) with Show/Hide
+ All/Any logic — the engine that makes the plugin title's "& Conditions" fully real. Built so
each new condition = **1 PHP class + 1 React value-control**, nothing else.

## Architecture (mirror theirs, in RV's namespace)

### PHP — `includes/conditions/`
- `abstract class Condition` (base): `name()`, `title()`, `group()`, `value_control()` (UI
  schema for JS), `check( $operator, $value )` → `bool|null`, shared `compare($l,$r,$op)`.
- `conditions/*.php` — one class each. `check()` bodies copy ~verbatim from Sky-Pro (pure WP).
- `class Conditions` (registry + evaluator): registers condition classes, and inside the
  existing `Render::render_block()` evaluates a block's saved rows → decides remove or keep.

### Evaluation (reuse `render_block`, return `''` to drop)
```
render_block():
  1. Conditions gate  ← NEW evaluator: if rows say "hide", return ''
  2. Login-status gate (v1.2.0 — folds INTO the engine as condition 'authentication')
  3. Device CSS classes (unchanged)
```
Fold All/Any with **strict** `in_array(true/false, $results, true)` + null-guard (copy their
defensive pattern: a void `check()` must never silently hide).

### Storage (block attributes — additive, back-compat)
```
conditionsEnable   : boolean (default false)
conditionsTo       : string  'show' | 'hide'      (default 'show')
conditionsRelation : string  'all' | 'any'        (default 'all')
conditions         : array   [{ id, key, operator, value }]   (default [])
```
`loginVisibility` (v1.2.0) stays valid; engine reads it as a built-in `authentication` row OR
keeps it as a parallel shortcut. Default empty → existing blocks unchanged.

### UI — React repeater (the bulk of the work)
`src/extentions/.../components/` new `ConditionsPanel`:
- lives in the "Visibility Conditions" PanelBody (already created in v1.2.0).
- master toggle, Show/Hide select, All/Any select.
- repeater rows: `+ Add condition`; each row = condition `SelectControl` (grouped by
  User/Post/URL/Date/System) → operator (is/is-not) → **value control that varies per
  condition type** (text / number / select / multiselect / datetime).
- condition metadata (groups, value-control schema) shipped to JS via `wp_localize_script`
  (like `rvBreakpoints`) so PHP stays the single source of truth.

### Editor preview
`render_block` is frontend-only → editor can't evaluate. Show block normally + a badge/notice
listing active conditions (same approach as login-status helper note).

## Backward compatibility
- Purely additive; all new attrs default to off/empty → zero change to existing blocks.
- Device system + `loginVisibility` untouched; engine runs alongside, gate order above.
- No migration required; optional later: migrate `loginVisibility` into a condition row.

## Cache rule (carry from login-status)
Server-evaluated. auth/role → cache must vary for logged-in (standard). **Per-request
conditions (date/time/url/country/visit-count) are unreliable under full-page cache** — page
frozen at first render. Document per condition; optional future JS-eval mode for the
cache-unsafe ones (VCEB-Pro's "cache-friendly frontend" model).

## Complexity ranking (which features are hard, and WHY)

**Tier 1 — Easy (pure WP, cache-safe-ish, do first):**
- `authentication` (done v1.2.0), `role`, `user` — `is_user_logged_in`, roles, user id.
- `post`, `post_type`, `static_page` — `get_queried_object`, `is_front_page`/`is_*`.
- `shortcode` — `do_shortcode` truthiness.
Why easy: one-line WP checks, no deps, vary only by login (cache-friendly via logged-in bypass).

**Tier 2 — Medium (logic simple, but UI or cache friction):**
- `date`, `time`, `day`, `month`, `time_range`, `date_time_before` — logic trivial, but need
  **datetime value-controls** in React + careful **site timezone** (`current_time`/`wp_date`)
  AND they're **per-request → break under full-page cache**.
- `url_parameters`, `url_string`, `ex_url`, `search_engine_url` — `$_GET`/`$_SERVER` parsing;
  also per-request → cache conflict. Sanitization care.
Why medium: not the logic — the value-control UI variety + the cache caveat.

**Tier 3 — Hard (external deps / unreliable / cache-hostile):**
- `country` — **hardest single item.** Needs **GeoIP**: a MaxMind DB shipped/updated, or an
  external IP-geolocation API (privacy + latency + a new external-service disclosure in
  readme). Per-request → cache-hostile.
- `os`, `browser`, `language` — user-agent / `Accept-Language` parsing; **spoofable, bot-noisy,
  low-trust**; UA strings drift over time (maintenance).
- `visit_count`, `session_count` — require **setting cookies** on the front end; cookies +
  full-page cache fight; needs a counter mechanism. Stateful = most moving parts.

**The genuinely complex part overall is NOT any single condition's logic — it's:**
1. The **React repeater UI** with per-condition value-controls (most of the effort).
2. **`country` (GeoIP)** — the only item needing an external dependency + service disclosure.
3. **Cache correctness** for per-request conditions (the architectural caveat, not code).

## Phased rollout (ship incrementally)
1. ✅ DONE — `Condition` base + registry + evaluator; `authentication` folded in. (foundation)
2. ✅ DONE — React repeater UI + Tier-1 (role, user, post, post_type, static_page, shortcode).
3. ✅ PARTIAL — `url_param` shipped (existence / exact / allow-list syntax). Plan: `url-query-params.md`.
4. ⬜ Tier-2 remainder (date/time family, url_string, ex_url, search_engine_url) + datetime controls + cache docs.
5. ⬜ Tier-3 last, optional/Pro (country/GeoIP, os/browser/language, visit/session count).

## Product note
This is the "simple plugin → conditions engine" expansion. Strong **Free vs Pro** candidate:
Free = device + breakpoints + login (Tier-1); **Pro = advanced conditions (Tier-2/3)**.
See `.ai/competitors/` (VCEB's split is the template).

## Extensibility / Pro contract (do NOT break — add-ons depend on it)

Built so a Pro/3rd-party add-on adds conditions WITHOUT editing core:
- **Register conditions:** `add_filter( 'responsive_visibility_condition_classes', … )` — append
  FQ Condition subclass names. Add-on loads its own class files first. (Lazy `all()` runs after
  plugins_loaded, so the filter is in place.)
- **Register groups:** `add_filter( 'responsive_visibility_condition_groups', … )` — e.g. add
  WooCommerce/ACF groups.
- **`Condition` base contract is STABLE.** Abstract methods today: `get_type`, `get_label`,
  `get_group`, `matches`. **NEVER add a new abstract method** (it fatals every existing
  subclass incl. Pro) — add new behavior as a CONCRETE method with a safe default
  (like `get_value_field()` → null).
- **Unknown value-control types degrade safely:** `ConditionValue` renders any unrecognized
  `control` as a text field, so a Pro condition shipping a new control type never white-screens
  the free editor (Pro ships its own JS to render it richly).
- **Storage is forward-compatible:** `rvConditions` is a single object; `ConditionsPanel`
  spreads over `DEFAULT_CONFIG` and `should_hide` reads keys defensively, so adding fields
  later won't break old saved blocks.

Behavior note: conditions also apply during REST/server-render (a dynamic block's gated
content never leaks via `wp-json`). The editor *canvas* is client-side, so blocks are never
hidden while editing.

## Audit status (v1.2.0)
Reviewed: strict bool folding + null-guards correct; default-off back-compat holds; security
(sanitize_key/absint per condition, allowlisted operator); namespace/class collision verified;
Pro filters added. **Open for multiselect (Tier-1.5):** when a value can be an array, update
`evaluate_rule`'s empty-value guard (`'' === $value`) to also treat `array()` as incomplete.

## NEVER / ALWAYS
- NEVER hide via CSS for server conditions (leak); return `''` not `null`; never trust stored
  `value`/`key` without allowlist/sanitize; never add an abstract method to `Condition`.
- ALWAYS default new attrs off/empty (back-compat); strict `in_array(…, true)` + null-guard;
  PHP is the source of truth for condition metadata (localize to JS); document cache caveat
  per condition; `npm run build` + `php -l` after changes.
