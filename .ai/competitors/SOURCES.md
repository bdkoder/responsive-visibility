# Competitor Sources — refresh registry

> Single source of truth for **where each competitor readme lives** and **how to refresh it**.
> To update: ask "update competitor txt" — I re-fetch every URL below into the matching
> `*.txt` file, then re-diff the profiles. `.ai/` is excluded from build/dist zips.

## Tracked competitors

| # | Plugin | Slug | Local file | Profile | readme URL (SVN trunk) |
|---|--------|------|-----------|---------|------------------------|
| 1 | Block Visibility | `block-visibility` | `block-visibility.txt` | `block-visibility.md` | https://plugins.svn.wordpress.org/block-visibility/trunk/readme.txt |
| 2 | Visibility Controls for Editor Blocks | `visibility-controls-for-editor-blocks` | `visibility-controls-for-editor-blocks.txt` | `visibility-controls-for-editor-blocks.md` | https://plugins.svn.wordpress.org/visibility-controls-for-editor-blocks/trunk/readme.txt |
| 3 | Wicked Block Conditions | `wicked-block-conditions` | `wicked-block-conditions.txt` | `wicked-block-conditions.md` | https://plugins.svn.wordpress.org/wicked-block-conditions/trunk/readme.txt |
| 4 | Osom Block Visibility | `osom-block-visibility` | `osom-block-visibility.txt` | `osom-block-visibility.md` | https://plugins.svn.wordpress.org/osom-block-visibility/trunk/readme.txt |

## Refresh command (all four)

```bash
cd .ai/competitors
curl -s -o block-visibility.txt                        "https://plugins.svn.wordpress.org/block-visibility/trunk/readme.txt"
curl -s -o visibility-controls-for-editor-blocks.txt   "https://plugins.svn.wordpress.org/visibility-controls-for-editor-blocks/trunk/readme.txt"
curl -s -o wicked-block-conditions.txt                 "https://plugins.svn.wordpress.org/wicked-block-conditions/trunk/readme.txt"
curl -s -o osom-block-visibility.txt                   "https://plugins.svn.wordpress.org/osom-block-visibility/trunk/readme.txt"
```

Pattern for ANY wp.org plugin: `https://plugins.svn.wordpress.org/<slug>/trunk/readme.txt`
(always-current trunk). Tagged release instead: `.../tags/<version>/readme.txt`.

## Versions captured (last fetch)

| Plugin | Stable tag | Tested up to | Requires WP | Requires PHP |
|--------|-----------|--------------|-------------|--------------|
| Block Visibility | 3.7.1 | 6.9 | 6.5 | 7.4 |
| Visibility Controls for Editor Blocks | 1.2.5 | 7.0 | 5.0 | 7.4 |
| Wicked Block Conditions | 1.2.5 | 6.7 | 5.0 | 7.4 |
| Osom Block Visibility | 1.0.2 | 7.0 | 6.3 | 7.4 |
| **Responsive Visibility (us)** | 1.1.0 | 7.0 | 6.2 | 7.2 |

> Update this table whenever the `.txt` files are refreshed — quickest staleness check.

## Candidate competitors to add later (not yet tracked)

Ask me to add any of these and I'll append a row + fetch:
- `editorskit` (Block Options — has visibility), `coblocks` (visibility controls),
  `conditional-blocks`, `wp-show-hide`, page-builder visibility (Greenshift, Kadence).
  Only worth tracking ones with a **device/responsive** angle — that's RV's lane.
