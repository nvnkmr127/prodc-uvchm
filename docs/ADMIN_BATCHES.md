# `/admin/batches` — Batch Command Center

A functional map of the Batch module: what it does, what it links to, what
depends on it, what happens on status/internship changes, and where it touches
WhatsApp, payments and the academic year. The last section lists **issues and
missing pieces** found while tracing the code.

> Scope: this document covers the **Batch** feature only (controller, model,
> routes, views, migrations) and its relationships to other modules. It does
> not re-document those other modules in depth.

---

## 1. What a "Batch" is

A **Batch** is a cohort of students taking one **Course** in one **Academic
Year** over a date range (e.g. "Spring 2025 Intake"). It is the central pivot
that ties a student to a course, a fee structure, a timetable, practical
groups and attendance.

**Table:** `batches`
(`database/migrations/2025_06_17_132929_create_batches_table.php` + later ALTERs)

| Column | Source migration | Notes |
|---|---|---|
| `id` | create_batches_table | PK |
| `course_id` | create_batches_table | FK → `courses`, `onDelete('cascade')` |
| `name` | create_batches_table | free text, **not unique** |
| `start_date`, `end_date` | create_batches_table | cast to `date` |
| `status` | 2025_07_29_..._add_status_to_batches | **enum** `active/inactive/completed/cancelled`, default `active` |
| `is_on_internship` | 2025_11_20_..._add_internship_flag | boolean, default `false` |
| `internship_start_date` | 2025_12_15_..._add_internship_start_date | nullable date |
| `academic_year_id` | 2025_10_03_000001_add_academic_year_to_core_tables | FK → `academic_years` (added later, backfilled to current year) |

**Model:** `app/Models/Batch.php`
- Traits: `HasAcademicYear`, `HasFactory`, `WebhookEnabled`.
- Casts: dates, `is_on_internship => boolean`.
- Accessor: `getIsActiveAttribute()` → `status === 'active'`.

---

## 2. Routes & actions

All under the `admin` prefix (`routes/web.php` ~line 304+), controller
`App\Http\Controllers\Admin\BatchController`.

| Route name | Verb / URI | Controller method | What it does |
|---|---|---|---|
| `admin.batches.index` | GET `/admin/batches` | `index` | List batches + stats + filters (course, academic year) |
| `admin.batches.store` | POST `/admin/batches` | `store` | Create batch (from "Add Batch" modal) |
| `admin.batches.update` | PATCH `/admin/batches/{batch}` | `update` | Edit batch (from "Edit" modal) |
| `admin.batches.destroy` | DELETE `/admin/batches/{batch}` | `destroy` | Delete batch (only if no students) |
| `admin.batches.manageStudents` | GET `.../manage-students` | `manageStudents` | Screen to add/remove students |
| `admin.batches.syncStudents` | POST `.../sync-students` | `syncStudents` | Persist the student assignment set |
| `admin.batches.graduate` | POST `.../graduate` | `graduate` | Mark all **active** students in the batch as `graduated` |
| `admin.batches.toggleInternship` | POST `.../toggle-internship` | `toggleInternship` | AJAX switch: In College ↔ On Internship |

**Related routes that consume a batch (defined elsewhere):**
- `admin.batches.practical-groups` → `TimetableController@getBatchPracticalGroups`
- `admin.student-fees.generate-for-batch` → `StudentFeeController@generateForBatch`
- `students.get-batches-for-course` → `StudentController@getBatchesForCourse`
- `daily-attendance.batch-students`, `lab-allocation.*.batch`, API `batches/{batch}/students`.

The `index` view itself only exposes: Create, Edit, Manage Students, Graduate,
Delete, and the internship toggle. Fee-structure setup, fee generation,
timetable and practical-group management are **not linked from this page**.

---

## 3. Relationships (what a Batch links to)

Defined in `app/Models/Batch.php`:

| Relationship | Type | Target | Meaning |
|---|---|---|---|
| `course()` | belongsTo | `Course` | The course this cohort studies |
| `academicYear()` | belongsTo (via trait) | `AcademicYear` | The year the batch belongs to |
| `students()` | hasMany | `Student` (`batch_id`) | Enrolled students |
| `timetableEntries()` | hasMany | `Timetable` | Scheduled classes |
| `practicalGroups()` | hasMany | `PracticalGroup` | Lab/practical sub-groups |
| `feeStructure()` | hasOne | `FeeStructure` | Fee definition for the cohort |
| `subjects()` | hasManyThrough | `Subject` via `Course` | ⚠️ flagged as "might not be correct" in code |

**Reverse / indirect dependents** (things that read `batch_id`):
- `Student.batch_id` → and `Student.course` / `Student.getCourseAttribute()` are
  **derived through the batch** (`hasOneThrough`). So a student's course *is*
  whatever their batch's course is.
- `Attendance.batch_id`, `Timetable.batch_id`, `PracticalGroup.batch_id`,
  `FeeStructure.batch_id`.
- `EnrollmentService::generateForBatch()` builds enrolment numbers from the batch.

```
Course ──< Batch >── AcademicYear
              │
      ┌───────┼───────────┬───────────────┬───────────────┐
   Student  Timetable  PracticalGroup  FeeStructure   Attendance
      │                                     │
  StudentFee ◄──────────(generated from)────┘
      │
  Payment / PaymentReminder ──► WhatsAppService
```

---

## 4. What each action actually does

### Create (`store`)
- Validates `academic_year_id`, `course_id`, `name`, `start_date`,
  `end_date` (`after_or_equal:start_date`), `status` (nullable), `is_on_internship`.
- Defaults `status` → `active`; sets `is_on_internship` from checkbox presence.
- Note: the **Add modal has no status field and no internship field**, so new
  batches are always `active`, `is_on_internship = false`.

### Edit (`update`)
- Validates the same set; `status` is **required** here and limited to
  `in:active,completed,archived`.
- `is_on_internship` is set via `$request->boolean('is_on_internship')`.

### Delete (`destroy`)
- Blocks deletion if `students()->count() > 0`.
- Does **not** consider timetables, practical groups or fee structure.

### Manage / Sync students (`manageStudents`, `syncStudents`)
- Lists students already in the batch and unassigned students
  (`whereNull('batch_id')`), **querying `withoutGlobalScope('academic_year')`**
  (deliberately cross-year).
- `syncStudents` runs in a transaction: unassign anyone removed from the set,
  assign everyone in the set to this `batch_id`.

### Graduate (`graduate`)
- `Student::where('batch_id', …)->where('status','active')->update(['status'=>'graduated'])`.
- Redirects with a count message. **Does not** change the batch's own `status`.

### Toggle internship (`toggleInternship`, AJAX)
- Flips `is_on_internship`; sets `internship_start_date = now()` when turning
  **on**, `null` when turning **off**. Returns JSON; the table badge updates
  client-side ("On Internship" / "In College").

---

## 5. Status changes — what actually happens

There are **two different "status" concepts** here; keep them separate.

### a) Batch `status` (`active/completed/archived/…`)
- Set on create (defaults `active`) and edited via the Edit modal.
- **Effect: essentially none.** A code trace shows no business logic reads
  `batch.status` — no filtering in `index`, no gating of enrolment, fees,
  timetable or attendance. It is stored and displayed only. See issues #1, #2.

### b) `is_on_internship` (the toggle) — this one *is* functional
- `is_on_internship` is read by **`SendDailySummaryWebhook`**
  (`app/Console/Commands/SendDailySummaryWebhook.php`): students in
  internship batches are **excluded** from active-student attendance counts
  (`$q->where('is_on_internship', 0)`).
- Student-facing views render an "On Internship" badge and "Since {date}"
  (`resources/views/admin/students/_table_body.blade.php`,
  `admin/reports/students/index.blade.php`).
- Because `Batch` uses `WebhookEnabled`, toggling internship fires an
  `updated` webhook (see §8).

### c) Student status via `graduate`
- The only status change the batch page pushes onto students: `active` →
  `graduated`. Graduated students are excluded from active counts and most
  fee/attendance operational scopes (`Student` scopes `active`, `nonDropout`,
  `withOutstandingFees`, etc.).

---

## 6. Academic Year relationship

- `Batch` uses the `HasAcademicYear` trait. The trait **conditionally adds a
  global scope** filtering by the session's selected academic year when
  `config('app.enable_academic_year_global_scope', true)` is true (**default
  true**) and not in console / not an `api/*` request.
- `index()` therefore normally shows only batches for the currently selected
  year, and *also* offers an explicit `academic_year_id` filter param. The two
  are combined with `AND`. See issue #6 (the in-code comments claim the global
  scope is "DISABLED", which contradicts the default config value).
- `manageStudents`/`syncStudents` bypass the scope with
  `withoutGlobalScope('academic_year')` so students can be moved regardless of
  the active year.
- Student ↔ year is **indirect**: a student has no `academic_year_id`; their
  year is inferred from their batch (`Student::scopeForCurrentAcademicYear`
  filters `batch_id IN (batches of that year) OR batch_id IS NULL`).

---

## 7. Payments & fees relationship

There is **no direct payment code in `BatchController`.** The chain is:

```
Batch → FeeStructure (hasOne) → FeeCategories (components)
      → StudentFee rows (per student) → Payment / ComponentPayment
```

- Fee generation for a batch lives in
  `StudentFeeController@generateForBatch` (route
  `admin.student-fees.generate-for-batch`), which calls
  `ComponentPaymentService::createFeeComponentsForBatch()`. It requires the
  batch to have a `feeStructure`, else "Fee structure not found for this batch."
- **None of this is reachable from `/admin/batches`** — the page never links to
  fee-structure setup or fee generation. See issue #7.
- Outstanding/overdue/paid logic is all on `Student` (component-based), keyed by
  `student_id`, not by batch.

---

## 8. WhatsApp / notifications relationship

- **No batch-level WhatsApp feature exists.** `BatchController` sends nothing.
- WhatsApp is driven per **student / per fee**: `WhatsAppService`,
  `ComponentPaymentReminderService`, `PaymentReminderController`,
  `ProcessPendingReminders`, `SendPaymentReminder`,
  `PaymentObserver`. These act on `Student` → `student_mobile` /
  `father_mobile` and `StudentFee` records.
- The only automated batch-aware messaging is the **daily summary webhook**
  (`SendDailySummaryWebhook`), which uses `is_on_internship` to decide which
  students to count, and daily absent alerts — these are attendance webhooks,
  not batch actions.
- Because `Batch` is `WebhookEnabled`, `created`/`updated`/`deleted` events fire
  `EloquentWebhookEvent` → `UniversalWebhookListener` (used for external
  integrations such as automation platforms). Toggling internship or editing a
  batch therefore emits an outbound webhook. This is the closest thing to a
  "batch → messaging" link, and it is generic, not WhatsApp-specific.

---

## 9. Issues & missing pieces

Ordered roughly by severity.

### 🔴 #1 — Batch `status` enum vs. validation mismatch (data bug)
- DB enum: `['active','inactive','completed','cancelled']`
  (`2025_07_29_152117_add_status_to_batches_table.php`).
- Edit validation: `in:active,completed,archived` (`BatchController@update`),
  and the Edit modal `<select>` offers **active / completed / archived**.
- `archived` is **not a valid enum value** → on strict MySQL this write
  **fails**; on lenient mode it is silently coerced/truncated. Meanwhile
  `inactive` and `cancelled` are valid in the DB but **unreachable from the
  UI**. The three layers disagree.

### 🔴 #2 — Editing a batch silently turns OFF internship
- `update()` sets `is_on_internship = $request->boolean('is_on_internship')`.
- The **Edit modal has no internship checkbox** (only name, course, year,
  dates, status). So every "Save Changes" submits no internship field →
  `boolean()` returns `false` → **any batch currently On Internship is reset to
  In College on any edit**, also nulling the intended state. `internship_start_date`
  is left stale (update never touches it), so data becomes inconsistent.

### 🟠 #3 — Batch `status` is a dead/ornamental field
- Nothing reads `batch.status`. It doesn't filter the list, gate enrolment,
  stop fee/timetable/attendance operations, or drive any report. Either wire it
  up (e.g. hide `archived`/`completed` batches from active dropdowns, block
  enrolment into non-active batches) or remove it to avoid false expectations.

### 🟠 #4 — `graduate` doesn't complete the batch or log the change
- Graduating students leaves `batch.status = active`. A "graduated" cohort is
  indistinguishable from a running one.
- `Student::logBatchChange()` / status-change logging exists but is **not
  called** by `graduate()` or `syncStudents()`, so cohort moves/graduations
  aren't captured in the activity log the way individual edits are.

### 🟠 #5 — No batch capacity enforcement
- Courses carry `max_batch_size`
  (`2025_06_23_..._add_max_batch_size_to_courses_table`,
  `2025_07_17_..._add_enrollment_prefix_and_max_batch_size`), but neither
  `store()` nor `syncStudents()` checks it. A batch can be over-filled without
  warning.

### 🟠 #6 — Academic-year scope: code comments contradict behaviour
- `HasAcademicYear::bootHasAcademicYear()` is commented "DISABLED GLOBAL SCOPE"
  but actually **applies** the scope when `config('app.enable_academic_year_global_scope', true)`
  (default `true`). So the real behaviour depends on a config flag whose default
  contradicts the comment. Combined with the explicit `academic_year_id` filter
  in `index()`, selecting a different year in the filter than the session year
  can `AND` to an **empty result**, which looks like "data missing." Worth
  making the intent explicit and consistent.

### 🟡 #7 — Fee structure & fee generation not reachable from the page
- A batch's whole financial setup (`FeeStructure`, generate `StudentFee`s) has
  no entry point on `/admin/batches`, yet fee generation *requires* a batch to
  have a fee structure first. Admins must go elsewhere; there's no indicator on
  the batch row showing whether a fee structure exists.

### 🟡 #8 — `destroy` ignores child records other than students
- Deletion is blocked only when students exist. A batch with `timetableEntries`,
  `practicalGroups` or a `feeStructure` (but zero students) can be deleted,
  risking orphaned rows or an FK error depending on those tables' constraints.
  The user gets a generic failure rather than a clear message.

### 🟡 #9 — `syncStudents` moves students across years/courses silently
- Using `withoutGlobalScope('academic_year')`, the unassigned pool and updates
  span all years. Assigning a student changes their **effective course and
  academic year** (both derived through the batch) with no confirmation and no
  activity log entry. Enrolment numbers (`EnrollmentService`) are not
  regenerated on move.

### 🟡 #10 — Internship date handling is lossy
- Toggling off then on **overwrites** `internship_start_date` with the new
  `now()`, losing the original start. There is no internship *end* date, and
  editing a batch never maintains the field (see #2). Reporting "Since {date}"
  can therefore be wrong.

### ⚪ #11 — Minor / data-hygiene
- **No uniqueness** on batch `name` (per course/year) → duplicate batch names
  are allowed and confusing in dropdowns.
- `subjects()` `hasManyThrough` is self-flagged in the model as possibly
  incorrect; verify it returns the intended subjects.
- `BatchFactory` doesn't set `academic_year_id` or `status`, so factory-made
  batches can violate the current schema expectations in tests/seeders.
- Add-batch modal can't set `status` or internship at creation (only defaults),
  which is fine but asymmetric with Edit.

---

## 10. Quick reference — files

| Concern | File |
|---|---|
| Controller | `app/Http/Controllers/Admin/BatchController.php` |
| Model | `app/Models/Batch.php` |
| List / modals / JS | `resources/views/admin/batches/index.blade.php` |
| Manage students | `resources/views/admin/batches/manage_students.blade.php` |
| Routes | `routes/web.php` (~304), `routes/api.php` |
| Academic-year scope | `app/Traits/HasAcademicYear.php` |
| Webhook wiring | `app/Traits/WebhookEnabled.php`, `app/Listeners/UniversalWebhookListener.php` |
| Fee generation for batch | `app/Http/Controllers/Admin/StudentFeeController.php@generateForBatch`, `app/Services/ComponentPaymentService.php` |
| Internship in reporting | `app/Console/Commands/SendDailySummaryWebhook.php` |
| Migrations | `database/migrations/*batch*`, `2025_10_03_000001_add_academic_year_to_core_tables.php` |
