# Data Model: F01 - Authentication, User Profile & Authorization

## User

Represents both Citizen and internal users.

**Existing backing table**: `users`

**Fields used by F01**:

- `id`: primary key
- `name`: display and profile name
- `email`: normalized unique login email
- `password`: hashed credential
- `role`: one of `citizen`, `staff`, `manager`, `super_admin`
- `citizen_id`: CCCD for Citizen accounts; nullable for internal users; unique when present
- `date_of_birth`: Citizen profile date of birth
- `phone`: Citizen profile phone number
- `address`: Citizen profile contact address
- `is_active`: whether login and protected access are allowed
- `deleted_at`: soft delete marker

**Validation rules**:

- Citizen registration requires valid name, email, password, CCCD, date of birth, phone, and address.
- Email is trimmed, lowercased, and unique.
- CCCD is trimmed, numeric-only, exactly 12 digits, and unique.
- Self-registration always sets `role = citizen`; submitted role values are rejected or ignored.
- Citizen profile update allows only name, date of birth, phone, and address.
- Citizen profile update must reject any attempt to change `citizen_id`, `email`, `role`, `password`, or `is_active`.

**Relationships**:

- User submits many `Application` records through `applications.citizen_id`.
- User can be assigned many `Application` records through `applications.assigned_staff_id`.
- User has many `ActivityLog` records through `activity_logs.actor_id`.
- Internal users can belong to departments and service types through existing pivot tables.

## Role

Represents the current authorization class of a user.

**Existing implementation**: `App\Enums\UserRole`

**Values**:

- `citizen`
- `staff`
- `manager`
- `super_admin`

**Rules**:

- Each user has exactly one current role.
- Citizen routes require `citizen`.
- Admin routes require one of `staff`, `manager`, `super_admin`.
- Role changes are out of scope for F01.

## Authentication Session

Represents an active browser session.

**Existing backing table**: `sessions`; Sanctum stateful SPA behavior uses Laravel session cookies.

**Rules**:

- Login creates or rotates a valid authenticated session.
- Logout invalidates the current session.
- Protected resources reject missing, expired, revoked, inactive, deleted, or wrong-role sessions.
- Citizen SPA must rely on cookies and CSRF, not browser-stored bearer tokens.

## Security Event

Represents auditable authentication and authorization activity.

**Existing backing table**: `activity_logs`

**Events for F01**:

- `citizen.registered`
- `citizen.login_succeeded`
- `citizen.login_failed`
- `citizen.logout`
- `admin.login_succeeded`
- `admin.login_failed`
- `admin.logout`
- `access.denied`
- `profile.updated`

**Rules**:

- Events should include actor when available, action name, subject when applicable, timestamp, and structured metadata such as route area and denial reason.
- Failed login events must not expose whether email or CCCD exists in user-facing responses.

## State Transitions

No Application workflow states are changed by F01.

User auth state transitions:

```text
guest -> authenticated citizen -> logged out
guest -> authenticated internal user -> logged out
authenticated -> denied when role, ownership, active status, or session validity fails
```
