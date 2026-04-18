# Academic Years (Manage / Change)

## Purpose

Academic Years are used to:

- Define the year boundaries (name, start date, end date)
- Mark one year as the system “Current Year” (`is_current = true`)
- Let admins switch the “Currently Viewing Academic Year” (session-based) to filter data across the admin panel

## Key Concepts

### 1) Current Year (system state)

- Stored in DB: `academic_years.is_current`
- Used as the default year when no “Viewing Year” is selected in session
- Only one year is intended to be current at a time (the app enforces this by updating all rows to `is_current=false` and then setting one to `true`)

### 2) Currently Viewing Year (per-user session)

- Stored in session: `selected_academic_year_id`
- Controls which year’s data most admin screens show (via model global scopes)
- Does not change `is_current`; it only changes what you see

## Where to Manage in UI (Admin)

Admin → Manage Academic Years:

- List + switch viewing year: `/admin/academic-years`
- Create: `/admin/academic-years/create`
- Edit: `/admin/academic-years/{id}/edit`

Access is protected by: `auth` + `permission:view backend`.

## Common Admin Tasks

### Add a new Academic Year

1. Go to Admin → Manage Academic Years → “Add New Academic Year”.
2. Fill:
   - Year Name (must be unique, example: `2025-2026`)
   - Start Date
   - End Date (must be after start date)
3. Optional: tick “Mark this as the current active academic year”.

What happens:

- If you mark it current, the system clears `is_current` from all other years and sets the new year as current.
- If you don’t mark it current, it will be created as inactive (`is_current=false`).

### Change (edit) an Academic Year

1. Go to the Academic Year list.
2. Click edit.
3. Update name/dates and optionally toggle “current”.

Important behavior:

- If you check “current”, the system will set all other years to `is_current=false` and set this one to `true`.
- If you uncheck “current”, the system will set this year to `is_current=false` and will not automatically pick another year as current. This can leave the system with no `is_current=true` year.

Best practice:

- Keep exactly one current year at all times.

### Switch the “Currently Viewing Academic Year”

On the Manage Academic Years page, use the “Currently Viewing Academic Year” dropdown and click Switch (or just change selection).

What happens:

- The system stores the selected academic year ID in session: `selected_academic_year_id`.
- Most admin data screens will filter to that year automatically (if academic-year global scoping is enabled).

### Set a year as Current (without changing dates)

The system supports a dedicated endpoint to set the current year:

- `POST /admin/academic-years/{academicYear}/set-current`

This clears all other current flags and sets the chosen year to `is_current=true`.

Note:

- The Manage Academic Years UI currently does not display a “Set current” button; setting current is done via the edit form’s checkbox or by calling this endpoint.

### Delete an Academic Year (danger)

Deleting an academic year can cascade-delete related data due to foreign keys configured with `onDelete('cascade')`.

Before deleting a year, confirm you will not lose required historical data.

## Data Model (DB)

### academic_years table

- `id`
- `name` (unique)
- `start_date`
- `end_date`
- `is_current` (boolean)
- `auto_switch_enabled` (boolean; used by auto-switch command)

### Core tables linked to academic_year_id

The system adds `academic_year_id` (FK to `academic_years.id`) to:

- `batches`
- `admissions` (if table exists)
- `enquiries` (if table exists)
- `attendances` (if table exists)
- `student_fees` (if table exists)
- `payments` (if table exists)

All of these foreign keys are configured to cascade on delete.

## How Year Filtering Works (Developer Notes)

### Global scoping (default ON)

When `config('app.enable_academic_year_global_scope')` is `true`:

- Admin web requests (non-console, non-API) automatically filter many models by the selected year in session, falling back to the DB current year.
- API routes (`/api/*`) do not apply the global scope; year filtering should be explicit in API queries.
- Console commands/migrations do not apply the global scope.

### Session fallback logic

- If `selected_academic_year_id` is not set, the app falls back to the `academic_years` row where `is_current=true`.
- Layout data for dropdowns is provided via a view composer on `layouts.theme`.

## Automation (CLI)

### Diagnose academic year setup / filtering issues

- `php artisan academic-year:diagnose`

Outputs:

- Which academic years exist, and which one is current
- Whether key tables have missing `academic_year_id`
- Distribution of data by year

### Backfill missing academic_year_id values

- `php artisan academic-year:backfill`

Use when:

- You added academic years after existing data already existed, or some rows were created without year assignment.

### Auto-switch current year (optional)

- `php artisan academic-year:auto-switch`

Behavior:

- Finds an academic year with `auto_switch_enabled = true`, `start_date <= today`, and `is_current = false`
- Sets it as current (and clears current flag from others)

Important:

- This repo currently does not schedule the auto-switch command automatically. If you want this feature, add it to the scheduler (App\Console\Kernel) and ensure `php artisan schedule:run` is executed via cron.

## Troubleshooting

### “Data disappeared” after switching year

- Most likely you switched the “Viewing Year” and your data exists in a different year.
- Switch back to the expected year.
- If records exist but show in no year, run `php artisan academic-year:diagnose` and then `php artisan academic-year:backfill`.

### No current academic year

If no row has `is_current=true`:

- Set a year as current via the edit screen (check “current”) or via the set-current endpoint.

## Code References

- Controller: [AcademicYearController.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Http/Controllers/Admin/AcademicYearController.php)
- Views: [academic_years views](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/resources/views/admin/academic_years)
- View composer: [ViewServiceProvider.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Providers/ViewServiceProvider.php)
- Global scope trait: [HasAcademicYear.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Traits/HasAcademicYear.php)
- Student year scope: [Student.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Models/Student.php)
- Migrations:
  - [create_academic_years_table.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/database/migrations/2025_06_30_111230_create_academic_years_table.php)
  - [add_auto_switch_to_academic_years_table.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/database/migrations/2025_10_03_210732_add_auto_switch_to_academic_years_table.php)
  - [add_academic_year_to_core_tables.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/database/migrations/2025_10_03_000001_add_academic_year_to_core_tables.php)
- Commands:
  - [DiagnoseAcademicYear.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Console/Commands/DiagnoseAcademicYear.php)
  - [BackfillAcademicYears.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Console/Commands/BackfillAcademicYears.php)
  - [AutoSwitchAcademicYear.php](file:///Users/naveenadicharla/Documents/test/prodc-uvchm/app/Console/Commands/AutoSwitchAcademicYear.php)

