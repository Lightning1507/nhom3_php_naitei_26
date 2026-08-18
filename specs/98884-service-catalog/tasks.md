# Implementation Tasks: Public Service Catalog Management

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [X] T001 Create project structure (Directories for Controllers, Requests, Views, and JS)
- [X] T002 Verify `ServiceCategory` and `ServiceType` models are correctly configured with relationships and `$fillable` properties in `app/Models/`

---

## Phase 2: User Story 1 - Citizen Browsing and Searching Services (Priority: P1)

**Goal**: Citizens need to be able to find public services they want to apply for by searching and filtering through the catalog.

### Implementation for User Story 1

- [X] T003 [P] [US1] Create ServiceCatalogController (`app/Http/Controllers/Api/V1/ServiceCatalogController.php`)
- [X] T004 [P] [US1] Implement `index` endpoint to list active `ServiceType` records with search & filter logic
- [X] T005 [P] [US1] Implement `show` endpoint for service details
- [X] T006 [P] [US1] Register API routes in `routes/api.php` under the `v1` prefix
- [ ] T007 [US1] Add automated Feature tests for Citizen API (`tests/Feature/Api/V1/ServiceCatalogTest.php`)
- [X] T008 [P] [US1] Create React component for Catalog list (`resources/js/citizen/pages/ServiceCatalog.jsx`)
- [X] T009 [P] [US1] Create React component for Service details (`resources/js/citizen/pages/ServiceDetail.jsx`)
- [X] T010 [US1] Integrate React components with the API endpoints

**Checkpoint**: User Story 1 should be fully functional and testable independently via the SPA and API.

---

## Phase 3: User Story 2 & 3 - Admin Managing Categories and ServiceTypes (Priority: P1 & P2)

**Goal**: Admins need to manage the catalog of public services (`ServiceType`) and their classifications (`ServiceCategory`).
*(Note: Authorization is handled by another team member and is excluded from these tasks)*

### Implementation for User Story 2 & 3

- [X] T011 [P] [US2] Create Admin ServiceCategoryController (`app/Http/Controllers/Admin/ServiceCategoryController.php`)
- [X] T012 [P] [US2] Create Admin ServiceTypeController (`app/Http/Controllers/Admin/ServiceTypeController.php`)
- [X] T013 [P] [US2] Create Form Requests for validation (`StoreServiceCategoryRequest`, `UpdateServiceCategoryRequest`, `StoreServiceTypeRequest`, `UpdateServiceTypeRequest`)
- [X] T014 [P] [US2] Register Admin routes for Categories and ServiceTypes in `routes/web.php`
- [X] T015 [US2] Implement Controller CRUD logic for Categories
- [X] T016 [US2] Implement Controller CRUD logic for ServiceTypes (incorporating JSON required_documents, active/inactive toggle and soft deletes)
- [X] T017 [P] [US2] Create Blade views for Category CRUD (`resources/views/admin/service_categories/index.blade.php`, `create.blade.php`, `edit.blade.php`)
- [X] T018 [P] [US2] Create Blade views for ServiceType CRUD (`resources/views/admin/service_types/index.blade.php`, `create.blade.php`, `edit.blade.php`)
- [ ] T019 [US2] Add automated Feature tests for Admin Category/ServiceType Management (`tests/Feature/Admin/ServiceCatalogManagementTest.php`)

**Checkpoint**: Admins can manage Service Categories and ServiceTypes.

---

## Phase 4: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T020 Review code formatting and syntax against PHP/Laravel standards
- [ ] T021 Ensure strict separation between `routes/api.php` and `routes/web.php` is maintained
- [ ] T022 Final end-to-end testing of Admin to Citizen flow

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: No dependencies.
- **User Stories (Phase 2 & 3)**: Depend on Setup completion. Can proceed sequentially or in parallel.
- **Polish (Phase 4)**: Depends on all user stories being complete.
