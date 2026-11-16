#!/usr/bin/env bash
# Final step after "pnpm release": commits, tags, and pushes.
# Workflow: pnpm release → inspect dist/responsive-visibility/ folder → pnpm ship
#
# Detects the release zip for the current version. If found, skips all checks
# and goes straight to git. If not, stops and tells you to run release first.

set -e

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PLUGIN_DIR"

# ── Helpers ───────────────────────────────────────────────────────────────────
red()    { echo -e "\033[0;31m$*\033[0m"; }
green()  { echo -e "\033[0;32m$*\033[0m"; }
yellow() { echo -e "\033[0;33m$*\033[0m"; }
blue()   { echo -e "\033[0;34m$*\033[0m"; }
bold()   { echo -e "\033[1m$*\033[0m"; }
die()    { red "ERROR: $*"; exit 1; }

confirm() {
  echo ""
  read -r -p "$(echo -e "\033[1m$1 [y/N]: \033[0m")" answer
  [[ "$answer" =~ ^[Yy]$ ]] || { yellow "Aborted."; exit 0; }
}

# ── Read version ──────────────────────────────────────────────────────────────
VERSION=$(node -p "require('./package.json').version" 2>/dev/null) \
  || die "Cannot read version from package.json"

ZIP_FILE="dist/responsive-visibility-${VERSION}.zip"
BUILD_FOLDER="dist/responsive-visibility"

echo ""
blue "================================================================"
blue "  Shipping Responsive Visibility v$VERSION"
blue "================================================================"

# ── Check release was already run ─────────────────────────────────────────────
if [[ -f "$ZIP_FILE" && -d "$BUILD_FOLDER" ]]; then
  green "✓ Release zip found: $ZIP_FILE"
  green "✓ Build folder ready: $BUILD_FOLDER/"
  yellow "  Skipping checks — already passed in 'pnpm release'"
else
  echo ""
  red "✗ Release zip not found for v$VERSION"
  echo ""
  yellow "Run this first:"
  echo ""
  echo "  pnpm release"
  echo ""
  echo "Then inspect the dist/responsive-visibility/ folder, and re-run:"
  echo ""
  echo "  pnpm ship"
  echo ""
  exit 1
fi

# ── Show what will be committed ───────────────────────────────────────────────
echo ""
yellow "--> Uncommitted changes:"
git diff --stat HEAD
echo ""
git status --short

# ── Confirm ───────────────────────────────────────────────────────────────────
echo ""
bold "About to:"
echo "  git add -A"
echo "  git commit -m \"v$VERSION\""
echo "  git tag v$VERSION"
echo "  git push origin main"
echo "  git push origin v$VERSION"
echo ""
echo "  GitHub Actions will then:"
echo "  → Deploy to WordPress.org SVN"
echo "  → Create GitHub Release"

confirm "Proceed with release v$VERSION?"

# ── Clean up build artifacts before committing ────────────────────────────────
yellow "--> Cleaning up build artifacts..."
rm -rf "$BUILD_FOLDER"
green "  ✓ Removed $BUILD_FOLDER/ (zip kept for local testing)"

# ── Commit, tag, push ─────────────────────────────────────────────────────────
yellow "--> Committing..."
git add -A
git commit -m "v$VERSION"
green "  ✓ Committed"

yellow "--> Tagging v$VERSION..."
git tag "v$VERSION"
green "  ✓ Tagged"

yellow "--> Pushing to GitHub..."
git push origin main
git push origin "v$VERSION"
green "  ✓ Pushed"

# ── Done ──────────────────────────────────────────────────────────────────────
echo ""
green "================================================================"
green "  v$VERSION is live! GitHub Actions is now:"
green "  1. Running CI checks"
green "  2. Deploying to WordPress.org"
green "  3. Creating GitHub Release"
green "================================================================"
echo ""
yellow "Watch: https://github.com/bdkoder/responsive-visibility/actions"
