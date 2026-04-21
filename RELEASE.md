# Release Instructions

This plugin releases from GitHub to WordPress.org SVN automatically when a Git tag is pushed.

On a successful deploy, the WordPress.org SVN repository receives the same production files in:

- `trunk/`
- `tags/{version}/`

Example: pushing Git tag `1.1.0` deploys production files to SVN `trunk/` and `tags/1.1.0/`.

## Safety Rule

WordPress.org deploy is the final workflow step. If version validation, dependency install, build, PHP syntax checks, or zip generation fails, the workflow stops and nothing is released to WordPress.org.

The deploy workflow uses `10up/action-wordpress-plugin-deploy`, which commits the tag contents (after `.distignore` exclusions) to both SVN `trunk` and the matching SVN version tag.

## Required Secrets

Add these in GitHub repository settings under Actions secrets:

- `SVN_USERNAME`
- `SVN_PASSWORD`

Use a WordPress.org SVN password (application password), not your normal account password.

## Release Checklist

### Step 1 — Update version strings (all 4 locations must match)

| File | Field |
|------|-------|
| `package.json` | `"version"` |
| `responsive-visibility.php` | `* Version:` |
| `readme.txt` | `Version:` |
| `readme.txt` | `Stable tag:` |

Also update `readme.txt` changelog:
```
= X.Y.Z [Day Month Year] =
* Added: ...
* Fixed: ...
```

### Step 2 — Local pre-flight (run this before any git commands)

```bash
npm run release
```

This script checks all version strings, `Requires PHP`, `Requires at least`, changelog entry, PHP syntax, builds assets, creates zip, and verifies zip contents. It will fail loudly if anything is wrong.

### Step 3 — Commit, tag, push

```bash
git add .
git commit -m "v1.1.0"
git tag v1.1.0
git push origin main
git push origin v1.1.0
```

The pushed tag triggers the WordPress.org deployment via GitHub Actions.

### Step 4 — (Optional) GitHub Release

```bash
gh release create v1.1.0 responsive-visibility-v1.1.0.zip \
  --title "Responsive Visibility 1.1.0" \
  --notes "See readme.txt for full changelog."
```

## Version Guard (in GitHub Actions)

The workflow rejects a deploy unless the Git tag matches both:

- `Version:` in `responsive-visibility.php`
- `Stable tag:` in `readme.txt`

## Version bump rules

| Change type | Example | When to use |
|-------------|---------|-------------|
| Patch | 1.1.0 → 1.1.1 | Bug fixes only |
| Minor | 1.1.0 → 1.2.0 | New features, backward compatible |
| Major | 1.x.x → 2.0.0 | Breaking changes |

## Files shipped to WordPress.org

Controlled by `.distignore`. These files/dirs are **excluded**:
`.git`, `.github`, `.ai`, `.claude`, `.distignore`, `.editorconfig`, `.gitignore`, `.gitattributes`, `.DS_Store`, `node_modules`, `package.json`, `package-lock.json`, `phpcs.xml`, `CLAUDE.md`, `RELEASE.md`, `scripts/`, `src/`, `*.zip`
