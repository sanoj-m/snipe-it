---
name: saver
description: "Save the current chat/session as a durable record: write a session log into docs/sessions/, update the session index + PROJECT_HANDOFF.md, and commit everything to git when there are 50 or more changes. Invoke with 'saver', 'save session', or at the end of any substantial work session."
---

# Saver Skill

Persists a work session into the repo so the next agent (or human) can resume
without the chat. **Be token-frugal: you already know the session — don't
re-research it.**

## Budget rules (read first)

- One `git log --oneline -8` + `git status --short` is all the gathering you
  get. No diff spelunking, no re-reading files you edited this session.
- Never read `PROJECT_HANDOFF.md` or `docs/sessions/README.md` in full. For the
  index, `tail -2 docs/sessions/README.md` to copy the row format, then append.
- Write the log from memory of THIS conversation. Verify only facts you're
  unsure of (one cheap command max each).
- Don't re-verify live state, don't run tests, don't screenshot.

## Procedure

### 1. Write the session log

Create `docs/sessions/YYYY-MM-DD-<short-slug>.md`:

```
# Session: <title> — YYYY-MM-DD
## Goal
## What was done (grouped, with file paths + commit hashes)
## Decisions & root causes (the non-obvious "why" only)
## Verification (one line per check: what + result)
## Server / environment notes (only NEW facts worth keeping)
## Follow-ups / known issues
```

Compact and factual. Skip empty sections. Target ≤ 60 lines.

### 2. Update docs

- Append ONE row to `docs/sessions/README.md` (date | session | ≤ 2-line summary).
- `PROJECT_HANDOFF.md`: only if a durable rule/workflow changed, add ≤ 3 lines
  to the existing matching section. Skip otherwise.
- `CORE_PATCH_REGISTER.md`: only if a new core patch was added (should already
  be done during the work — don't redo it).
- `AGENTS.md`: only if a documented rule itself changed.

### 3. Commit (threshold rule)

- Count: `git status --short | wc -l` files + `git diff --shortstat` insertions.
  Commit + `git push origin HEAD` when ≥ 50 combined.
- Stage ONLY files this session touched. Never stage: `.vscode/`, secrets,
  other sessions' in-progress edits, user-owned untracked dirs.
- Message: `Session: <title> (<date>) — <one-line summary>`.
- Under 50: report the log was written, threshold not met, ask before committing.
