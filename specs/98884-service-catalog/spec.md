# Feature Specification: Public Service Catalog Management

**Feature Branch**: `[feature-branch-name]`

**Created**: 2026-08-14

**Status**: Draft

**Input**: User description: "F02 – Public Service Catalog Management - Quản lý danh mục dịch vụ công mà Citizen có thể đăng ký. Bao gồm: Service Category, CRUD ServiceType cho Admin. Service detail: mô tả, yêu cầu hồ sơ, phí, thời gian xử lý. Citizen xem/search/filter danh sách dịch vụ. Active/Inactive hoặc Soft Delete ServiceType."
## User Scenarios & Testing *(mandatory)*

### User Story 1 - Citizen Browsing and Searching Services (Priority: P1)

Citizens need to be able to find public services they want to apply for by searching and filtering through the catalog.

**Why this priority**: Without the ability to find services, citizens cannot apply for them, which is the core purpose of the system.

**Independent Test**: Can be fully tested by creating a few active services as an Admin, then visiting the Citizen SPA to search for them by keyword or filter by category/type.

**Acceptance Scenarios**:

1. **Given** there are active services in various categories, **When** a Citizen visits the service catalog, **Then** they see a paginated list of all active services.
2. **Given** a Citizen is viewing the catalog, **When** they enter a keyword in the search bar, **Then** the list updates to show only services matching the keyword in their name or description.
3. **Given** a Citizen is viewing the catalog, **When** they select a specific Service Category filter, **Then** the list updates to show only services belonging to that category.
4. **Given** a Citizen clicks on a specific service in the list, **Then** they are taken to the service detail page showing description, required documents, fee, and processing time.

---

### User Story 2 - Admin Managing Services (Priority: P1)

Admins need to manage the catalog of public services, including creating new ones, updating details, and organizing them into categories.

**Why this priority**: The catalog must be populated and maintained by admins for the system to offer any value to citizens.

**Independent Test**: Can be fully tested by logging into the Admin portal, navigating to Service Management, and performing CRUD operations on services.

**Acceptance Scenarios**:

1. **Given** an Admin is logged into the Admin portal, **When** they navigate to Service Management, **Then** they see a list of all services (both active and inactive).
2. **Given** an Admin wants to add a new service, **When** they fill out the required fields (name, category, description, required documents, fee, processing time) and save, **Then** the new service is created and visible in the Admin list.
3. **Given** an Admin wants to remove a service temporarily, **When** they toggle the status to "Inactive", **Then** the service no longer appears in the Citizen SPA but remains in the Admin portal.
4. **Given** an Admin wants to delete a service, **When** they perform a soft delete, **Then** the service is hidden from all primary lists but its data is preserved in the database for historical application integrity.

---

### User Story 3 - Admin Managing Categories (Priority: P2)

Admins need to maintain the reference data (Categories) used to classify services.

**Why this priority**: Categories are necessary for organizing services, but change less frequently than the services themselves.

**Independent Test**: Can be fully tested by managing categories in the Admin portal and verifying they appear as options when creating/editing a ServiceType.

**Acceptance Scenarios**:

1. **Given** an Admin is in the Admin portal, **When** they create a new Service Category, **Then** it becomes available as an option when creating a ServiceType.

### Edge Cases

- What happens when an Admin attempts to soft delete a Service Category that has active services linked to it? (Should be prevented or cascaded safely based on business rules).
- How does system handle a Citizen viewing the detail page of a service that an Admin just marked as inactive? (Should show a message that the service is no longer available for new applications).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow Admins to perform CRUD (Create, Read, Update, Delete) operations on Service Categories.
- **FR-002**: System MUST allow Admins to perform CRUD operations on ServiceTypes.
- **FR-003**: System MUST capture the following details for a ServiceType: Name, Category, Description, Required Documents (text/list), Fee, and Processing Time (e.g., number of days).
- **FR-004**: System MUST support marking a ServiceType as Active or Inactive.
- **FR-005**: System MUST support Soft Delete for ServiceTypes to preserve historical integrity of applications.
- **FR-006**: System MUST provide a Citizen-facing catalog (via REST API for the React SPA) that lists only Active ServiceTypes.
- **FR-007**: System MUST allow Citizens to search the catalog by keywords.
- **FR-008**: System MUST allow Citizens to filter the catalog by Service Category.
- **FR-009**: System MUST provide a detailed view of a ServiceType for Citizens, including all relevant details (description, requirements, fee, time).

### Key Entities

- **ServiceCategory**: Represents a high-level grouping of services (e.g., "Land & Housing", "Business Registration").
- **ServiceType**: Represents a specific public service offered to citizens. Belongs to a Category. Contains details like fee, description, required documents, and processing time.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Admins can successfully create, edit, and deactivate services through the Admin Blade interface without errors.
- **SC-002**: Citizens can load the paginated list of active services via the React SPA in under 1 second.
- **SC-003**: Citizen search and filter operations return accurate results reflecting the current active service catalog.
- **SC-004**: Deactivated or soft-deleted services are completely hidden from the Citizen SPA catalog.

## Assumptions

- Admins have the necessary permissions to manage the service catalog (authentication/authorization handled in F01).
- "Required documents" is a descriptive text or structured text field (JSON/list) describing what the citizen needs to prepare, rather than actual file attachments on the Service model itself.
- Fees are represented in a standard currency format (e.g., integer for VND).
- Processing time is represented in days (integer).
- Pagination is set to a reasonable default (e.g., 15-20 items per page) for both Citizen and Admin views.
