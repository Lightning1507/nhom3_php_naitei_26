# Admin SSR Contract: F07 - Admin Management & Search

**Rendering boundary**: Laravel Blade under `/admin/...`; session authentication plus `internal` middleware; all authorization is server-side.  
**Mutation convention**: CSRF-protected form, policy authorization, Form Request validation, transaction where required, then Post/Redirect/Get.

## Route matrix

| Method | URI | Route name | Authorized actor | Purpose |
|---|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | active Staff, Manager, Super Admin | Scoped operational metrics and drill-down links. |
| GET | `/admin/applications` | `admin.applications.index` | active Staff, Manager, Super Admin | Scoped Application search/filter/list. |
| GET | `/admin/applications/{application}` | `admin.applications.show` | actor for whom Application is in `visibleTo()` | Read complete Application detail. |
| GET | `/admin/applications/{application}/documents/{document}/download` | `admin.applications.documents.download` | actor authorized for parent Application and document | Stream/download private document. |
| GET | `/admin/users` | `admin.users.index` | active Super Admin | Search/filter User accounts. |
| GET | `/admin/users/{user}` | `admin.users.show` | active Super Admin | Read safe User detail. |
| PATCH | `/admin/users/{user}/status` | `admin.users.status.update` | active Super Admin passing target guards | Activate/deactivate account. |

Existing F05 mutation routes remain unchanged. Existing `/admin/users/import` routes must be declared before `{user}` routes or `{user}` must be constrained to a numeric ID, preventing `import` from being consumed by model binding.

## `GET /admin/applications`

### Query contract

| Parameter | Allowed values | Default |
|---|---|---|
| `q` | trimmed string, 1-100 chars; literal case-insensitive search | absent |
| `status` | `received`, `processing`, `supplement_required`, `approved`, `rejected`, `completed` | absent |
| `service_type_id` | positive integer present in actor's scoped options | absent |
| `department_id` | positive integer present in actor's scoped options | absent |
| `assigned_staff_id` | positive integer present in actor's scoped options | absent |
| `submitted_from` | valid local date `Y-m-d` | absent |
| `submitted_to` | valid local date `Y-m-d`, on/after `submitted_from` | absent |
| `overdue` | `1` when enabled | absent |
| `sort` | `newest`, `oldest`, `code_asc`, `code_desc`, `status_asc`, `status_desc` | `newest` |
| `page` | positive integer | `1` |

Unknown or invalid values do not reach query composition. Validation returns a normal Blade validation response without revealing whether a protected entity ID exists. Pagination is fixed at 20 and preserves the validated query string. Every sort mode appends `id` as a deterministic tie-breaker.

### Search semantics

`q` matches any of these within the actor's visibility scope:

- Application code;
- Citizen name;
- Citizen ID;
- Service Type name.

The four fields are grouped with `OR`; every other supplied filter is combined with that group using `AND`. `%`, `_`, and `\` are literal characters, not user-controlled wildcards.

### Response view model

The page receives:

- `applications`: 20-row paginator with Citizen, Service Type, responsible Department, and assigned Staff eager-loaded;
- `statusOptions`: existing statuses plus virtual `completed`;
- `serviceOptions`, `departmentOptions`, `staffOptions`: distinct choices derived from the unfiltered authorized Application set;
- validated filter state and whether any search/filter is active.

Each row displays code, Citizen, Service, Department, assigned Staff or unassigned label, status text/badge, submitted date, and detail action. The view distinguishes:

1. the actor has no Application in scope; and
2. the actor has Applications but none match the active criteria.

## `GET /admin/applications/{application}`

- Route binding may resolve the model, but the policy MUST verify it exists in `visibleTo(actor)` for this request.
- Outside-scope and nonexistent records produce the same not-found response.
- Eager-loaded read model includes Citizen, Service Type, responsible Department, current assigned Staff, form data, private document metadata, assignment history, status history, timestamps, result, and rejection reason.
- Assignment/status histories have deterministic timestamp-plus-ID ordering.
- Archived/inactive related records retain their label and show an inactive/archived badge.
- Document links use authorized controller routes; storage paths are never rendered as public URLs.
- F05 controls are included only with `@can`; their existing server policies and Actions remain authoritative.

## `GET /admin`

The dashboard returns six values calculated from the actor's canonical visibility scope:

```text
total
received
processing
supplement_required
completed = approved + rejected
overdue = non-terminal and past service processing deadline
```

Each metric links to `/admin/applications` with the corresponding validated list query. The result count of that drill-down must equal the card count at the same data snapshot.

## `GET /admin/users`

### Query contract

| Parameter | Allowed values | Default |
|---|---|---|
| `search` | trimmed string, 1-100 chars; literal match on name/email/citizen ID | absent |
| `role` | `citizen`, `staff`, `manager`, `super_admin` | absent |
| `status` | `active`, `inactive` | absent |
| `page` | positive integer | `1` |

Filters combine with `AND`; results order by `created_at DESC, id DESC`, paginate 20, and retain the query string. Response rows contain only safe profile, role, status, and organization-summary data.

## `GET /admin/users/{user}`

The safe detail view may show profile fields, role, current status, created/updated times, Department memberships, led Departments, and aggregate Application counts. It MUST NOT serialize or render password hashes, remember tokens, personal access tokens, session identifiers, reset tokens, or other authentication secrets.

## `PATCH /admin/users/{user}/status`

Request body:

| Field | Rule |
|---|---|
| `is_active` | required boolean |

Success:

- changes only `users.is_active`;
- writes one matching ActivityLog in the same transaction, except an idempotent no-op;
- redirects to User detail with a success message.

Guard failure:

- makes no User or audit success mutation;
- redirects back with a field/actionable business error;
- covers self-deactivation, last active Super Admin, active Department leader, and current unfinished Application assignee.

Authorization failure for Staff, Manager, Citizen, guest, or inactive actor occurs server-side even if the UI link/form is absent.

`UserPolicy` must preserve F01's existing active-Citizen self-view and self-update behavior. F07 adds Super Admin `viewAny`/`view` and a distinct `changeStatus` ability; it must not repurpose the general `update` ability to grant profile or role editing.

## Navigation and accessibility contract

- “Hồ sơ” appears for active internal roles.
- “Người dùng” appears only when `UserPolicy::viewAny` passes.
- Import remains separately owned by F08 and is not folded into F07 status management.
- Status is conveyed by text plus badge styling, never color alone.
- Filter controls have labels; tables support horizontal overflow on narrow screens.
- Deactivation requires a clear confirmation dialog with keyboard focus management and return focus using the existing Admin dialog behavior.
