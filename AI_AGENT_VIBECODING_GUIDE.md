# AI Agent Vibe Coding Guide

A practical reference for using AI agents (like Claude Code) to build, debug, and ship features fast.

---

## What is Vibe Coding?

Vibe coding means driving development through natural language — you describe what you want, the AI writes and runs the code, you review and steer. You stay at the idea level, the agent handles the implementation details.

---

## Project Setup Checklist

Before your first session, do these once:

```
[ ] Add a CLAUDE.md file at project root (tells the agent how your project works)
[ ] Make sure the project can run locally (npm install / composer install / etc.)
[ ] Have your .env configured and database seeded
[ ] Know your branch strategy (feature branches recommended)
```

### Minimal CLAUDE.md Template

```markdown
# Project Name

## Stack
- Backend: Laravel 11 / Node 20 / Django 4 (pick yours)
- Frontend: React + Tailwind / Blade / Vue
- Database: MySQL / PostgreSQL / SQLite
- Auth: Laravel Breeze / JWT / Passport

## Run Locally
npm install && npm run dev
php artisan serve

## Run Tests
php artisan test
npm test

## Key Conventions
- Controllers go in app/Http/Controllers/{Role}/
- Always use policy-based authorization
- API responses follow { success, message, data } shape
- Use Feature flags for incomplete work

## Branch Rules
- Feature branches: feature/short-description
- Never push directly to main
```

---

## How to Talk to the Agent

### Be Specific About What You Want

| Instead of...               | Say this...                                                              |
|-----------------------------|--------------------------------------------------------------------------|
| "fix the bug"               | "The backup restore modal sends `backup_filename` but the controller validates `filename` — fix the mismatch" |
| "add a feature"             | "Add a bulk-delete button to the students list that calls DELETE /admin/students with an array of IDs" |
| "make it look better"       | "Style the backup index page cards to match the existing dashboard card style using the same Tailwind classes" |
| "write tests"               | "Write PHPUnit feature tests for BackupController::restoreDatabase() covering: file not found, invalid extension, and success cases" |

### Useful Prompt Patterns

**Explore first, then build:**
```
"Look at how PaymentController handles pagination, then apply the same pattern to BackupController::index()"
```

**Fix with context:**
```
"The restore settings modal at resources/views/admin/backups/index.blade.php line 497 is broken.
The field is named backup_filename but BackupController::restoreSettings() at line 750 validates 'filename'.
Fix both ends."
```

**Add a full feature:**
```
"Add a scheduled backup status widget to the admin dashboard. It should:
1. Show last backup date and size
2. Show next scheduled backup time
3. Have a green/yellow/red health indicator
4. Pull data from BackupService::getLocalBackups()
Use the existing card component style."
```

**Refactor safely:**
```
"Refactor BackupController — extract the Google Drive upload logic into BackupService.
Don't change any method signatures or route behavior."
```

---

## Agent Workflow (Step by Step)

```
1. Tell the agent the task
        │
        ▼
2. Agent explores relevant files
   (reads controllers, routes, models, views)
        │
        ▼
3. Agent makes changes
   (edits files, may run tests)
        │
        ▼
4. You review the diff
   (git diff or IDE diff view)
        │
        ├── Looks good? → "commit and push"
        │
        └── Something wrong? → "that broke X, revert the change to Y"
```

---

## Branch & Git Commands to Know

```bash
# Let the agent commit
"commit these changes with a clear message and push to feature/backup-restore"

# Review before committing
"show me what changed before you commit"

# Undo last change
"revert the last change to BackupController.php"

# Work on a new feature
"create a new branch feature/student-bulk-delete and implement..."
```

---

## Effective Session Patterns

### Starting a Session
Always orient the agent at the start:
```
"We're working on the admin backup module.
The relevant files are:
- app/Http/Controllers/Admin/BackupController.php
- app/Services/BackupService.php
- resources/views/admin/backups/index.blade.php
- routes/web.php (lines 816–840)

Today's task: add a progress bar when a backup is running."
```

### Splitting Big Features
Break large features into messages:
```
Message 1: "Add the API endpoint POST /admin/backups/progress that returns { status, percent, message }"
Message 2: "Now add the frontend polling logic in the backup index view to call that endpoint every 2 seconds while backup is running"
Message 3: "Add the progress bar UI component that the polling updates"
```

### When the Agent Gets Stuck
```
"You're going in the wrong direction. Stop and just tell me:
1. What files are relevant to this bug?
2. What is the root cause?
3. What is the minimal fix?"
```

---

## What the Agent Does Well

| Task | Notes |
|------|-------|
| CRUD scaffolding | Controller + routes + views + migrations in one shot |
| Bug hunting | Give it the error message and the file, it finds the cause fast |
| Refactoring | Extract method, rename, move class — safe when you review the diff |
| Writing tests | Give it the method signature and edge cases, it writes the test |
| Explaining code | "Explain how BackupService::createPHPDatabaseBackup works" |
| Adding UI | Describe the component, it matches your existing style |
| Route/auth fixes | Missing middleware, wrong permission name, CSRF issues |

---

## What to Double-Check Yourself

The agent is fast but you are the reviewer. Always check:

- **Security**: Are new routes protected by the right middleware/permission?
- **Validation**: Does user input get validated before use?
- **Edge cases**: What happens if the uploaded file is empty, or the DB is down?
- **N+1 queries**: Does a new loop call the DB inside it? Add `->with()` eager loading.
- **Environment differences**: Does it hardcode paths or values that differ between dev/prod?

---

## Common Mistakes to Avoid

```
# Don't do this
"fix everything"           ← too vague, agent will guess wrong

"rewrite the whole file"   ← high risk of breaking things

"just make it work"        ← skips understanding the real bug

# Do this instead
"fix only the restore settings validation mismatch in BackupController line 750"
```

---

## Debugging with the Agent

When something is broken, give the agent:

1. **The error message** (full stack trace if available)
2. **The file and line** where it breaks
3. **What you expected** vs **what happened**

Example:
```
"Getting this error when clicking Restore Settings:
  422 Unprocessable Content
  { message: 'The filename field is required.' }

The modal is at resources/views/admin/backups/index.blade.php line 495.
The controller validation is at BackupController.php line 750.
Expected: the modal submits the filename and it restores successfully."
```

---

## Prompts for This Project (Laravel + Admin Panel)

Copy-paste ready prompts for common tasks:

```
# Add a new admin route with permission
"Add a GET route /admin/reports/backup-history protected by 'manage settings' permission.
Create the controller method and a basic Blade view showing a table of past backups."

# Add form validation
"Add client-side validation to the manual backup form — the type field must be selected before submit."

# Add a notification
"After a successful backup, show a toast notification using the existing alert system in the view."

# Add an API endpoint
"Add GET /api/admin/backup/status that returns the last backup date, file size, and storage used.
Protect it with sanctum auth."

# Seed test data
"Add a database seeder that creates 3 sample backup records in storage/app/backups/ for testing the backup list UI."
```

---

## Glossary

| Term | Meaning |
|------|---------|
| Vibe coding | Describing features in plain language and letting AI write the code |
| Hallucination | When the agent invents a method or file that doesn't exist — always verify |
| Diff review | Looking at `git diff` to see exactly what the agent changed before committing |
| Scaffolding | Auto-generating boilerplate: controller + model + migration + routes together |
| Context window | The amount of conversation the agent can "remember" in one session — start fresh sessions for unrelated tasks |

---

## Quick Reference Card

```
Start session   → tell the agent what module you're in + today's goal
Explore first   → "look at X before changing Y"
Small steps     → one logical change per message
Review diff     → before every commit
Name the file   → always give file paths, not just "the controller"
Give errors     → paste the full error, not just "it doesn't work"
Commit often    → after each working change so you can roll back safely
```
