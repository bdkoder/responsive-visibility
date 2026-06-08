# Feature Skill — Login-Status Visibility

> Read before coding this feature. WHAT it does, WHY each decision, and the
> backward-compat / cache rules that must hold. Status: **SUPERSEDED — folded into the
> conditions engine as the `authentication` condition (v1.2.0).** The standalone
> `loginVisibility` attribute described below was replaced by `rvConditions` before release;
> the server-removal + cache reasoning here still holds and underpins the whole engine.
> Build details now live in `.ai/conditions-engine/SKILL.md`. Keep this for the rationale.

## What

Per-block control to show or hide a block based on whether the visitor is **logged in**
or **logged out** of WordPress. The first "condition" beyond device — makes the plugin
title's "& Conditions" honest.

Closes the only table-stakes gap: all 4 tracked competitors have login-status; RV does not
(see `.ai/competitors/`). RV ships it **free** (VCEB gates it behind Pro).

## Semantics (single select — not two toggles)

One attribute, three states. A single select prevents the contradictory "hide from both"
state that two booleans allow.

| `loginVisibility` value | Meaning | Front-end result |
|-------------------------|---------|------------------|
| `''` (default) | Everyone | Block always renders. **Zero behavior change** (back-compat). |
| `logged-in` | Logged-in users only | Block **removed** for guests (`! is_user_logged_in()`). |
| `logged-out` | Logged-out visitors only | Block **removed** for members (`is_user_logged_in()`). |

UI label (SelectControl): "Show this block to" → Everyone / Logged-in users only /
Logged-out visitors only.

## Mechanism — SERVER-SIDE removal (NOT CSS)

Device hiding uses CSS classes (block stays in HTML, media-query hides it). **Login-status
cannot** — two reasons:
1. CSS has no way to know auth state.
2. Member-only content must NOT sit in the page source for guests to read/scrape. CSS
   `display:none` would still leak it. Server-side removal is the only correct option.

So this is a **second, independent mechanism** bolted alongside the CSS one. They compose:
the login gate runs first (may remove the block entirely); if the block survives, the
existing device-class logic runs as before.

### `Render::render_block()` new structure

```php
public static function render_block( $block_content, $block, $content ) {
    if ( empty( $block['attrs'] ) ) {
        return $block_content;
    }
    $a = $block['attrs'];

    // 1. Login-status gate — server-side removal, runs BEFORE device logic.
    if ( ! empty( $a['loginVisibility'] ) ) {
        $lv = $a['loginVisibility'];
        if ( 'logged-in' === $lv && ! is_user_logged_in() ) {
            return ''; // members-only block, viewer is a guest
        }
        if ( 'logged-out' === $lv && is_user_logged_in() ) {
            return ''; // guests-only block, viewer is logged in
        }
    }

    // 2. Device hiding (CSS classes) — existing logic, unchanged.
    $has_attr = ( ! empty( $a['hiddenBreakpoints'] ) && is_array( $a['hiddenBreakpoints'] ) )
        || ! empty( $a['hideOnDesktop'] )
        || ! empty( $a['hideOnTablet'] )
        || ! empty( $a['hideOnMobile'] );
    if ( ! $has_attr ) {
        return $block_content;
    }
    // ... WP_HTML_Tag_Processor add_class loop as today ...
}
```

### Why `return ''` and not `null`
Returning `null` from `render_block` broke third-party plugins (Sensei) in Block Visibility's
history — an empty string is the safe "render nothing" value. Always `''`.

## Editor preview (keep minimal)

In the editor `is_user_logged_in()` is always true (you're an admin), so PHP can't preview the
logged-out case. **Do NOT hide the block in the editor** — the user must still see/edit it.
Minimal approach: a short helper note under the SelectControl, e.g. *"This block will be
removed on the front end for logged-out visitors."* No wrapper styling needed. (A badge can
come later with the B2 editor-badge feature — out of scope here.)

## Backward compatibility (non-negotiable)

- Purely **additive**: new attribute `loginVisibility` defaults `''` → existing blocks render
  identically. No migration needed.
- Legacy device system (`hideOnDesktop/Tablet/Mobile`) and `hiddenBreakpoints` untouched.
- Old blocks have no `loginVisibility` key → `empty()` short-circuits the gate → no change.
- The device-attr short-circuit must stay AFTER the login gate (a login-only block with no
  device hiding must still be gated, then fall through to the unchanged `return $block_content`).

## Cache caveat (DOCUMENT in readme FAQ)

Server-side removal + full-page cache: a cached page is shared across visitors, so the cache
must **vary or bypass for logged-in users** for login rules to be reliable. Every major cache
plugin (WP Rocket, W3TC, LiteSpeed, hosting cache) does this by default for logged-in users.
Device rules remain CSS/cache-safe; only login rules carry this caveat. Same caveat VCEB Pro
documents.

## Security

- Sanitize `loginVisibility` against an allowlist before use:
  `in_array( $lv, array( 'logged-in', 'logged-out' ), true )` — never trust the stored value.
- No output of the value into HTML/CSS, so no XSS surface (unlike slugs). Allowlist is for
  correctness, not escaping.

## Files to touch

| File | Change |
|------|--------|
| `src/extentions/visibility-conditions/block.json` | New editor-only extension entry (wp-scripts auto-builds) |
| `src/extentions/visibility-conditions/index.js` | Registers the 2 addFilter hooks (attributes + inspector) |
| `src/extentions/visibility-conditions/components/attributes.js` | `loginVisibility: { type: 'string', default: '' }` |
| `src/extentions/visibility-conditions/components/settings.js` | "Visibility Conditions" PanelBody + `SelectControl` + helper note |
| `includes/class-render.php` | Login gate before device logic (above) |
| `responsive-visibility.php` | Add `visibility-conditions` to the `$extentions` enqueue array; CSS/localize now file-existence-guarded |
| `readme.txt` | Feature bullet + FAQ (cache caveat) + changelog/upgrade |
| `CLAUDE.md` | `loginVisibility` in the attributes section |

**Lives in its OWN extension folder** (`src/extentions/visibility-conditions/`), NOT mixed into
the device extension (`src/extentions/responsive-visibility/`). Each feature = one extension =
one build entry. The conditions extension is editor-only (no frontend CSS — hiding is
server-side in `Render`). Tier-1 conditions (role, post, …) grow inside this same folder.
See `.ai/conditions-engine/SKILL.md`.

## NEVER
1. Use CSS to hide login-gated blocks — server-side removal only (leak risk).
2. Return `null` from `render_block` — use `''`.
3. Trust `loginVisibility` without the allowlist check.
4. Hide the block in the **editor** — only on the front end.
5. Remove/rename the device attrs or change the device short-circuit order so it skips the gate.

## ALWAYS
1. Default `loginVisibility` to `''` (everyone) — back-compat.
2. Run the login gate BEFORE the device-class logic.
3. `npm run build` after editing `src/`; `php -l` the render class.
4. Document the cache caveat in the readme FAQ.

## Test plan (manual)

1. Default block (no setting) → renders for guest + member. (back-compat)
2. `logged-in` → visible to member, **absent from HTML source** for guest.
3. `logged-out` → visible to guest, absent for member.
4. Login rule + device rule together → gated first, then device class applied if it survives.
5. Old block saved pre-feature → unchanged.
6. Inner/nested block gated → no layout break (returns `''`).
7. Editor → block still visible/editable in all three states; helper note shows.
