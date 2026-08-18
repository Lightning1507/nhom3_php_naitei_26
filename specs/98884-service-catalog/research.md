# Research: Public Service Catalog Management

## Technical Context Unknowns
None. Architecture and constraints are clearly defined in the `constitution.md`.

## Technology Choices
- **Citizen Catalog API**: Laravel REST API under `/api/v1/` consumed by React SPA.
  - *Rationale*: Constitution dictates Citizen Site MUST be a React SPA consuming JSON endpoints.
- **Admin Management**: Laravel Blade with Tailwind CSS and Alpine.js.
  - *Rationale*: Constitution dictates Admin Site MUST use server-side rendering under `/admin/`.
- **Soft Deletes**: Eloquent `SoftDeletes` trait on the `Service` model.
  - *Rationale*: Standard Laravel approach to preserve historical application integrity when a service is deleted.
- **Search & Filtering**: Eloquent query scopes for keyword search and category/type filtering.
  - *Rationale*: Safest and simplest approach within Laravel's ecosystem for this scale.
