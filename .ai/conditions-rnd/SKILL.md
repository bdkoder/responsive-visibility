# R&D — Porting Sky Elementor "Display Conditions" to Blocks

> Source studied: `sky-elementor-addons-pro/modules/display-conditions` (24 conditions, ~3k
> lines). Question: can the same advanced conditions engine be built for Gutenberg blocks as
> a block extension? **Verdict: YES — ~80% of the PHP ports near-verbatim; the real work is a
> React repeater UI.** This is the bigger architecture the v1.2.0 login-status feature seeds.

## How their Elementor module is built (clean, mirror it)

- **`Condition` (abstract base)** — every condition is one class with: `get_name()`,
  `get_title()`, `get_group()`, `get_control_value()` (UI control config), `check($relation,
  $val, …)` (returns bool / null). Plus a shared `compare($l, $r, $relation)` (is / is not).
- **`Module` (orchestrator)** — registers all conditions into a registry, builds ONE UI:
  - master switch `enable`, `to` = Show|Hide, `relation` = All|Any conditions met,
  - a **Repeater** of condition rows (unlimited): each row = pick condition → operator
    (is/not) → value control specific to that condition.
  - Evaluates on render: `set_conditions()` runs each row's `check()`, `is_visible()` folds
    them with all/any, then show/hide decides render.
- **Render hook:** `elementor/frontend/{widget,container}/should_render` filter → returns
  bool. **RV's equivalent already exists: the `render_block` filter** (we use it today).
- **Cache:** they mark elements dynamic to bypass Elementor's *element* cache. Defensive code
  uses **strict** `in_array(true, …, true)` + null guards so a void `check()` never wrongly
  hides. Mirror these patterns.

## Key insight: the `check()` logic is framework-agnostic

Every condition's `check()` is **pure WordPress/PHP** — nothing Elementor-specific:
- `authentication`: `is_user_logged_in()`
- `role`: `wp_get_current_user()->roles`
- `url_parameters`: `$_SERVER['REQUEST_URI']` + `$_GET`
- `date`/`time`/`day`/`month`: `current_time()` / `wp_date()`
- `post`/`post_type`/`static_page`: `get_queried_object()`, `is_front_page()`, etc.

→ These bodies drop straight into RV with only the namespace + control-config layer swapped.
**Only two layers are Elementor-bound:** (1) the UI controls (`Controls_Manager`/`Repeater`),
(2) the `should_render` hook. Both have clean Gutenberg equivalents.

## Portability of all 24 conditions

| Group | Conditions | Mechanism | Port difficulty | Notes |
|-------|-----------|-----------|-----------------|-------|
| User | authentication, user, role | server (`render_block`→`''`) | **Easy** | auth already shipped (v1.2.0). Pure WP. |
| Post | post, post_type, static_page | server | **Easy** | `get_queried_object`, `is_*` template tags. |
| URL | ex_url, url_parameters, url_string, search_engine_url | server | **Easy–Med** | `$_SERVER`/`$_GET`. ⚠️ per-request → full-page cache conflict. |
| Date & Time | date, time, day, month, time_range, date_time_before | server | **Easy–Med** | `current_time()`. ⚠️ per-request → cache conflict; needs site timezone care. |
| System | os, browser, language | server | **Med** | user-agent / `Accept-Language` parsing. Bot/UA reliability caveats. |
| System | country | server | **Hard** | needs **GeoIP** (IP database or external API) — new dependency. |
| System | visit_count, session_count | server + **cookies** | **Med–Hard** | sets cookies; cookies + full-page cache fight. |
| Misc | shortcode | server | **Easy** | `do_shortcode` truthiness. |

## What a block port looks like

**Storage (block attributes):** mirror their structure as
```
displayConditions      : array   // repeater rows: [{ key, operator, value, … }]
displayConditionsEnable: boolean
displayConditionsTo    : string  // 'show' | 'hide'
displayConditionsRelation: string// 'all' | 'any'
```
(`loginVisibility` from v1.2.0 becomes a special-case / migrates into this.)

**UI (the real work):** a React Inspector repeater in `src/extentions/.../components/`:
- map over rows; each row: condition `SelectControl` (grouped) + operator select + a
  **value control that varies by condition type** (text, number, select, multiselect,
  datetime). This is what Elementor gives declaratively and we must hand-build.
- "Add condition" / remove buttons; All/Any + Show/Hide selects.

**PHP (easy):** port the abstract `Condition` base + `conditions/*.php` into
`includes/conditions/` under `WowDevs\Responsive_Visibility`. A `Conditions` evaluator runs
inside the existing `Render::render_block()` and returns `''` when the row set says hide.
~80% of each `check()` body copies over.

**Editor preview:** `render_block` is frontend-only, so the editor can't run checks (same as
login-status). Show the block normally + a badge/notice listing active conditions.

## Challenges / honest limits

1. **Full-page cache vs per-request conditions.** auth/role/cookies → cache must vary for
   logged-in (standard). But date/time/url/country/visit-count change **per request**; with
   full-page cache the page is frozen at first render → these are unreliable under cache.
   Elementor has the same limitation (they only bypass *element* cache, not page cache).
   Honest fix options: document it, and/or a future client-side JS eval mode for the
   cache-unsafe ones (this is exactly what VCEB Pro sells as "cache-friendly frontend mode").
2. **GeoIP (country)** = external dependency (MaxMind DB or API). Heaviest single item.
3. **UA parsing (os/browser)** = unreliable, bot-spoofable. Low-trust conditions.
4. **UI surface area.** 24 value-controls in React is the bulk of the effort, not the logic.

## Effort & verdict

- **Feasible: clearly yes.** Architecture maps cleanly; `render_block` is a *better* fit than
  Elementor's hooks (we already own it). PHP logic mostly copy-paste.
- **Effort: medium-large**, almost all in the React repeater UI + per-condition value inputs.
  Shippable **incrementally** — one condition at a time (auth ✓ → role → post/post_type →
  date/time → url → system). Each new condition = 1 PHP class + 1 React value-control.
- **Recommended architecture:** build the extensible `Condition` base + registry now (mirror
  theirs), refactor v1.2.0 login-status into it as condition #1, then add conditions over
  releases.

## Brand / product caution

This converts RV from "simple responsive plugin" into a **full conditions engine** — i.e.
Block Visibility / Sky-Pro parity. User's standing guidance: *"simple plugin, less feature."*
So treat this as a deliberate strategic expansion, not feature-creep. Strong candidate for a
**Free vs Pro split** (copy VCEB): Free = device + breakpoints + login-status; **Pro = the
advanced conditions** (role, date/time, url, post, system). The new title's "& Conditions"
already sets up this story.

## Reuse map (when building)

- Copy pattern: `condition.php` (base) + `conditions/*.php` (`get_*` + `check`).
- `compare()` + strict `in_array(…, true)` + null-guard logic → copy verbatim (battle-tested).
- Evaluator lives in existing `Render` (return `''` to drop, like login-status).
- See `.ai/login-status/SKILL.md` for the server-removal + cache-caveat groundwork.
