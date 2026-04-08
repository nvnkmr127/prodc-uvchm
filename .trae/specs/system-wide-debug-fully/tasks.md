# Tasks

- [x] Task 1: Create `app/Console/Commands/SystemDiagnostics.php`: This command will be the "master" diagnostic tool that executes other health checks and analyzes logs.
  - [x] SubTask 1.1: Implement command signature `system:diagnose {--full : Run exhaustive tests}`.
  - [x] SubTask 1.2: Add logic to execute and capture output from existing commands (`health:check`, `system:final-test`, `backup:monitor`).
  - [x] SubTask 1.3: Add a log analyzer that reads the last 100-500 lines of `storage/logs/laravel.log` and groups exceptions.
  - [x] SubTask 1.4: Add environment variable validation (check for `ETIMEOFFICE_API_URL`, `GOOGLE_DRIVE_FOLDER_ID`, etc.).
  - [x] SubTask 1.5: Implement a summary table for the final report.

- [x] Task 2: Enhance `app/Console/Commands/FixSystemIssues.php`: Update the existing fix command with more checks and automated fixes.
  - [x] SubTask 2.1: Add `checkETimeOfficeSync()` to detect stalled syncs or missing mappings.
  - [x] SubTask 2.2: Add `checkBackupHealth()` to verify recent backup success.
  - [x] SubTask 2.3: Implement `fixETimeOfficeMappings()` to suggest or auto-map students based on enrollment numbers.
  - [x] SubTask 2.4: Add a check for database migration status using `migrate:status`.

- [x] Task 3: Final Verification: Run the new diagnostic command and verify it correctly identifies existing system states.
  - [x] SubTask 3.1: Execute `php artisan system:diagnose` and verify the output.
  - [x] SubTask 3.2: Execute `php artisan system:fix --dry-run` to ensure new checks are working.

# Task Dependencies
- [Task 1] depends on existing commands (`health:check`, etc.).
- [Task 3] depends on [Task 1] and [Task 2].
