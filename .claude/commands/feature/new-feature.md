# Add a New Feature to Responsive Visibility

Use this skill when adding any new feature to this plugin. It ensures you follow the established patterns and don't break existing functionality.

## Step 1 — Read the brain first
Read `CLAUDE.md` completely. Pay attention to the "Rules" and "Key Decisions" sections before writing a single line of code.

## Step 2 — Understand the request: $ARGUMENTS

## Step 3 — Classify the feature type

**Type A: New visibility condition** (e.g. hide by user role, hide by date)
- Needs new block attributes in `src/.../components/attributes.js`
- Needs new UI in `src/.../components/settings.js` (new section in PanelBody)
- Needs new PHP logic in `responsive_visibility_render_block()` in `responsive-visibility.php`
- May need server-side rendering instead of pure CSS (e.g. user role can't be CSS-only)

**Type B: New breakpoint-related feature** (e.g. reset button, validation, theme.json integration)
- Works within the existing breakpoints system in `includes/admin-settings.php` and `responsive-visibility.php`
- Follows the same `responsive_visibility_breakpoints` option schema
- Must not change CSS class naming pattern

**Type C: Editor UX improvement** (e.g. block badge, better preview)
- Changes in `src/.../components/` JS files
- Requires `npm run build` after changes

## Step 4 — Backward compatibility check
Before implementing, answer:
- Does this add new block attributes? → Keep all OLD attributes, never remove
- Does this change CSS class names? → Never change legacy names (`mobile-hidden`, `tablet-hidden`, `desktop-hidden`)
- Does this change the breakpoints option schema? → Must be backward compatible with existing saved data

## Step 5 — Implement, then build
After code changes to any `src/` file, always run:
```bash
npm run build
```
Then verify:
```bash
php -l responsive-visibility.php
php -l includes/admin-settings.php
```

## Step 6 — Save memory
Run `/save-memory` to record the new feature's architecture in project memory.
