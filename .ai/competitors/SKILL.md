# Competitor Intelligence — Index

> Hub for RV's competitor research. Ask me anything against these and I answer from here.
> Raw readmes (`*.txt`) + per-competitor deep profiles (`*.md`) live in this folder.
> `.ai/` is excluded from build/dist zips — none of this ships.

## Files
- `SOURCES.md` — every competitor's readme URL + refresh commands. Say **"update competitor txt"** → I re-fetch all `*.txt` and re-diff.
- `block-visibility.md` — Block Visibility (ndiego) deep profile.
- `visibility-controls-for-editor-blocks.md` — the direct rival, deep profile.
- `wicked-block-conditions.md` — conditions engine, deep profile.
- `osom-block-visibility.md` — minimal device+login, deep profile.
- This file — matrix, positioning, tags strategy, readme fixes, feature roadmap.

## Matrix

| Plugin | Ver | Model | Hide mechanism | Device/breakpoints | Login | Roles/Schedule/URL | Threat |
|--------|-----|-------|----------------|--------------------|-------|--------------------|--------|
| Block Visibility | 3.7.1 | Free (huge) | Server-render (CSS for screen-size) | Fixed, max 4 | ✅ | ✅ + Woo/ACF/EDD | ⭐⭐⭐⭐⭐ different category |
| **Visibility Controls for Editor Blocks** | 1.2.5 | Freemium | **CSS classes** | Mobile+tablet widths | ✅ free | Pro | ⭐⭐⭐⭐⭐ **direct rival** |
| Wicked Block Conditions | 1.2.5 | Free | Server-render | ❌ none | ✅ | ✅ + custom PHP | ⭐⭐ adjacent (logic engine) |
| Osom Block Visibility | 1.0.2 | Free | Server-render (`wp_is_mobile`) | Can't split mobile/tablet | ✅ | ❌ | ⭐⭐ weak/new |
| **Responsive Visibility (us)** | 1.1.0 | Free | **CSS classes** | **Unlimited custom, named** | ❌ gap | ❌ | — |

## RV's moat (repeat everywhere)
1. **Unlimited, named, custom breakpoints** (Elementor-style). Every rival hardcodes
   mobile/tablet/desktop or offers ≤2 width fields. Nobody matches this.
2. **CSS-media-query hiding = 100% page-cache safe + live on resize.** The server-render
   crowd (BV device, Osom, Wicked) serves the wrong device from full-page cache.
3. **Obsessive backward compatibility** (legacy attrs + slug-lock). Wicked literally erases
   conditions if edited while deactivated — RV never loses settings.

Tagline: *"The only block visibility plugin with unlimited custom breakpoints — and 100%
cache-safe, because it hides with CSS, never by re-rendering."*

## Tag / keyword strategy (wp.org)
**Ranking weight:** plugin **title** ≫ **short description** > headings > body density > tags.
Tags are a minor signal; wp.org honors ~first 5. Avoid keyword stuffing (rejection risk).

**Tags each rival uses:**
- BV: visibility, dynamic content, conditional content, conditional blocks, restrict blocks
- VCEB: block visibility, responsive blocks, gutenberg, conditional blocks, responsive design
- Wicked: block visibility, conditions, conditional blocks, blocks
- Osom: hide block, hide content, visibility, conditional blocks, **hide on mobile**

**Old RV tags:** responsive visibility, block visibility, conditional blocks, hide/show blocks, gutenberg

**✅ LOCKED (live in readme.txt 1.1.0):** `block visibility` · `conditional blocks` · `gutenberg` · `visibility` · `responsive`
- **wp.org rule (readme-validator):** a tag only works if it's *widely used* across the directory; below that threshold it's IGNORED — no tag page, no search weight — yet still eats one of the 5 indexed slots. "Dead tag" = wasted slot.
- First draft used `hide on mobile` + `responsive blocks`; **validator flagged both as not widely used** → replaced with `visibility` + `responsive` (both proven, used by multiple rivals / directory-wide). All 5 now working.
- Frequency across the 4 rivals = the widely-used signal: `conditional blocks` 4/4 · `block visibility` 2/4 · `visibility` 2/4 · `gutenberg` huge directory-wide · `responsive` huge directory-wide.
- **The lost phrases live in the CONTENT instead:** `hide blocks on mobile` + `responsive blocks` woven into the Description body — stronger SEO weight than tags anyway. Nothing lost.
- Dropped `responsive visibility` (brand — already saturates title+body) and `hide/show blocks` (slash, not a real query).
- Note: wp.org honors ~first 5 tags; tags are a MINOR signal (title + short desc + body do ~80%).

## readme.txt fixes — ✅ APPLIED in 1.1.0
- A1 short description — keyword-dense, emoji removed (138 chars). ✅
- A2 tags — locked version above. ✅
- A3 "Why" + "Ideal For" personas + use-cases. ✅
- A4 FAQ expanded 3→9 (breakpoint how-to, CSS-vs-render, cache-safe, perf, FSE, back-compat, Elementor, multisite). ✅
- A6 `== Upgrade Notice ==` added. ✅
- Title → `Responsive Visibility — Show or Hide Blocks by Device, Custom Breakpoints & Conditions` (was "...for Blocks Editor"). Future-proof framing: dropped fading "Gutenberg" brand; carries all 3 keyword pillars — Device (volume) + Custom Breakpoints (moat, exact phrase) + Conditions (future login/role/schedule). "Show or Hide" kept (user: important for clarity/CTR); ~86 chars, within wp.org norms. Slug permanent — retune freely later. ✅
- **Still TODO:** A5 changelog — pre-1.1.0 entries remain "System improved" (truthful, left as-is; write real entries going forward). A7 add a screenshot of the React breakpoints settings page.
- Plugin-file header `Plugin Name:` synced to match the readme title. ✅
- **Not claimed:** login-status (not built — see roadmap B1).

## Feature roadmap (on-brand, ranked)
1. **Login-status visibility** (B1) — only table-stakes gap; ALL 4 rivals have it. Low effort,
   server-side in `Render`. Document cache caveat. RV can offer it FREE (VCEB charges via Pro).
2. **In-editor visibility badge** (B2) — cheap polish, demos in screenshots (BV's beloved feature).
3. **Show-only-on** inverse toggle (B3) · **export/import breakpoints** (B5).
- **DEFER** (→ becomes Block Visibility): scheduling, roles, query/URL, Woo/ACF, cookies, geo.
  Only revisit if a Pro tier is planned — copy VCEB's split (device+login+breakpoints free).
