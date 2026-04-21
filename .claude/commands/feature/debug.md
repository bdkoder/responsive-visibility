# Debug an Issue in Responsive Visibility

Use this when something isn't working. Follow these steps based on the symptom.

## Symptom: Block is NOT hidden on frontend even though toggle is ON

**Check 1 — Is the class being added?**
View page source and search for `mobile-hidden` (or relevant class). If the class is missing:
→ Problem is in PHP `responsive_visibility_render_block()` in `responsive-visibility.php`
→ Check: is `hiddenBreakpoints` being read? Is `rv_class_for_slug()` returning correct class?
→ Also check legacy attrs: `hideOnMobile`, `hideOnTablet`, `hideOnDesktop`

**Check 2 — Is the class there but block is still visible?**
Search page source for `<style id="rv-dynamic-breakpoints">`. If missing:
→ `responsive_visibility_dynamic_css()` is not firing — check `wp_head` hook registration
→ If present, check the media query values match expected breakpoints

**Check 3 — CSS specificity conflict?**
Open DevTools → inspect the block element → check if `display:none` is being overridden by a theme rule
→ Our CSS uses `body .mobile-hidden { display:none !important }` — should win, but check anyway

## Symptom: Block IS hidden in editor but NOT on frontend

PHP render filter is not running OR block has no wrapper tag to attach the class to.
→ Check `$tags->next_tag()` returns `true` in `responsive_visibility_render_block()`
→ Some blocks render only text with no wrapper — check the block's HTML output

## Symptom: Inspector panel shows wrong/missing breakpoints

`window.rvBreakpoints` is not being populated correctly.
→ Check `wp_localize_script()` call in `responsive_visibility_init()` — is it inside `is_admin()` block?
→ Check the option: `get_option('responsive_visibility_breakpoints')` — is it returning data?
→ In browser console: `console.log(window.rvBreakpoints)` to inspect

## Symptom: Old blocks (with hideOnMobile:true) stopped working

**Never happened if rules were followed.** Both PHP render filter paths must exist:
1. `hiddenBreakpoints` loop (new system)
2. Individual `hideOnDesktop/Tablet/Mobile` checks (legacy)

Check `responsive_visibility_render_block()` still has BOTH checks.

## Symptom: Settings page not showing

→ Check `rv_settings_menu()` is hooked to `admin_menu`
→ Check `includes/admin-settings.php` is being `require_once`'d in main plugin file
→ Check user has `manage_options` capability

## After any fix — always run:
```bash
npm run build   # if JS files were changed
php -l responsive-visibility.php
php -l includes/admin-settings.php
```
