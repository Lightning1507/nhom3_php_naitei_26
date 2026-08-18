# Data Model: Public Service Catalog Management

## Entities

### `ServiceCategory`
- `id` (PK, bigserial)
- `name` (string)
- `code` (string, unique)
- `description` (text, nullable)
- `created_at`, `updated_at`

### `ServiceType` (Acts as the Service itself)
- `id` (PK, bigserial)
- `category_id` (FK to service_categories.id)
- `responsible_department_id` (FK to departments.id)
- `name` (string)
- `code` (string, unique)
- `description` (text, nullable)
- `requirements` (text, nullable)
- `form_schema` (json, nullable)
- `document_requirements` (json, nullable)
- `processing_time_days` (integer, nullable)
- `fee` (decimal, default 0)
- `is_active` (boolean, default true)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

## Relationships
- `ServiceCategory` has many `ServiceType` (category_id)
- `ServiceType` belongs to `ServiceCategory`

## Validations
- `name` and `code` must be required.
- `fee` must be >= 0.
- `processing_time_days` must be > 0.
- `category_id` must exist.
- Cannot delete a `ServiceCategory` if it has linked `ServiceType` records (restrict on delete).
