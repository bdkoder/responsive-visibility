# Competitor Profile — Wicked Block Conditions

- **Slug:** `wicked-block-conditions` · **Author:** Wicked Plugins (wickedplugins)
- **Stable:** 1.2.5 (May 2025) · **Tested:** 6.7 · **Requires:** WP 5.0 / PHP 7.4 · **License:** GPLv2
- **readme:** https://plugins.svn.wordpress.org/wicked-block-conditions/trunk/readme.txt
- **Threat to RV:** ⭐⭐ adjacent, not direct. It's a **conditions/logic engine**, no responsive
  or device angle at all. Different buyer. Useful only as a model for "conditions UI".

## Positioning (short description)
> "Show or hide blocks based on conditions."

Minimal. Sells the logic engine, not a use case.

## Feature set
Condition types: user login status · user role · date/time · post categories/tags · post status
· post ID · post slug · query-string value · **result of a custom PHP function**. Combine with
**and/or operators**, **group conditions** for complex rules, unlimited conditions per block,
negate any condition, custom label per condition. Works on any block incl. third-party.

## Hide mechanism
**Server-side** (render-time evaluation). No CSS, no device/screen-size concept whatsoever.

## Editor UX
"Display Conditions" panel in sidebar; "Add Condition" → cog to edit/delete; per-condition
config + negate toggle + custom label. Power-user oriented (boolean logic builder).

## ⚠️ Data-loss gotcha (their own FAQ admits it)
"If you edit a page with conditional blocks **while the plugin is deactivated, any conditions
assigned to blocks will be erased.**" — conditions stored as block attrs, wiped if block
re-saved without the plugin. RV's legacy-attr + slug-lock back-compat design is explicitly
safer; worth contrasting in RV's own "will my settings survive?" FAQ.

## Their tags
`block visibility, conditions, conditional blocks, blocks`

## Strengths
Flexible boolean logic (and/or, groups, negate) · custom-PHP escape hatch for devs · simple,
focused, reliable vendor reputation.

## Weaknesses RV can exploit
- **No responsive/device features at all** — not competing for "hide on mobile" traffic.
- Server-render = cache leak risk for any per-visitor rule.
- Thin readme (~127 lines), no use-cases/personas, no marketing polish.
- Data-loss-on-deactivation is a real trust wart.

## What RV takes
Almost nothing feature-wise (different category). Lesson: their and/or **condition grouping**
is the UX pattern to copy IF RV ever adds multi-rule logic — but that's deliberately out of
RV's "simple responsive" scope. Use their deactivation gotcha to highlight RV's back-compat
strength in copy.
