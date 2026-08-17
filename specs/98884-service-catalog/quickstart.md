# Quickstart Validation: Public Service Catalog Management

## Prerequisites
- Laravel backend running (`php artisan serve`)
- Frontend dev server running (`npm run dev`)
- Database migrated and optionally seeded
- An Admin user account exists (from F01)

## Setup
1. Run migrations: `php artisan migrate`

## Validation Scenarios

### Scenario 1: Admin manages the catalog
1. Navigate to `/admin/login` and log in as an Admin.
2. Go to the "Service Categories" page (e.g. `/admin/service-categories`). Create a new category "Business Registration".
3. Go to the "Service Types" page. Create a new type "Certification".
4. Go to the "Services" page (`/admin/services`). Click "Create New Service".
5. Fill the form selecting the new Category and Type. Set fee to `100000` and processing days to `7`. Save it.
6. Verify the new service appears in the Admin Services list.

### Scenario 2: Citizen views and searches the catalog
1. Without logging in, navigate to the Citizen SPA service catalog page (e.g. `/services`).
2. Verify the list shows the active service created by the Admin.
3. Enter a keyword from the service name in the search bar. Verify the service is returned.
4. Click on the service to view details. Verify description, required documents, fee, and processing time are displayed accurately based on Admin input.

### Scenario 3: Admin deactivates a service
1. In the Admin portal (`/admin/services`), edit the service and set `is_active` to false.
2. Navigate back to the Citizen SPA catalog (`/services`).
3. Verify the service no longer appears in the list.
4. Try to access the service detail page by ID directly (if known) and verify it returns a "not found" or "inactive" message.
