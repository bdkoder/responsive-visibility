# Skill: Settings Hardening (Slug Stability + Save/Render Safety)

> Read this whole file before touching `includes/class-admin-settings.php`,
> `includes/class-breakpoints.php`, or `includes/class-render.php`.
> Goal of this work: make the breakpoints settings + render path bulletproof so
> **no block ever silently stops hiding** for old, new, or updating users.

---

## The Problem This Fixes

A breakpoint has two names:

| Name | What it is | Who sees it |
|------|-----------|-------------|
| **label** | Display text the user types: "Mobile", "Tablet", "Widescreen" | User, in settings + Inspector toggles |
| **slug** | Internal code id: `mobile`, `tablet`, `widescreen` | Nobody — internal only |

Blocks store which breakpoint they hide on **by slug**, in the
`hiddenBreakpoints` array attribute (e.g. `["tablet"]`). The frontend CSS also
targets the slug-derived class. So **the slug is the contract between a saved
block and the breakpoint**. It must be stable for life.

### The original bug

`Admin_Settings::process_save()` regenerated the slug from the label on **every
save**:

```php
$slug = sanitize_title( $label );   // "Tablet" -> "tablet", "iPad" -> "ipad"
```

There was no record of the slug a row already had. So:

1. Block saved in post DB with `hiddenBreakpoints:["tablet"]`.
2. User renames the breakpoint **label** "Tablet" → "iPad" and saves.
3. Slug flips `tablet` → `ipad`. The `tablet` slug no longer exists in the option.
4. Dynamic CSS now emits a rule for `rv-hidden--ipad`. The block in the DB still
   carries the `tablet-hidden` class. **No rule matches it. The block shows
   where the editor said hide.** Silent. No PHP/JS error.

This is a **data-integrity bug**, not a crash. It surfaces the moment a user
renames any breakpoint label.

---

## The Fix — Lock the Slug on Creation

**Rule:** a row's slug is generated **once**, when the row is first created.
After that it is carried verbatim through every save and never regenerated, no
matter how the label changes. Only brand-new rows mint a fresh slug.

Mechanism: a hidden `rv_slug[]` input per row, aligned by index with
`rv_label[]` and `rv_max_width[]`.

- **Existing rows** render their stored slug into the hidden field.
- **New rows** (added via the JS "+ Add Breakpoint" button) render an **empty**
  hidden field → that empty value is the signal "mint a slug from the label".
- On save, a non-empty hidden slug is **reused as-is**; an empty one is
  generated from the label.

The 3 default slugs (`mobile`, `tablet`, `desktop`) flow through the hidden
field like any other, so their legacy CSS classes (`mobile-hidden`,
`tablet-hidden`, `desktop-hidden`) are preserved automatically.

---

## Implementation Spec

### 1. `class-admin-settings.php` → `render_page()`

Inside each `<tr class="rv-breakpoint-row">`, add a hidden slug field carrying
the existing slug:

```php
<input type="hidden" name="rv_slug[]" value="<?php echo esc_attr( $bp['slug'] ); ?>" />
```

It must sit **inside the same row** so that removing a row removes all three
fields together and the three `[]` arrays stay index-aligned.

In the inline JS add-row template, append an **empty** slug field so new rows
signal "mint fresh":

```js
'<input type="hidden" name="rv_slug[]" value="" />' +
```

### 2. `class-admin-settings.php` → `process_save()`

Two passes so a reused slug always wins over a new row that happens to generate
the same string, regardless of row order:

```php
private static function process_save() {
    $labels     = isset( $_POST['rv_label'] )     ? (array) wp_unslash( $_POST['rv_label'] )     : array();
    $max_widths = isset( $_POST['rv_max_width'] )  ? (array) wp_unslash( $_POST['rv_max_width'] ) : array();
    $slugs_in   = isset( $_POST['rv_slug'] )       ? (array) wp_unslash( $_POST['rv_slug'] )      : array();

    // Pass 1: reserve every existing (locked) slug so new rows never steal one.
    $slugs_used = array();
    foreach ( $labels as $i => $label ) {
        if ( '' === sanitize_text_field( $label ) ) {
            continue;
        }
        $existing = isset( $slugs_in[ $i ] ) ? sanitize_key( $slugs_in[ $i ] ) : '';
        if ( '' !== $existing ) {
            $slugs_used[] = $existing;
        }
    }

    // Pass 2: build, reusing locked slugs, minting only for new rows.
    $breakpoints = array();
    foreach ( $labels as $i => $label ) {
        $label = sanitize_text_field( $label );
        if ( '' === $label ) {
            continue;
        }

        $existing = isset( $slugs_in[ $i ] ) ? sanitize_key( $slugs_in[ $i ] ) : '';
        if ( '' !== $existing ) {
            $slug = $existing;                       // locked — reuse verbatim
        } else {
            $slug          = sanitize_title( $label ); // new row — mint once
            $original_slug = $slug;
            $counter       = 2;
            while ( in_array( $slug, $slugs_used, true ) ) {
                $slug = $original_slug . '-' . $counter;
                ++$counter;
            }
            $slugs_used[] = $slug;
        }

        $max_width_raw = trim( isset( $max_widths[ $i ] ) ? $max_widths[ $i ] : '' );
        $max_width     = ( '' === $max_width_raw ) ? null : absint( $max_width_raw );

        $breakpoints[] = array(
            'slug'      => $slug,
            'label'     => $label,
            'max_width' => $max_width,
        );
    }

    // ... existing "at least one" + "only one null max_width" validation, then:
    update_option( 'responsive_visibility_breakpoints', Breakpoints::sort( $breakpoints ) );
    return true;
}
```

Note the added `wp_unslash()` before `sanitize_text_field()` — without it a
label containing an apostrophe (`O'Brien`) saves as `O\'Brien`. This was a
second latent bug; fixed here.

### 3. `class-breakpoints.php` → `dynamic_css()` — single-breakpoint guard

Current code makes the **last** breakpoint `min-width` only. With exactly one
breakpoint, that becomes `@media (min-width:0px)` → the block is hidden on
**every** screen. Guard it:

```php
$is_last = ( $i === $count - 1 );

if ( $is_last && $count > 1 ) {
    // largest device, no upper cap
    $css .= "@media (min-width:{$prev_min}px){body .{$class}{display:none!important}}";
} elseif ( 0 === $prev_min ) {
    if ( null === $max_width ) {
        continue; // a lone, uncapped breakpoint defines no hide range — skip
    }
    $css .= "@media (max-width:{$max_width}px){body .{$class}{display:none!important}}";
    $prev_min = $max_width + 1;
} else {
    $css .= "@media (min-width:{$prev_min}px) and (max-width:{$max_width}px){body .{$class}{display:none!important}}";
    $prev_min = $max_width + 1;
}
```

### 4. `class-render.php` → `render_block()` — early short-circuit

Today a `WP_HTML_Tag_Processor` is built for **every** block that has any attrs,
even unrelated ones. Skip the work unless a visibility attr is actually present:

```php
public static function render_block( $block_content, $block, $content ) {
    if ( empty( $block['attrs'] ) ) {
        return $block_content;
    }

    $a   = $block['attrs'];
    $has = ( ! empty( $a['hiddenBreakpoints'] ) && is_array( $a['hiddenBreakpoints'] ) )
        || ! empty( $a['hideOnDesktop'] )
        || ! empty( $a['hideOnTablet'] )
        || ! empty( $a['hideOnMobile'] );

    if ( ! $has ) {
        return $block_content;
    }

    // ... existing Tag_Processor logic unchanged
}
```

---

## Backward Compatibility — Why Nobody Breaks

| User type | State | After fix |
|-----------|-------|-----------|
| **Old / legacy** | Blocks use `hideOnMobile/Tablet/Desktop` booleans, no option saved | Untouched. Render still checks booleans; no option → defaults → identical CSS. |
| **New install** | Defaults, first save mints + locks slugs | Slugs stable from day one. |
| **Updating (has custom breakpoints)** | Option already stores a `slug` per breakpoint | Next save reuses those exact slugs via the hidden field. Existing blocks keep hiding; renames are now safe. Before they even re-save, nothing changes — same option, same CSS. |
| **Already drifted** (renamed a label pre-fix) | Slug already diverged, some blocks orphaned | Cannot auto-recover the lost original slug without scanning every post (rejected — violates perf rules). Exposure ≈ 0 because custom breakpoints shipped in v1.1.0; the lock prevents all future drift. |

**Default-state invariant (must hold):** with no option saved, the generated CSS
must be byte-identical to the pre-fix output:

```css
@media (max-width:767px){body .mobile-hidden{display:none!important}}
@media (min-width:768px) and (max-width:1024px){body .tablet-hidden{display:none!important}}
@media (min-width:1025px){body .desktop-hidden{display:none!important}}
```

---

## Testing Checklist

- [ ] No option saved → CSS byte-identical to the block above.
- [ ] Save defaults unchanged → option stores slugs `mobile/tablet/desktop`.
- [ ] **Rename test:** add custom "Widescreen" (1600), hide a block on it, then
      rename label "Widescreen" → "Wide", save. Block **still hidden**. Option
      slug stays `widescreen`. (Pre-fix this broke.)
- [ ] Rename a default: "Mobile" → "Phone". Slug stays `mobile`; legacy
      `mobile-hidden` class still emitted; old blocks unaffected.
- [ ] Add a new row → it mints a slug from its label; a new row whose label
      collides with an existing slug gets `-2`, the existing slug is untouched.
- [ ] Label with apostrophe (`O'Brien`) saves and re-renders without `\'`.
- [ ] Single breakpoint with a max_width → `max-width` rule only (not hidden
      everywhere). Single breakpoint with blank max_width → no rule emitted.
- [ ] Block with no visibility attrs → `render_block` returns early, no
      Tag_Processor built (frontend HTML unchanged).
- [ ] Legacy boolean block (`hideOnMobile:true`, no array) → still hidden.
- [ ] `php -l` clean on all three `includes/class-*.php` files.

---

## Files Touched

| File | Change |
|------|--------|
| `includes/class-admin-settings.php` | Hidden `rv_slug[]` field in row + add-row JS; two-pass slug-preserving `process_save()`; `wp_unslash()` on label/max_width. |
| `includes/class-breakpoints.php` | Single-breakpoint guard in `dynamic_css()`. |
| `includes/class-render.php` | Early short-circuit before `WP_HTML_Tag_Processor`. |

No JS build needed — all changes are PHP. The slug is never surfaced in the
editor; `settings.js` and `block-wrapper.js` are unchanged.

Related: [`../custom-breakpoint/SKILL.md`](../custom-breakpoint/SKILL.md) for the
full breakpoint data flow.
