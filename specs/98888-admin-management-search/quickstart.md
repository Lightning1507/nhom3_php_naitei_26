# Quickstart: Validate F07 - Admin Management & Search

This guide is for implementation validation after F07 code exists. It deliberately does not run the current test suite until the database target has been made safe.

## 1. Safety prerequisite

Use a disposable local PostgreSQL database, for example `public_service_management_testing`, owned by a least-privileged local test user. Before any migration or PHPUnit command:

1. inspect the effective testing environment;
2. verify the host is local (`127.0.0.1`, `localhost`, or an approved isolated container);
3. verify the database name is dedicated to tests;
4. verify no shared, staging, production, or remote Supabase database is targeted.

The tracked PHPUnit configuration currently contains a remote database credential while many tests use `RefreshDatabase`. Remove that secret from version control and rotate it; do not run destructive tests with that configuration and do not copy the credential into `.env.testing` or documentation.

Example PowerShell validation after creating a safe `.env.testing`:

```powershell
php artisan about --env=testing
php artisan config:clear
php artisan migrate:status --env=testing
```

Only continue when the resolved database target is the disposable local test database.

## 2. Install/build prerequisites

```powershell
composer install
npm install
npm run build
```

No new PHP or JavaScript dependency is expected for F07.

## 3. Focused automated validation

Run focused F07 tests first, then regressions, using the safe local testing environment:

```powershell
php artisan test --testsuite=Feature --filter=ApplicationSearchTest
php artisan test --testsuite=Feature --filter=ApplicationDetailTest
php artisan test --testsuite=Feature --filter=UserManagementTest
php artisan test --testsuite=Feature --filter=DashboardTest
php artisan test --testsuite=Feature --filter=ApplicationAuthorizationTest
php artisan test --testsuite=Feature --filter=ApplicationProcessingTest
```

Then run the full backend suite and required quality gates:

```powershell
php artisan test
composer run lint
npm run lint
npm run build
```

Run the 10,000-row PostgreSQL performance benchmark separately so normal feature runs remain deterministic:

```powershell
php artisan test --group=admin-management-performance
```

Record database version, fixture volume, query counts, warm/cold state, and p95 elapsed time. If a list/search/filter/page/dashboard/User-list path exceeds two seconds, inspect `EXPLAIN (ANALYZE, BUFFERS)` before proposing a targeted index.

## 4. Authorization acceptance matrix

Seed Applications across at least two Departments, two Managers, several Staff, Citizens, Services, and all five statuses.

| Actor | Application list/detail/dashboard | User management |
|---|---|---|
| Staff | only currently assigned Applications | denied |
| Manager | only Services owned by Departments they lead | denied |
| Super Admin | all non-archived Applications | allowed |
| Citizen/guest/inactive internal | denied from Admin workspace | denied |

For Staff and Manager, paste an out-of-scope Application/document URL directly. Confirm the response does not distinguish it from a nonexistent resource. Reassign an open Application in another session and confirm the previous Staff loses access on the next request.

## 5. Application search/list scenarios

1. Search independently by Application code, Citizen name, Citizen ID, and Service name.
2. Repeat with leading/trailing spaces, case changes, Vietnamese text, `%`, `_`, and `\`; confirm literal safe behavior.
3. Apply each status, Service, Department, Staff, submitted-from, submitted-to, and overdue filter separately.
4. Combine all filters and confirm intersection semantics with no duplicates.
5. Supply reversed/invalid dates, invalid enum/boolean/page, and an out-of-scope entity ID; confirm actionable validation without protected labels/counts.
6. Create more than 20 matching rows with equal submitted timestamps; confirm deterministic order, numbered pagination, and preserved query string.
7. Confirm distinct messages for an empty authorized scope and an authorized scope with zero filtered results; reset filters from the latter.

## 6. Application detail scenarios

Open a visible Application containing form data, private documents, multiple assignments, multiple status events, and an approved/rejected result. Confirm all required sections, deterministic timelines, and authorized download.

Archive or deactivate a related Citizen, Staff, Service Type, or Department in the fixture. Confirm the historical label still renders with an inactive/archived marker. Confirm opening detail and navigating GET pages does not mutate any table.

Verify existing F05 buttons appear only under their policies and still execute through existing F05 Actions; F07 must not add a second transition path.

## 7. Dashboard scenarios

For each internal role:

1. calculate expected total, received, processing, supplement-required, completed, and overdue counts inside that actor's scope;
2. compare them with dashboard cards;
3. click each card and confirm the resulting authorized list has the same total;
4. verify a Manager with no led Department and Staff with no assignment see zero-valued metrics without errors.

## 8. User-management scenarios

As Super Admin:

1. search Users by name, email, and Citizen ID; filter every role/status combination;
2. verify 20-row deterministic pagination and retained query;
3. inspect detail and confirm no credential, token, or session value is rendered;
4. deactivate an eligible User, verify the next protected request is denied, verify history/relationships remain, and inspect the audit before/after metadata;
5. reactivate the User and verify access returns according to the unchanged role;
6. confirm repeated same-state submission is a no-op without duplicate audit.

Confirm each prohibited deactivation is rejected with no successful mutation/audit:

- current actor;
- last active Super Admin;
- Manager leading an active Department;
- Staff/Manager assigned an unfinished Application.

Repeat list/detail/status URLs as Staff, Manager, Citizen, guest, and inactive Super Admin; all must be denied server-side.

## 9. Manual UI validation

- Test at desktop width near 1089px and mobile width near 375px.
- Confirm filter wrapping, table horizontal scrolling, readable empty states, and text labels accompanying status colors.
- Navigate every control by keyboard.
- Open/cancel/confirm deactivation dialog; verify focus enters the dialog, Escape/cancel closes it, and focus returns to the trigger.
- Confirm Admin Application/User pages use Blade/Tailwind/Alpine only and the Citizen React subtree is untouched.
