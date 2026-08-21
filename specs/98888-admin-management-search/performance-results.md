# F07 Admin Management Performance Results

## Benchmark setup

- Date: 2026-08-21
- Database: PostgreSQL 17.5 on isolated localhost
- Dataset: 10,000 Applications, 10,000 benchmark Citizens, 10 Staff, and 1 Super Admin
- Samples: 20 authenticated HTTP requests per scenario after fixture creation
- Measurement: end-to-end Laravel Feature response time; p95 is the nineteenth ordered sample
- Test: `tests/Feature/Admin/AdminManagementQueryPerformanceTest.php`
- Group: `admin-management-performance`

Run independently with an approved isolated testing database:

```bash
php artisan test --testsuite=AdminManagementPerformance
```

## Results

| Scenario | p95 response | Maximum queries | Slowest captured query | Root plan node | Plan time | Buffer hits / reads |
|---|---:|---:|---:|---|---:|---:|
| Application list | 291.22 ms | 15 | 13.33 ms | Unique | 7.12 ms | 205 / 0 |
| Application literal search | 461.48 ms | 15 | 170.86 ms | Limit | 136.69 ms | 30,203 / 0 |
| Application status + Service filter | 319.36 ms | 16 | 9.71 ms | Aggregate | 9.86 ms | 206 / 0 |
| Application page 400 | 310.85 ms | 15 | 33.20 ms | Aggregate | 12.63 ms | 206 / 0 |
| Scoped dashboard | 56.65 ms | 2 | 24.38 ms | Limit | 22.91 ms | 206 / 0 |
| User search + role/status + page 250 | 449.66 ms | 3 | 149.53 ms | Limit | 173.59 ms | 15,272 / 0 |

The query-count limits enforced by the benchmark are 16 for each Application-list path, 3 for the dashboard, and 4 for the User list. These bounds cover the fixed pagination, scoped filter-option, eager-load, and summary queries and detect N+1 growth.

## Acceptance decisions

- **SC-002 — PASS**: all Application list, search, filter, and pagination scenarios have p95 below 2,000 ms. The slowest measured Application p95 was 461.48 ms.
- **SC-006 — PASS**: the User-management combined search/filter/page scenario has p95 449.66 ms, below 2,000 ms.
- **Dashboard — PASS**: the six scoped metrics are produced by one conditional aggregate query with p95 56.65 ms; one additional fixed query refreshes the authenticated account state before authorization.
- **Bounded queries — PASS**: all paths remained within their fixed query-count budgets at reference scale.

## Index decision

No migration or PostgreSQL extension is added. Every measured path passes its acceptance threshold with substantial headroom, and the captured `EXPLAIN (ANALYZE, BUFFERS)` summaries show warm-buffer execution without disk reads. Adding speculative indexes would increase write and maintenance cost without an evidence-backed need.

The literal multi-relation searches remain the most expensive paths because they intentionally support contains matching across multiple fields. If production-scale measurements later breach the two-second objective, rerun this benchmark against representative data and use the captured plans to justify a targeted index or search strategy change.
