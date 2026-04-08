# System-Wide Debug & Health Verification Spec

## Why
The application is a complex college management system with several critical integrations (ETimeOffice, Google Drive Backups, Webhooks, Notifications). Currently, health checks are fragmented across multiple artisan commands. A unified diagnostic tool is needed to perform a "system-wide" health check, identify issues across all modules, and provide automated or guided fixes.

## What Changes
- **MODIFIED**: `app/Console/Commands/FixSystemIssues.php` to include more comprehensive checks for:
    - ETimeOffice API connectivity and sync status.
    - Google Drive backup configuration and recent success/failure.
    - Webhook event registration and delivery status.
    - Notification service readiness.
- **NEW**: `app/Console/Commands/SystemDiagnostics.php` - A "master" command that:
    - Executes all relevant health check commands (`health:check`, `system:fix --dry-run`, `system:final-test`, `backup:monitor`, etc.).
    - Analyzes the last 100 lines of `storage/logs/laravel.log` for recurring exceptions.
    - Verifies critical `.env` variables are set.
    - Checks database migration status.
    - Provides a unified "System Health Report".

## Impact
- **Affected specs**: None (new capability).
- **Affected code**: 
    - [FixSystemIssues.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Console/Commands/FixSystemIssues.php)
    - New command: `app/Console/Commands/SystemDiagnostics.php`

## ADDED Requirements
### Requirement: Comprehensive Diagnostics
The system SHALL provide a single command `system:diagnose` that performs a full system-wide check.

#### Scenario: Running full diagnostics
- **WHEN** user runs `php artisan system:diagnose`
- **THEN** the system performs checks on:
    - Database connectivity and migrations.
    - Cache functionality.
    - File permissions (logs, storage, public).
    - External service connectivity (ETimeOffice, Google Drive).
    - Recent log errors.
    - Queue/Horizon status (if applicable).
    - Webhook and Notification health.
- **AND** displays a structured report with status (PASS/FAIL/WARN) for each component.

### Requirement: Enhanced Auto-Fixing
The `system:fix` command SHALL be expanded to handle more common configuration and data integrity issues.

#### Scenario: Fixing ETimeOffice mapping issues
- **WHEN** user runs `php artisan system:fix`
- **THEN** it identifies and offers to fix:
    - Missing biometric mappings for active students.
    - Unsynchronized attendance records.
    - Invalid fee category configurations.

## MODIFIED Requirements
### Requirement: FixSystemIssues
[FixSystemIssues.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Console/Commands/FixSystemIssues.php) SHALL include checks for ETimeOffice sync logs and backup health.

## REMOVED Requirements
None.
