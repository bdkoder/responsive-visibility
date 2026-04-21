# Save Session Memory

Run this at the end of any work session to keep the project brain up to date.

## Steps

1. Read `/tmp/rv-changed-files.txt` if it exists — this lists files changed this session
2. Review what was built or changed
3. Update the relevant memory files at:
   `~/.claude/projects/-Users-shahidul-Documents-local-wowdevs-app-public-wp-content-plugins-responsive-visibility/memory/`

   - New feature built → update or create `project_feature_*.md`
   - Architecture changed → update `project_plugin_overview.md`
   - New client preference learned → update `feedback_architecture.md`
   - New user info → update `user_profile.md`

4. Update `MEMORY.md` index if new files were added
5. Update `CLAUDE.md` in the plugin root if:
   - A new feature is now complete (move from "Planned" to "Current Feature Set")
   - A new rule or convention was established
   - A new client preference was revealed
   - A key decision was made that future AIs need to know

6. Delete `/tmp/rv-changed-files.txt` after saving

## What is worth saving?
- Non-obvious decisions and WHY they were made
- Client preferences and feedback that shapes future work
- Architectural patterns established in this session
- What NOT to do (and why)

## What is NOT worth saving?
- Bug fixes that are obvious from reading the code
- Routine refactors
- Anything already documented in `CLAUDE.md`
