# Skill: Block Editor Integration

> Everything about how this plugin integrates with the WordPress Gutenberg block editor. Read this before touching any JavaScript file in `src/extentions/responsive-visibility/`.

> **Two editor extensions now (v1.2.0).** This file covers the **device** extension
> (`src/extentions/responsive-visibility/` — breakpoint toggles). The **conditions** extension
> (`src/extentions/visibility-conditions/` — login/role/post-type… via a schema-driven repeater)
> is a separate, editor-only extension; see `.ai/conditions-engine/SKILL.md`. The
> block-extension pattern below (addFilter on attributes + BlockEdit) is identical for both;
> each feature lives in its own folder so they never entangle.

---

## Core Concept — This Is a Block Extension, Not a Block

This plugin does NOT register a new block type. It **extends every existing block** using WordPress filter hooks. This means:
- No `block.json` registration
- No `registerBlockType()`
- Works automatically on ALL blocks (paragraphs, images, headings, groups, third-party blocks — everything)
- Uses `addFilter()` three times to inject into the block lifecycle

---

## The Three Filter Hooks — Entry Points

All three are registered in `src/extentions/responsive-visibility/index.js`:

```js
// 1. Adds custom attributes to every block type
addFilter(
    "blocks.registerBlockType",
    "responsive-visibility/with-block-attributes",
    resposiveVisibilityBlockAttributes  // from attributes.js
);

// 2. Adds Inspector Controls panel to every block's sidebar
addFilter(
    "editor.BlockEdit",
    "responsive-visibility/with-inspector-controls",
    withResponsiveVisibilityControls    // from settings.js
);

// 3. Adds CSS classes to block wrappers in the editor canvas
addFilter(
    "editor.BlockListBlock",
    "responsive-visibility/with-block-wrapper-class",
    withBlockWrapperClass               // from block-wrapper.js
);
```

These three hooks cover the full editor lifecycle:
- `blocks.registerBlockType` → runs when blocks are registered (sets up data)
- `editor.BlockEdit` → runs when the block edit UI renders (adds sidebar controls)
- `editor.BlockListBlock` → runs when a block renders in the canvas (adds visual feedback)

---

## File 1: `attributes.js` — The Data Layer

**Purpose:** Defines which attributes get added to every block.

```js
const resposiveVisibilityBlockAttributes = (settings, name) => {
    return {
        ...settings,
        attributes: {
            ...settings.attributes,   // preserve ALL existing block attributes
            // Legacy system (never remove):
            hideOnDesktop: { type: "boolean", default: false },
            hideOnTablet:  { type: "boolean", default: false },
            hideOnMobile:  { type: "boolean", default: false },
            // New system:
            hiddenBreakpoints: { type: "array", default: [], items: { type: "string" } },
        },
    };
};
```

**Critical rules:**
- Always spread `...settings.attributes` first — never wipe a block's own attributes
- Never remove the legacy boolean attributes — old blocks in the database depend on them
- `hiddenBreakpoints` stores slugs like `["mobile", "widescreen"]` — not class names, not pixel values

**Where attributes are stored:**
WordPress serializes block attributes into the post content HTML comment:
```html
<!-- wp:paragraph {"hiddenBreakpoints":["mobile"],"hideOnMobile":false} -->
<p>Your content</p>
<!-- /wp:paragraph -->
```

---

## File 2: `settings.js` — The Inspector Panel UI

**Purpose:** Renders the "Responsive Visibility" panel in the right sidebar of the block editor.

### How it works:

```jsx
const withResponsiveVisibilityControls = createHigherOrderComponent(
    (BlockEdit) => {
        return (props) => {
            const { attributes, setAttributes } = props;
            // ...
            return (
                <>
                    <BlockEdit {...props} />        {/* render the original block edit UI */}
                    <InspectorControls>             {/* inject into the sidebar */}
                        <PanelBody title="Responsive Visibility">
                            {/* dynamic toggles + settings link */}
                        </PanelBody>
                    </InspectorControls>
                </>
            );
        };
    },
    "withResponsiveVisibilityControls"
);
```

**Key pattern:** The HOC (Higher Order Component) wraps the original `BlockEdit`. It renders `<BlockEdit {...props} />` first (the original block), then appends `<InspectorControls>` to inject into the sidebar. This pattern is how ALL WordPress block editor extensions work.

### The toggle logic:

```js
const hiddenBreakpoints = attributes?.hiddenBreakpoints || [];

const toggleBreakpoint = (slug, isHidden) => {
    if (isHidden) {
        setAttributes({ hiddenBreakpoints: [...hiddenBreakpoints, slug] });
    } else {
        setAttributes({ hiddenBreakpoints: hiddenBreakpoints.filter(s => s !== slug) });
    }
};
```

Toggle ON → spread existing array + add new slug.
Toggle OFF → filter out the slug.
`setAttributes()` is WordPress's method to update block data — always use this, never mutate directly.

### The auto-migration useEffect:

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
}, []);  // empty deps = runs once on mount only
```

Runs silently when any old block is opened. The user never sees this happening.
The empty dependency array `[]` ensures it runs once — not on every re-render.

### The settings link at the bottom:

```jsx
<ExternalLink href={settingsUrl}>
    ⚙ Customize breakpoints
</ExternalLink>
```

`settingsUrl` comes from `window.rvBreakpoints.settingsUrl` (injected by PHP via `wp_localize_script`).
`ExternalLink` is a WordPress component — it opens in a new tab automatically.

### Fallback when `window.rvBreakpoints` is undefined:

```js
const breakpoints = window?.rvBreakpoints?.breakpoints || DEFAULT_BREAKPOINTS;
```

`DEFAULT_BREAKPOINTS` is hardcoded in `settings.js` as the standard 3 (mobile/tablet/desktop). This is the safety net — if `wp_localize_script` fails for any reason, the UI still works with default values.

---

## File 3: `block-wrapper.js` — The Editor Canvas Preview

**Purpose:** Makes hidden blocks show a visual stripe pattern in the editor canvas so editors can see what will be hidden — without actually hiding it (you still need to select and edit it).

### How it works:

```js
const withBlockWrapperClass = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        const { attributes } = props;

        // Get current device preview type from WordPress editor store
        const { deviceType } = useSelect((select) => ({
            deviceType: select("core/editor").getDeviceType(),
        }), []);

        // Build class object
        const classObj = {};

        // New system
        (attributes?.hiddenBreakpoints || []).forEach((slug) => {
            const cssClass = rvClassForSlug(slug);
            const expectedDevice = DEVICE_TYPE_MAP[slug]; // "Desktop", "Tablet", or "Mobile"
            if (expectedDevice) {
                classObj[cssClass] = deviceType === expectedDevice; // only show stripe in matching preview
            } else {
                classObj[cssClass] = true; // custom slug = always show stripe
            }
        });

        // Legacy fallback
        if (attributes?.hideOnDesktop) classObj["desktop-hidden"] = classObj["desktop-hidden"] || deviceType === "Desktop";
        if (attributes?.hideOnTablet")  classObj["tablet-hidden"]  = classObj["tablet-hidden"]  || deviceType === "Tablet";
        if (attributes?.hideOnMobile")  classObj["mobile-hidden"]  = classObj["mobile-hidden"]  || deviceType === "Mobile";

        const wrapperProps = {
            ...props.wrapperProps,
            className: classNames(props?.wrapperProps?.className, classObj),
        };

        return <BlockListBlock {...props} wrapperProps={wrapperProps} />;
    };
}, "withBlockWrapperClass");
```

### Device type matching:

| WordPress device type | Slug | Shows stripe when |
|----------------------|------|------------------|
| `"Desktop"` | `desktop` | Previewing in Desktop mode |
| `"Tablet"` | `tablet` | Previewing in Tablet mode |
| `"Mobile"` | `mobile` | Previewing in Mobile mode |
| (no match) | `widescreen` etc. | Always (regardless of preview mode) |

The WordPress editor has three preview modes (the device icons in the top toolbar). `select("core/editor").getDeviceType()` returns the current one. When you switch preview modes, `useSelect` re-runs and the stripe updates reactively.

### Why not actually hide blocks in the editor?

If we used `display:none`, editors couldn't click, select, or edit hidden blocks. The stripe pattern from `editor.scss` makes the block visually "faded" but still fully interactive.

---

## File 4: `editor.scss` — The Visual Feedback Styles

The diagonal stripe on hidden blocks:

```scss
.editor-styles-wrapper {
    .wp-block {
        &.desktop-hidden,
        &.tablet-hidden,
        &.mobile-hidden {
            display: inherit;  // never actually hide in editor
            background: repeating-linear-gradient(
                125deg,
                rgba(0,0,0,0.05),
                rgba(0,0,0,0.05) 1px,
                transparent 2px,
                transparent 9px
            );
            border: 1px solid rgba(0,0,0,0.02);
        }
    }
}
```

If you add a new custom breakpoint class pattern (e.g. `rv-hidden--*`), you need to add it here too so custom breakpoints also get the stripe treatment. Currently this needs updating — it only covers the 3 legacy classes.

**TODO:** Update `editor.scss` to also apply stripe to `[class*="rv-hidden--"]` selector.

---

## How to Add a New Inspector Control

If you need to add a new type of control to the Inspector panel (e.g. a date picker, a role selector):

1. **Add the attribute** in `attributes.js`:
```js
hideAfterDate: { type: "string", default: "" },
```

2. **Add the UI** in `settings.js` inside the existing `<PanelBody>` or a new one:
```jsx
import { DatePicker } from "@wordpress/components";

<PanelBody title={__("Schedule Visibility", "responsive-visibility")}>
    <DatePicker
        currentDate={attributes.hideAfterDate}
        onChange={(date) => setAttributes({ hideAfterDate: date })}
    />
</PanelBody>
```

3. **Handle in PHP** in `responsive_visibility_render_block()`:
```php
if (!empty($block['attrs']['hideAfterDate'])) {
    if (current date > hideAfterDate) {
        // return empty string to hide block completely
        return '';
    }
}
```

4. Run `npm run build`.

---

## Key WordPress APIs Used

| API | Where used | Purpose |
|-----|-----------|---------|
| `addFilter()` | `index.js` | Hook into block registration, edit, and list |
| `createHigherOrderComponent()` | `settings.js`, `block-wrapper.js` | Wrap existing components |
| `InspectorControls` | `settings.js` | Inject into block sidebar panel |
| `PanelBody` | `settings.js` | Collapsible section in sidebar |
| `ToggleControl` | `settings.js` | On/off toggle input |
| `ExternalLink` | `settings.js` | Link that opens in new tab |
| `useSelect()` | `block-wrapper.js` | Read from WordPress data store (device type) |
| `useEffect()` | `settings.js` | Run migration once on block mount |
| `setAttributes()` | `settings.js` | Save block attribute changes |
| `classNames()` | `block-wrapper.js` | Conditionally build className strings |
| `wp_localize_script()` | PHP | Pass PHP data to JavaScript as `window.rvBreakpoints` |

---

## Build Process

After editing ANY file in `src/`:

```bash
npm run build
```

This compiles:
- `src/.../index.js` + all components → `build/.../index.js`
- `src/.../editor.scss` → `build/.../index.css`
- `src/.../style.scss` → `build/.../style-index.css`

**Never edit `build/` files directly** — they are overwritten on every build.

The compiled `index.asset.php` auto-generates dependency versions used by `wp_register_script()`.

---

## Common Mistakes

| Mistake | Consequence | Fix |
|---------|-------------|-----|
| Forgetting `npm run build` after JS changes | Old compiled code runs, changes have no effect | Always build after src changes |
| Mutating `attributes` directly instead of `setAttributes()` | Changes don't persist, React state desync | Always use `setAttributes()` |
| Not spreading `...settings.attributes` in `attributes.js` | Every block loses its own attributes | Always spread existing attrs first |
| Removing old boolean attr checks from `block-wrapper.js` | Old blocks stop showing stripe in editor | Keep both new + legacy paths |
| Using `display:none` in `editor.scss` | Editors can't select/edit hidden blocks | Use `display:inherit` + stripe background |
| Forgetting `// eslint-disable-line react-hooks/exhaustive-deps` on migration `useEffect` | Linter warning, or worse — migration runs on every render | Keep empty `[]` deps with the comment |
