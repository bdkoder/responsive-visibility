# Competitor Profile — Block Visibility

- **Slug:** `block-visibility` · **Author:** Nick Diego (ndiego, outermostdesign)
- **Stable:** 3.7.1 · **Tested:** 6.9 · **Requires:** WP 6.5 / PHP 7.4 · **License:** GPL-2.0
- **readme:** https://plugins.svn.wordpress.org/block-visibility/trunk/readme.txt
- **Site/GitHub:** blockvisibilitywp.com · github.com/ndiego/block-visibility
- **Threat to RV:** ⭐⭐⭐⭐⭐ category leader, but a DIFFERENT category (conditional logic empire,
  not responsive-first). Not a head-to-head rival — a ceiling to avoid copying.

## Positioning (their short description)
> "Easily show or hide any WordPress block. Schedule block visibility. Restrict blocks to
> specific screen sizes, user roles, post types, and more."

Keyword-dense, benefit-first, zero fluff. Textbook short description — study it.

## Feature set (free; Pro folded into free as of 3.1.0)
**Visibility controls:** hide entirely · schedule (start/end, day-of-week, time-of-day,
seasonal year-agnostic) · screen size (Desktop/Tablet/Mobile + extra, up to 4 breakpoints) ·
logged-in / logged-out · user role · specific users · URL query string · location (post type,
taxonomy, archive, author, has-excerpt, hierarchy) · referral source · post/user metadata ·
cookies · browser & device.
**Integrations:** WooCommerce (products, cart, purchase history), ACF, Easy Digital Downloads,
WP Fusion (CRM tags).
**Settings/power:** visibility presets (control many blocks at once), globally disable any
control, restrict controls to block types, per-user permissions, configurable breakpoints,
contextual-indicator color, "Full Control Mode" (controls on every block incl. child blocks),
remove-on-uninstall.

## Hide mechanism (IMPORTANT contrast)
**Server-side** — hidden blocks are NOT rendered at all (no markup, no asset load); also
filtered out of REST so hidden content can't leak via `wp-json`. **Exception: Screen Size**
uses CSS (the one control that must be client-side). So for device rules they have the SAME
cache problem RV avoids — but they let you disable the screen-size CSS and roll your own.

## Editor UX
Contextual indicators (colored icons/border) on blocks with conditions; eye-icon to manage
controls, vertical-dots for tools; "Manage Visibility Presets" command-palette entry. Works in
post/page editor, Site Editor (FSE since 1.4.0), block widgets. NOT Elementor/page builders.

## readme craft (what to steal)
- ~900 lines, exhaustive but skimmable. "3 simple steps", "Why do I need this?", "Why choose",
  "Plays nice with others", "Stay connected".
- Unbiased-review name-drop (WP Tavern) = social proof.
- Meticulous changelog grouped Added/Changed/Removed/Fixed per version — reads as actively
  maintained and trustworthy. This is the single biggest gap vs RV's "System improved" log.
- FAQ pre-empts objections: Full Control Mode, theme-agnostic, Site Editor, Elementor (no),
  "is it CSS?" (no, server-render except screen-size + perf rationale).

## Their tags
`visibility, dynamic content, conditional content, conditional blocks, restrict blocks`

## Strengths
Breadth no one matches · server-render = no cache leak (except device) + REST-safe · FSE-ready
· huge install base + reputation · transparent GitHub dev.

## Weaknesses RV can exploit
- **Overwhelming** for someone who just wants "hide on mobile" — RV is the simple choice.
- Device/screen-size is a small corner of their plugin; **fixed breakpoints, max 4**. RV's
  unlimited custom breakpoints beat them on the exact responsive use case.
- Heavy JS/settings footprint for a simple need.

## What RV takes
Short-description formula (A1) · changelog discipline · FAQ objection-handling · "3 steps"
framing. NOT the feature breadth — that's the trap.
