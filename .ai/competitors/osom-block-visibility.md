# Competitor Profile — Osom Block Visibility

- **Slug:** `osom-block-visibility` · **Author:** OsomPress (osompress, nahuai, esther_sola)
- **Stable:** 1.0.2 · **Tested:** 7.0 · **Requires:** WP 6.3 / PHP 7.4 · **License:** GPLv2
- **readme:** https://plugins.svn.wordpress.org/osom-block-visibility/trunk/readme.txt
- **Threat to RV:** ⭐⭐ weak/new (v1.0.2, single screenshot). Same device+login niche but
  minimal. Easy to out-feature; watch the performance-angle messaging.

## Positioning
> "Osom Block Visibility lets you easily control block visibility from WordPress Block Editor."

Leads with their differentiator: **performance** — "doesn't use CSS to hide the block, but
**prevents it from rendering**, adding no unnecessary load... hugely beneficial if you are
hiding images or other heavy content."

## Feature set (tiny — 4 toggles)
Hide on desktop · hide on mobile **and** tablet (can't separate them) · hide from logged-in ·
hide from logged-out. That's it. No custom breakpoints, no settings page.

## Hide mechanism (their whole pitch)
**Server-side via `wp_is_mobile()`.** Block not rendered for the matching device → lighter
page. **Limitation they admit:** `wp_is_mobile` can't tell mobile from tablet (so "mobile and
tablet" is one toggle). Also: resizing the browser does nothing (must use a real device), and
device rules + full-page cache = wrong device served from cache (the classic server-render
caching flaw — they don't mention it).

## Editor UX
Single "Visibility Settings" panel in the block sidebar. No admin settings page at all. Tip:
group blocks to hide several together. Multisite OK. Disables native WP block-visibility
support to avoid conflicts (changelog 1.0.2).

## Their tags
`hide block, hide content, visibility, conditional blocks, hide on mobile`
→ `hide on mobile` is a **high-intent search term** RV should grab.

## Strengths
Genuinely lighter payload when hiding heavy media (real perf win for images) · dead-simple ·
reputable author.

## Weaknesses RV can exploit
- **Can't distinguish mobile vs tablet** (`wp_is_mobile`) — RV's CSS breakpoints can, precisely.
- **No custom breakpoints, no settings page** — RV is far more capable.
- Server-render breaks under full-page cache for device rules (RV's CSS approach doesn't).
- Doesn't update live on resize — confusing; their FAQ has to explain it away.
- Brand-new, tiny install base.

## The performance trade-off (know both sides)
Osom is right that server-render avoids loading hidden heavy media — RV's CSS approach DOES
load then hide. **RV's honest counter:** CSS = cache-safe + precise breakpoints + live resize;
for the heavy-image edge case, recommend users not put huge media in device-hidden blocks, or
note it as a future "server-render option". Don't pretend the trade-off doesn't exist.

## What RV takes
The `hide on mobile` tag · their performance claim as a prompt to document RV's cache-safety
counter-argument · simplicity benchmark (RV must stay this easy for the basic case).
