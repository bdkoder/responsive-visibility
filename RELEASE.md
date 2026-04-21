# Release Instructions

This plugin releases from GitHub to WordPress.org SVN automatically when a Git tag is pushed.

On a successful deploy, the WordPress.org SVN repository receives the same production files in:

- `trunk/`
- `tags/{version}/`

Example: pushing Git tag `1.0.7` deploys production files to SVN `trunk/` and `tags/1.0.7/`.

## Safety Rule

WordPress.org deploy is the final workflow step. If version validation, dependency install, build, PHP syntax checks, or zip generation fails, the workflow stops and nothing is released to WordPress.org.

The deploy workflow uses `10up/action-wordpress-plugin-deploy`, which commits the tag contents to both SVN `trunk` and the matching SVN version tag.

## Required Secrets

Add these in GitHub repository settings under Actions secrets:

- `SVN_USERNAME`
- `SVN_PASSWORD`

Use a WordPress.org SVN password, not your normal account password.

## Release Checklist

1. Update the version in `responsive-visibility.php`.
2. Update `Stable tag` and changelog in `readme.txt`.
3. Update `version` in `package.json`.
4. Run checks locally:

```bash
npm run release:check
```

5. Commit the release changes:

```bash
git add .
git commit -m "Release 1.0.7"
```

6. Create and push the tag:

```bash
git tag 1.0.7
git push origin main
git push origin 1.0.7
```

The pushed tag triggers the WordPress.org deployment.

When deployment succeeds, WordPress.org SVN will contain the release in both:

```text
trunk/
tags/1.0.7/
```

Both locations are populated from the same production build after `.distignore` exclusions.

## GitHub Release From CLI

You do not need to open GitHub in the browser. Use GitHub CLI:

```bash
gh release create 1.0.7 responsive-visibility-v.1.0.7.zip \
  --title "Responsive Visibility 1.0.7" \
  --notes "Release notes here."
```

For longer notes, create a local notes file and pass it:

```bash
gh release create 1.0.7 responsive-visibility-v.1.0.7.zip \
  --title "Responsive Visibility 1.0.7" \
  --notes-file release-notes.md
```

GitHub release title and notes are separate from WordPress.org. WordPress.org uses `readme.txt` changelog and `Stable tag`.

## Version Guard

The workflow requires the Git tag to match both:

- `Version:` in `responsive-visibility.php`
- `Stable tag:` in `readme.txt`

Example: tag `1.0.7` only deploys when both files also say `1.0.7`.
