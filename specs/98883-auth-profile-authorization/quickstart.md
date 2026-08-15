# Quickstart: F01 - Authentication, User Profile & Authorization

## Prerequisites

- PHP 8.5 is active in the shell.
- Composer dependencies are installed.
- NPM dependencies are installed.
- `.env` points to a safe development database, not a shared production-like database.
- PostgreSQL extensions are enabled in PHP: `pdo_pgsql` and `pgsql`.

## Setup

```powershell
composer install
npm install
php artisan config:clear
php artisan migrate:status
```

Only run migrations after the team confirms the target database is safe to modify:

```powershell
php artisan migrate
php artisan db:seed
```

## Run The App

```powershell
php artisan serve
npm run dev
```

## Validation Scenarios

### Citizen Registration And Login

1. Register a Citizen with a new email and valid 12-digit CCCD.
2. Confirm the response uses the standard API envelope.
3. Log in with the new account.
4. Confirm the Citizen can access the protected profile endpoint.
5. Log out.
6. Confirm the same profile endpoint now rejects access.

Expected result: registration, login, protected access, and logout all work without storing a token in browser local storage.

### Duplicate Email Or CCCD

1. Register a Citizen.
2. Repeat registration with the same normalized email or CCCD.

Expected result: the second request is rejected and no duplicate account is created.

### Admin Login Boundary

1. Log in through `/admin/login` with Staff, Manager, or Super Admin credentials.
2. Confirm access to protected Admin pages.
3. Try the same Admin area with a Citizen account.

Expected result: internal users can enter Admin; Citizen accounts are denied.

### Citizen Profile Ownership

1. Log in as Citizen A.
2. Read Citizen A profile.
3. Attempt to read or update Citizen B profile by changing identifiers in the request.
4. Attempt to update CCCD, email, role, or active status.

Expected result: only Citizen A's own allowed profile fields can change; forbidden fields remain unchanged.

### Audit Events

1. Perform successful login, failed login, logout, denied access, and profile update flows.
2. Inspect `activity_logs`.

Expected result: required events are present with actor where available, action, timestamp, subject when applicable, and metadata.

## Test Commands

```powershell
php artisan test --filter=Auth
php artisan test --filter=Profile
php artisan test --filter=Admin
php artisan test
```
