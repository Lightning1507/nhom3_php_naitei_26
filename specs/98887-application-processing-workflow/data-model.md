# Data Model — F05 Application Processing Workflow

No new migrations. All columns below already exist from F00/F04. F05 only *uses* them and adds
code (Actions, Policies, controllers, views, factories).

## 1. `applications`

| Column | Type | F05 usage |
|---|---|---|
| `id` | bigint PK | |
| `application_code` | string unique | shown in board/worklist/result |
| `citizen_id` | FK users | owner (via `citizen()`) |
| `service_type_id` | FK service_types | determines `responsible_department_id` + `processing_time_days` |
| `assigned_staff_id` | FK users nullable | current handler; `null` = unassigned (claimable) |
| `status` | enum `ApplicationStatus` | state machine source of truth |
| `form_data` | json | untouched by F05 |
| `submitted_at` | datetime | overdue calc base (`+ processing_time_days`) |
| `processing_started_at` | datetime nullable | set on first `received → processing` |
| `completed_at` | datetime nullable | set on `approved`/`rejected` |
| `result_note` | text nullable | set on approve |
| `rejection_reason` | text nullable | required on reject |
| `deleted_at` | soft delete | existing |

**F05 rules**
- Status transitions only via `ApplicationTransitionMap` (see research.md) inside a
  `DB::transaction` with `lockForUpdate` on the row.
- Every transition inserts one `application_status_histories` row (`changed_by` = actor).
- `processing_started_at` set only on first `received → processing` (guard `=== null`).
- `completed_at` set only on terminal `approved`/`rejected`.
- `result_note`/`rejection_reason` mutually exclusive by terminal status.

## 2. `application_assignments` (append-only ledger)

| Column | Type | F05 usage |
|---|---|---|
| `id` | bigint PK | |
| `application_id` | FK applications | |
| `staff_id` | FK users | assigned staff |
| `department_id` | FK departments nullable | department snapshot at assign time |
| `assigned_by` | FK users | Manager/Super Admin, or self on claim |
| `assigned_at` | datetime | default now |
| `ended_at` | datetime nullable | `null` = active row |
| `note` | text nullable | e.g. reassign reason |

**F05 rules**
- Append-only: never update a row; a reassign/claim closes the active row (`ended_at = now`) and
  inserts a new active row — same transaction as `applications.assigned_staff_id` update.
- Model helper to add in #99475:
  `Application::activeAssignment(): ?ApplicationAssignment` (first `ended_at` null).
- Exactly zero or one active row per application at any time.

## 3. `application_status_histories` (append-only timeline)

| Column | Type | F05 usage |
|---|---|---|
| `id` | bigint PK | |
| `application_id` | FK applications | |
| `from_status` | enum nullable | null for very first record |
| `to_status` | enum | new status |
| `changed_by` | FK users | actor |
| `note` | text nullable | supplement note / result note / rejection reason (also mirrored on application) |
| `created_at` | datetime | `UPDATED_AT = null`, immutable |

**F05 rules**: append-only; order by `created_at ASC, id ASC` for the citizen timeline.

## 4. `application_documents`

| Column | Type | F05 usage |
|---|---|---|
| `id` | bigint PK | |
| `application_id` | FK applications | |
| `uploaded_by` | FK users | staff (result) or citizen (supplement) |
| `document_kind` | enum `DocumentKind` | `submission` / `supplement` / `result` |
| `original_name` | string | |
| `requirement_code` | string nullable | maps to `ServiceType.document_requirements` via `ServiceSchema` |
| `disk`, `path`, `mime_type`, `file_size` | | stored file metadata |
| `deleted_at` | soft delete | existing |

**F05 rules**
- `result`: uploaded by assigned staff via admin endpoint while status is `processing`
  (edge case: reject → no result doc allowed; upload blocked once `rejected`).
- `supplement`: citizen upload while status is `supplement_required` (reuses F04
  `POST /api/v1/applications/{app}/documents`, which already keys `document_kind` off status).
- Download authorized by existing `ApplicationDocumentPolicy` (owner + internal).

## 5. `service_types` (read for scope + deadline)

| Column | F05 usage |
|---|---|
| `responsible_department_id` | assignment/claim scope: eligible staff = active Staff in this department |
| `processing_time_days` | overdue = `completed_at IS NULL AND submitted_at + processing_time_days < now()` |
| `document_requirements` | missing-doc computation via `ServiceSchema::normalizeDocumentRequirements` |

## 6. `departments` / `users` (scope source)

- `departments.leader_id` → Manager scope (reuse `Department::scopeVisibleTo`).
- `users.role`, `users.is_active` → `scopeEligibleDepartmentStaff` (active Staff),
  `scopeEligibleDepartmentLeaders` (active Manager), `canAccessProtectedResources()`.
- `User::assignedApplications()` and `Department::applicationAssignments()` already exist.

## Factories needed (in #99475)

- `ApplicationFactory` — status, service_type, citizen, submitted_at, optional
  assigned_staff_id/processing_started_at/completed_at/result_note/rejection_reason.
- `ApplicationAssignmentFactory` — active or ended, assigned_by, department_id.
- `ApplicationStatusHistoryFactory` — from/to status, changed_by, note.
- Existing: `UserFactory` (`withRole`/`staff`/`manager`), `ServiceTypeFactory`, `ServiceCategoryFactory`,
  `DepartmentFactory`.