# Skill: Custom Breakpoints

> This skill document explains everything about the Custom Breakpoints feature in the Responsive Visibility plugin. Any AI agent working on this feature should read this entire file before making any changes.

---

## What This Feature Does

Users can define their own breakpoint pixel values (instead of the hardcoded defaults) via a settings page. They can add, edit, or remove breakpoints — similar to how Elementor handles breakpoints. Each breakpoint gets its own toggle in every block's Inspector panel. The CSS is generated dynamically from the saved values on every page load.

**Default breakpoints (used when no settings are saved):**
| Label | Max Width | CSS Class | Device |
|-------|-----------|-----------|--------|
| Mobile | 767px | `mobile-hidden` | ≤767px |
| Tablet | 1024px | `tablet-hidden` | 768–1024px |
| Desktop | none (largest) | `desktop-hidden` | ≥1025px |

---

## Full Data Flow — Start to Finish

### 1. User saves breakpoints on the settings page
**File:** `includes/class-rest-breakpoints.php` → `Rest_Breakpoints::save_breakpoints()`

The settings page is a React app (`src/admin`, see `.ai/admin-react/SKILL.md`). Saving
POSTs the rows to REST `responsive-visibility/v1/breakpoints`. The controller:
- Sanitizes each row: `sanitize_text_field( wp_unslash( label ) )`, `absint`/`null` max_width
- **Slug-lock (two-pass):** reuses each row's existing `slug` verbatim; mints
  `sanitize_title( label )` (with `-2`,`-3` collision bump) ONLY for new rows — so renaming
  a label never changes a slug. See `.ai/settings-hardening/SKILL.md`.
- Validates (≥1 breakpoint, only one `null` max_width) → `WP_Error` 400 on failure
- Sorts ascending by `max_width`, `null` last
- Saves to `wp_options` key: `responsive_visibility_breakpoints`

**Stored schema:**
```php
[
  ["slug" => "mobile",     "label" => "Mobile",     "max_width" => 767],
  ["slug" => "tablet",     "label" => "Tablet",     "max_width" => 1024],
  ["slug" => "widescreen", "label" => "Widescreen", "max_width" => 1600],
  ["slug" => "desktop",    "label" => "Desktop",    "max_width" => null],
]
```

---

### 2. PHP passes breakpoints to the block editor (JavaScript)
**File:** `responsive-visibility.php` → `responsive_visibility_init()`

On every admin page load, `wp_localize_script()` injects the saved breakpoints into the editor:
```php
wp_localize_script(
    'responsive-visibility-editor-script',
    'rvBreakpoints',
    [
        'breakpoints' => get_option('responsive_visibility_breakpoints', Breakpoints::get_defaults()),
        'settingsUrl' => admin_url('options-general.php?page=responsive-visibility'),
    ]
);
```
This makes `window.rvBreakpoints` available globally in the editor JavaScript.

---

### 3. Inspector panel renders dynamic toggles
**File:** `src/extentions/responsive-visibility/components/settings.js`

Reads `window.rvBreakpoints.breakpoints` and renders one `ToggleControl` per breakpoint:
```jsx
const breakpoints = window?.rvBreakpoints?.breakpoints || DEFAULT_BREAKPOINTS;

{breakpoints.map((bp) => (
    <ToggleControl
        key={bp.slug}
        label={sprintf(__("Hide on %s"), bp.label)}
        checked={hiddenBreakpoints.includes(bp.slug)}
        onChange={(value) => toggleBreakpoint(bp.slug, value)}
    />
))}
```

When a toggle is turned ON: slug is added to the `hiddenBreakpoints` array on the block.
When a toggle is turned OFF: slug is removed from the array.

**Block attribute being updated:**
```js
hiddenBreakpoints: ["mobile", "widescreen"]  // array of active slug strings
```

---

### 4. Auto-migration of old blocks
**File:** `src/extentions/responsive-visibility/components/settings.js` → `useEffect`

When a block is opened that has the old boolean attributes (`hideOnMobile: true`) but no `hiddenBreakpoints` array yet, it auto-migrates:
```js
useEffect(() => {
    if ((!attributes.hiddenBreakpoints || attributes.hiddenBreakpoints.length === 0) &&
        (attributes.hideOnDesktop || attributes.hideOnTablet || attributes.hideOnMobile)) {
        const migrated = [];
        if (attributes.hideOnDesktop) migrated.push("desktop");
        if (attributes.hideOnTablet)  migrated.push("tablet");
        if (attributes.hideOnMobile)  migrated.push("mobile");
        setAttributes({ hiddenBreakpoints: migrated });
    }
}, []);
```
This runs once when the block mounts. Old blocks silently upgrade. The old boolean attrs are NOT removed — PHP still reads them for full backward compat.

---

### 5. Editor live preview shows stripe on hidden blocks
**File:** `src/extentions/responsive-visibility/components/block-wrapper.js`

Maps each slug in `hiddenBreakpoints` to a CSS class, then checks current device preview type:
```js
function rvClassForSlug(slug) {
    const legacyMap = { mobile: "mobile-hidden", tablet: "tablet-hidden", desktop: "desktop-hidden" };
    return legacyMap[slug] || `rv-hidden--${slug}`;
}

const DEVICE_TYPE_MAP = { desktop: "Desktop", tablet: "Tablet", mobile: "Mobile" };

(attributes?.hiddenBreakpoints || []).forEach((slug) => {
    const cssClass = rvClassForSlug(slug);
    const expectedDevice = DEVICE_TYPE_MAP[slug];
    if (expectedDevice) {
        // Standard slug: show stripe only when preview matches device
        classObj[cssClass] = deviceType === expectedDevice;
    } else {
        // Custom slug (e.g. widescreen): no device type match, always show stripe
        classObj[cssClass] = true;
    }
});
```

The diagonal stripe comes from `editor.scss` — it makes any element with a `-hidden` class show as striped instead of disappearing, so editors can still select and edit it.

---

### 6. Dynamic CSS is generated on every frontend page load
**File:** `includes/class-breakpoints.php` → `Breakpoints::dynamic_css()`
**Hook:** `wp_head` priority 99 (runs AFTER all styles are enqueued)

Reads the saved breakpoints, sorts them, and generates media query CSS:
```php
$breakpoints = get_option('responsive_visibility_breakpoints', Breakpoints::get_defaults());

// Sort: null max_width values go last
usort($breakpoints, function($a, $b) {
    if (null === $a['max_width'] && null === $b['max_width']) return 0;
    if (null === $a['max_width']) return 1;
    if (null === $b['max_width']) return -1;
    return $a['max_width'] - $b['max_width'];
});

$prev_min = 0;
foreach ($breakpoints as $i => $bp) {
    $class   = Breakpoints::class_for_slug($bp['slug']);
    $is_last = ($i === count($breakpoints) - 1);

    if ($is_last) {
        // Last = largest device, no upper limit
        "@media (min-width:{$prev_min}px){ body .{$class}{ display:none!important } }"
    } elseif ($prev_min === 0) {
        // First = smallest device, no lower limit
        "@media (max-width:{$bp['max_width']}px){ body .{$class}{ display:none!important } }"
        $prev_min = $bp['max_width'] + 1;
    } else {
        // Middle devices: bounded range
        "@media (min-width:{$prev_min}px) and (max-width:{$bp['max_width']}px){ body .{$class}{ display:none!important } }"
        $prev_min = $bp['max_width'] + 1;
    }
}

echo '<style id="rv-dynamic-breakpoints">' . $css . '</style>';
```

**Example output for default 3 breakpoints:**
```css
<style id="rv-dynamic-breakpoints">
@media (max-width:767px){body .mobile-hidden{display:none!important}}
@media (min-width:768px) and (max-width:1024px){body .tablet-hidden{display:none!important}}
@media (min-width:1025px){body .desktop-hidden{display:none!important}}
</style>
```

**Example output with custom "Widescreen" at 1600px:**
```css
@media (max-width:767px){body .mobile-hidden{display:none!important}}
@media (min-width:768px) and (max-width:1024px){body .tablet-hidden{display:none!important}}
@media (min-width:1025px) and (max-width:1600px){body .rv-hidden--widescreen{display:none!important}}
@media (min-width:1601px){body .desktop-hidden{display:none!important}}
```

---

### 7. PHP adds CSS classes to block HTML on render
**File:** `includes/class-render.php` → `Render::render_block()`
**Hook:** `render_block` filter

```php
// New system: hiddenBreakpoints array
foreach ($block['attrs']['hiddenBreakpoints'] ?? [] as $slug) {
    $tags->add_class(Breakpoints::class_for_slug(sanitize_key((string)$slug)));
}

// Legacy: old boolean attributes (never remove these checks)
if (!empty($block['attrs']['hideOnDesktop'])) $tags->add_class('desktop-hidden');
if (!empty($block['attrs']['hideOnTablet']))  $tags->add_class('tablet-hidden');
if (!empty($block['attrs']['hideOnMobile']))  $tags->add_class('mobile-hidden');
```

---

## CSS Class Naming Rules

This is critical — changing these breaks existing sites:

```php
// In PHP: Breakpoints::class_for_slug($slug)
// In JS:  rvClassForSlug(slug)
// Both must always return identical values:

"mobile"   → "mobile-hidden"        // legacy, never change
"tablet"   → "tablet-hidden"        // legacy, never change
"desktop"  → "desktop-hidden"       // legacy, never change
anything else → "rv-hidden--{slug}" // custom breakpoints
```

---

## Helper Functions

### PHP (in `includes/class-breakpoints.php`)
```php
Breakpoints::get_defaults()
// Returns the default 3-breakpoint array (mobile/tablet/desktop)
// Used as fallback in get_option() calls and wp_localize_script()

Breakpoints::class_for_slug($slug)
// Maps a breakpoint slug to its CSS class name
// Always sanitize_key() the slug before passing here
```

### JavaScript (in `block-wrapper.js`)
```js
rvClassForSlug(slug)
// Identical logic to PHP Breakpoints::class_for_slug()
// Must stay in sync with PHP version at all times
```

---

## Settings Page

**URL:** `wp-admin/options-general.php?page=responsive-visibility`
**File:** `includes/class-admin-settings.php`
**Option key:** `responsive_visibility_breakpoints`

The settings page renders a table of rows. Each row = one breakpoint.
- Leave **Max Width** blank → that breakpoint has no upper limit (becomes the largest device)
- The **last sorted breakpoint** always gets `min-width` only in the generated CSS
- JavaScript handles add/remove rows dynamically (no page reload needed)
- Minimum 1 row — remove button is disabled when only one row remains

---

## How to Add a New Breakpoint Programmatically (Developer Filter)

If a theme developer wants to set breakpoints without using the admin UI, they can filter the option:
```php
// In theme's functions.php or a plugin
add_filter('option_responsive_visibility_breakpoints', function($breakpoints) {
    return [
        ['slug' => 'mobile',  'label' => 'Mobile',  'max_width' => 600],
        ['slug' => 'tablet',  'label' => 'Tablet',  'max_width' => 900],
        ['slug' => 'desktop', 'label' => 'Desktop', 'max_width' => null],
    ];
});
```
> Note: This filter is not officially documented yet — it is a future roadmap item. The architecture supports it natively.

---

## Common Mistakes to Avoid

| Mistake | Why it breaks | Correct approach |
|---------|--------------|-----------------|
| Removing old `hideOnMobile/Tablet/Desktop` checks from PHP render | Old blocks stop hiding on frontend silently | Always keep BOTH checks in `Render::render_block()` |
| Changing `"mobile"` slug → `"mobile-hidden"` mapping | Existing blocks with `hiddenBreakpoints: ["mobile"]` get wrong CSS class | The legacy map in `rvClassForSlug` is permanent |
| Setting all breakpoints with a `max_width` value (no null) | Last breakpoint only covers up to its max_width — viewports above that are uncovered | The last sorted breakpoint always gets `min-width` only, regardless of its `max_width` value |
| Generating CSS before sorting breakpoints | Media queries appear in wrong order, ranges overlap incorrectly | Always sort (nulls last) before the CSS generation loop |
| Using `echo` without `// phpcs:ignore` on the CSS output | PHPCS flags unescaped output | CSS is built from sanitized values — ignore rule is intentional |

---

## Testing Checklist

When changing anything in this feature, verify:

- [ ] Default state (no option saved) → CSS identical to original plugin (767/1024/1025)
- [ ] Add custom breakpoint "Widescreen 1600px" → 4th toggle appears in Inspector for all blocks
- [ ] Toggle "Hide on Widescreen" on a block → class `rv-hidden--widescreen` added to block HTML
- [ ] View page source → `<style id="rv-dynamic-breakpoints">` contains correct 4-range CSS
- [ ] Remove Widescreen from settings → 4th toggle disappears from Inspector
- [ ] Old block with `hideOnMobile: true` (no `hiddenBreakpoints`) → still hidden on frontend
- [ ] Old block opened in editor → `useEffect` auto-populates `hiddenBreakpoints: ["mobile"]`
- [ ] Multiple blocks on same page → each has independent toggle state
- [ ] Settings page "Customize breakpoints →" link in Inspector → opens correct admin URL
