---
name: saver
description: "Save the current chat/session as a durable record: write a session log into docs/sessions/, update the session index + PROJECT_HANDOFF.md, and commit everything to git when there are 50 or more changes. Invoke with 'saver', 'save session', or at the end of any substantial work session."
---

# Saver Skill

Persists a work session into the repo so the next agent (or human) can resume
without the chat. Follows this repo's docs conventions (`AGENTS.md`,
`docs/upgrades/UPGRADE_HISTORY.md` style).

## Procedure

### 1. Gather

- `git log --oneline -15` and `git status --short` — what changed this session.
- Scan the conversation for: goals, decisions made (and why), defects found +
  root causes, verification evidence (screenshots, curl codes, logs),
  unfinished items / follow-ups.
- Server/environment facts learned (paths, credentials locations, CDN
  behaviors, deploy mechanics) worth keeping.

### 2. Write the session log

Create `docs/sessions/YYYY-MM-DD-<short-slug>.md` (today's date, kebab-case
topic) with:

```
# Session: <title> — YYYY-MM-DD

## Goal
## What was done (grouped by theme, with file paths)
## Decisions & root causes (the non-obvious "why")
## Verification (what was checked and how)
## Server / environment notes
## Follow-ups / known issues
```

Keep it factual and compact. No chat transcript — the distilled record.

### 3. Update docs

- Create/append an entry in `docs/sessions/README.md` (index table:
  date | session | summary).
- If the session changed project state (new subsystem, new workflow, new
  conventions), update the matching section of `PROJECT_HANDOFF.md` and any
  affected doc in `docs/` (e.g. `docs/ui/UI_GUIDELINES.md` for theme work,
  `docs/upgrades/UPGRADE_HISTORY.md` for upgrades, `CORE_PATCH_REGISTER.md`
  for new core patches).
- Update `AGENTS.md` ONLY if a documented rule/workflow itself changed.

### 4. Commit (threshold rule)

- Count changes: `git status --short | wc -l` files OR
  `git diff --stat | tail -1` insertions — **commit when ≥ 50**
  (files+insertions combined, judgment call on huge generated files).
- Stage everything relevant EXCEPT: `.vscode/`, machine-local junk, secrets.
- Commit message: `Session: <title> (<date>) — <one-line summary>`.
- Push: `git push origin HEAD` (this repo's update workflow relies on the
  fork being current).

If under 50 changes, report the session log was written but the threshold was
not met, and ask before committing.
