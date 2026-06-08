# Competitor Profile — Visibility Controls for Editor Blocks

- **Slug:** `visibility-controls-for-editor-blocks` · **Author:** Denis Doroshchuk (denisdoroshchuk)
- **Stable:** 1.2.5 · **Tested:** 7.0 · **Requires:** WP 5.0 / PHP 7.4 · **License:** GPLv3
- **readme:** https://plugins.svn.wordpress.org/visibility-controls-for-editor-blocks/trunk/readme.txt
- **Threat to RV:** ⭐⭐⭐⭐⭐ **THE direct rival.** Same niche (device + breakpoints, CSS-based),
  same simple positioning, freemium upsell. Beat THIS one on readme + features.

## Positioning (their short description)
> "Hide Gutenberg blocks by device, custom breakpoints, and user login status. Pro adds role,
> schedule, and URL rules."

Names the exact features in one line. Free vs Pro split stated immediately.

## Feature set
**Free:** device visibility (mobile/tablet/desktop) · **custom breakpoints** (define your own
mobile/tablet widths) · login-status visibility (logged-in / logged-out) · sidebar controls ·
dynamic/server-rendered block support · block-theme/Site-Editor support · "disable CSS on
non-Gutenberg pages" option.
**Pro:** user-role visibility · date/time scheduling · URL/query-parameter rules ·
**cache-friendly frontend mode** (evaluates schedule/URL rules in the browser so full-page
cache works) · choose which roles appear in controls.

## Hide mechanism
**CSS classes** (same model as RV) — "only adds small visibility classes and the CSS needed to
hide matching blocks... avoids changing your block content structure." Pro's cache-friendly
mode does client-side eval for schedule/URL so cached pages still work.

## Editor UX
Controls in the block sidebar (Visibility Settings panel); visual strikethrough/overlay
indicators in editor; settings page at **Settings → Gutenberg Blocks Visibility**. Has a
**YouTube video playlist** linked in readme (RV has one video too).

## readme craft (what to steal)
- ~213 lines, tight. Sections: Key Features / Pro Features / **Ideal For** (personas:
  content creators, site owners, designers, agencies) / video tips.
- "Ideal For" persona list is a cheap, effective conversion device RV lacks.
- FAQ covers the cache question explicitly (Pro) and Elementor (no).
- Clean changelog with real entries (contrast RV's "System improved").

## Their tags
`block visibility, responsive blocks, gutenberg, conditional blocks, responsive design`
→ note `responsive blocks` + `responsive design`: they're targeting RV's exact keywords.

## Critical differences vs RV
| | VCEB | RV |
|---|------|-----|
| Custom breakpoints | Mobile + tablet widths only (2 knobs) | **Unlimited, named, any number** ← RV wins |
| Login status | ✅ free | ❌ (RV's biggest gap) |
| Role/schedule/URL | Pro (paid) | — |
| Settings UI | Standard PHP page | React app |
| Cache-safe device | ✅ (CSS) | ✅ (CSS) — tie |

## Strengths
Exact-match positioning · login status free · proven freemium path · good SEO tags · video
content · broad WP 5.0+ support.

## Weaknesses RV can exploit
- **Breakpoints limited to mobile/tablet width fields** — not arbitrary/unlimited/named. RV's
  Elementor-style dynamic breakpoints are strictly more powerful. Hammer this in copy.
- Login/role/etc. gated behind Pro — RV can offer login-status FREE and undercut.

## What RV takes
"Ideal For" persona block (A3) · the explicit "free vs Pro in the short description" clarity
(if RV ever splits) · login-status feature (B1) to erase the gap · their tag choices
(`responsive blocks`, `responsive design`).
