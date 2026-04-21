# /release — Prepare a plugin release

Use this command when the user wants to release a new version of the plugin.

## Steps

1. **Determine target version** — ask the user if not specified (e.g. "What version? Patch=1.1.1, Minor=1.2.0, Major=2.0.0").

2. **Update version strings in ALL four locations** (must be identical):
   - `package.json` → `"version": "X.Y.Z"`
   - `responsive-visibility.php` → `* Version: X.Y.Z`
   - `readme.txt` → `Version: X.Y.Z`
   - `readme.txt` → `Stable tag: X.Y.Z`

3. **Add changelog entry** to `readme.txt` under `== Changelog ==`:
   ```
   = X.Y.Z [Day Month Year] =
   * [list changes]
   ```

4. **Verify consistency checks** (read files to confirm all match):
   - `Requires PHP` matches in plugin header AND readme.txt
   - `Requires at least` (WP version) matches in plugin header AND readme.txt
   - `WP_HTML_Tag_Processor` usage → Requires at least 6.2

5. **Run the release script**:
   ```bash
   npm run release
   ```
   This script does: version check → PHP lint → npm build → rsync with .buildignore → zip → zip contents verification

6. **Report the output zip filename** and the next steps:
   - `git add -A && git commit -m "vX.Y.Z"`
   - `git tag vX.Y.Z`
   - `git push && git push --tags`
   - Upload zip to WordPress.org SVN

## Version bump rules
- **Patch** (1.0.x): Bug fixes only
- **Minor** (1.x.0): New features, backward compatible
- **Major** (x.0.0): Breaking changes

## Files that must be in the zip
- `responsive-visibility.php`
- `readme.txt`
- `includes/class-*.php`
- `build/` (compiled JS/CSS)
- `assets/` (if exists)
- `dci/` (SDK)

## Files that must NOT be in the zip (excluded by .buildignore)
- `src/`, `node_modules/`, `scripts/`
- `.claude/`, `.ai/`, `CLAUDE.md`
- `package.json`, `package-lock.json`
- `.github/`, `.gitignore`, `.buildignore`
- `phpcs.xml`, `.editorconfig`
