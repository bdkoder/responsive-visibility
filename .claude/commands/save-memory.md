# Save Memory

Check `/tmp/rv-changed-files.txt` for files modified this session (if it exists). Then review what changed and update the relevant memory files in the project memory directory.

Steps:
1. Read `/tmp/rv-changed-files.txt` (if it exists) to see what files were changed
2. For each significant change (new feature, architecture decision, convention established, bug fixed), determine which memory file it belongs to:
   - Feature/architecture → `project_feature_*.md` or `project_plugin_overview.md`
   - Preference/feedback → `feedback_architecture.md`
   - User info → `user_profile.md`
3. Update or create the relevant memory file(s)
4. Update `MEMORY.md` index if new files were added
5. Clear `/tmp/rv-changed-files.txt` after saving

Only save things that are non-obvious, decision-based, or would be useful to a future AI session starting cold on this project.
