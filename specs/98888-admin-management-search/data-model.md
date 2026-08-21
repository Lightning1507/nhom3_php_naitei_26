# Data Model: F07 - Admin Management & Search

**Feature**: `98888-admin-management-search`  
**Date**: 2026-08-21

F07 introduces no new domain entity and initially requires no schema migration. It reads and safely changes existing F01-F06 entities.

## Existing entities used by F07

### User

| Field | Meaning and F07 rule |
|---|---|
| `id` | Stable identity and tie-breaker for pagination/locking. |
| `name`, `email`, `citizen_id` | Super Admin search/display fields; never expose password, tokens, or session secrets. |
| `role` | Existing `citizen`, `staff`, `manager`, `super_admin` enum; F07 cannot edit it. |
| `is_active` | Reversible account-access state; the only User field F07 mutates. |
| `deleted_at` | Existing soft-delete history; F07 does not delete/restore Users. |
| timestamps | Used for newest-first User list and audit context. |

Relevant relations:

- `departments`: Staff/Manager memberships supplied by F03.
- `ledDepartments`: Departments for which the User is the current leader.
- `submittedApplications`: Citizen-owned applications.
- `assignedApplications`: Applications currently assigned to the internal User.
- `activityLogs`: audit records where the User is actor or subject.

Validation invariants:

- User-management reads and status mutation are Super Admin-only.
- F07 never changes role, identity fields, credentials, membership, leadership, or assignments.
- Deactivation cannot target the actor, last active Super Admin, active Department leader, or an internal User with a non-terminal current assignment.

### Application

| Field | Meaning and F07 use |
|---|---|
| `application_code` | Search, display, stable human identifier. |
| `citizen_id` | Ownership and Citizen search relation. |
| `service_type_id` | Service display/filter and path to responsible Department. |
| `assigned_staff_id` | Current Staff display/filter and Staff visibility scope. |
| `status` | Existing lifecycle status; F07 never mutates it. |
| `form_data` | Submitted data displayed read-only on detail. |
| `submitted_at`, `processing_started_at`, `completed_at` | Sort, date filter, overdue and detail timestamps. |
| `result`, `rejection_reason` | Terminal outcome shown read-only. |
| `deleted_at` | Archived applications are excluded by normal Eloquent queries. |

Relevant relations:

- `citizen` -> User, historical reads include soft-deleted User.
- `serviceType` -> ServiceType, historical reads include archived Service Type.
- `serviceType.responsibleDepartment` -> Department, includes archived Department for historical labels.
- `assignedStaff` -> User, includes inactive/soft-deleted Staff for historical labels.
- `documents`, `assignments`, `statusHistories` -> detail timeline and private files.

Authorization invariant:

```text
visibleTo(actor)
├── Super Admin -> all non-archived Applications
├── Manager     -> Service Type belongs to a Department actor leads
├── Staff       -> assigned_staff_id = actor.id
└── otherwise   -> empty set
```

This set is the base for list rows, valid filter IDs, direct detail, document download, and dashboard metrics.

### ServiceType

Relevant fields are `id`, `name`, `responsible_department_id`, `processing_time_days`, `is_active`, and `deleted_at`.

- Provides Service label/search/filter.
- Links Manager scope to the responsible Department.
- Supplies processing duration for the overdue predicate.
- Archived/inactive rows referenced by visible Applications remain readable and are labeled accordingly.

### Department

Relevant fields are `id`, `name`, `code`, `leader_id`, and `deleted_at`.

- `leader_id` defines Manager visibility; ordinary membership does not.
- Provides Department display/filter.
- An active, non-archived Department leadership relation blocks Manager deactivation.

### ApplicationAssignment

Relevant fields are `application_id`, `staff_id`, `department_id`, `assigned_by`, `assigned_at`, `ended_at`, and `note`.

- Current assignment remains represented by `Application.assigned_staff_id`.
- Assignment rows are immutable history displayed deterministically by `assigned_at`, then `id`.
- Staff and actor relations include soft-deleted Users for historical display.

### ApplicationStatusHistory

Relevant fields are `application_id`, `from_status`, `to_status`, `changed_by`, `changed_at`, and `note`.

- Read-only in F07; F05/F06 own creation semantics.
- Display order is `changed_at`, then `id`.
- The actor relation includes soft-deleted Users.

### ApplicationDocument

F07 lists document metadata only after Application authorization and serves content only through an authorized controller response from private storage. It does not expose storage paths or create a public URL. The uploader relation is soft-delete-aware for historical display.

### ActivityLog

An account-status change creates one existing ActivityLog record:

| Attribute | Value |
|---|---|
| event | `user.activated` or `user.deactivated` |
| actor | current Super Admin |
| subject | affected User |
| metadata.before.is_active | previous boolean |
| metadata.after.is_active | new boolean |
| request context | timestamp plus existing IP/user-agent fields |

The User update and audit insert share one database transaction; either both commit or both roll back.

## Derived read models

### Admin Application row

```text
application id/code
citizen name + citizen ID
service name
responsible department code/name
current assigned Staff name or "Chưa phân công"
status
submitted date/time
overdue boolean
detail URL
```

### Application filter criteria

| Query field | Validation | Database meaning |
|---|---|---|
| `q` | trimmed, nullable, max 100 | escaped case-insensitive match across code/Citizen name/Citizen ID/Service name |
| `status` | base enum or `completed` | exact enum, or approved/rejected group |
| `service_type_id` | positive integer in scoped options | exact Service Type |
| `department_id` | positive integer in scoped options | Service Type responsible Department |
| `assigned_staff_id` | positive integer in scoped options | current assigned Staff |
| `submitted_from` | `Y-m-d` | `submitted_at >= local start-of-day` |
| `submitted_to` | `Y-m-d`, not before from | `submitted_at < next local start-of-day` |
| `overdue` | optional boolean | non-terminal and past calculated deadline |
| `sort` | whitelisted mode | mapped columns/direction with `id` tie-breaker |
| `page` | positive integer | fixed 20-row offset page |

All supplied criteria combine with `AND`; the keyword's internal fields combine with one grouped `OR`.

### Dashboard metrics

| Metric | Definition |
|---|---|
| total | all Applications in `visibleTo(actor)` |
| received | status `received` |
| processing | status `processing` |
| supplement required | status `supplement_required` |
| completed | status in `approved`, `rejected` |
| overdue | status not terminal and calculated deadline before now |

### Admin User row

```text
user id/name/email/citizen ID
role
active/inactive state
department membership/leadership summary
created timestamp
detail URL
```

User filters use `search` (trimmed literal name/email/citizen-ID match), `role`, `status=active|inactive`, and positive `page`; all combine with `AND`, order is `created_at DESC, id DESC`, 20 rows per page.

## State transition: User account access

```text
active --deactivate--> inactive
inactive --activate--> active
```

Guards apply only to `active -> inactive`; both directions require Super Admin authorization. A request whose desired state already equals current state is a successful no-op without an audit entry.

Transaction sequence:

1. Resolve authorization before presenting the action and again server-side on mutation.
2. Begin transaction; for Super Admin deactivation lock active Super Admin rows in stable ID order, otherwise lock the affected User directly.
3. Reload actor/target and re-evaluate self/last-admin/leadership/current-unfinished-assignment guards.
4. Update only `is_active`.
5. Insert the ActivityLog event with before/after state.
6. Commit, then use Post/Redirect/Get with a flash message.

F05 assign/claim must lock and revalidate the User's active Staff state before locking/writing the Application so a deactivation and assignment cannot both succeed from stale data.

## Persistence and index decision

- No new table, column, foreign key, or package is planned.
- Existing indexes on Application foreign keys, status, submitted time, and User unique search fields are the baseline.
- Query-count and PostgreSQL plan/timing benchmarks at 10,000 Applications/Users are implementation gates.
- A targeted index migration is allowed only when measured evidence identifies a slow predicate/order; any such migration must record the query plan and write-cost tradeoff in the implementation PR.
