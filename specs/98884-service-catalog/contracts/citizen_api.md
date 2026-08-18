# Citizen API Contracts

## `GET /api/v1/services`
Retrieves a paginated list of active services.

**Query Parameters:**
- `search` (string, optional): Keyword to match in service name or description.
- `category_id` (integer, optional): Filter by service category.
- `type_id` (integer, optional): Filter by service type.
- `page` (integer, optional): Page number.

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Issuance of Construction Permit",
      "category": {
        "id": 2,
        "name": "Land & Housing"
      },
      "type": {
        "id": 3,
        "name": "License Issuance"
      },
      "fee": 500000,
      "processing_time_days": 15
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "total": 65
  }
}
```

## `GET /api/v1/services/{id}`
Retrieves details of a specific active service.

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "name": "Issuance of Construction Permit",
    "description": "Permit required for building new residential properties.",
    "required_documents": [
      "ID Card copy",
      "Land Ownership Certificate",
      "Construction Drawings"
    ],
    "fee": 500000,
    "processing_time_days": 15,
    "category": {
        "id": 2,
        "name": "Land & Housing"
    },
    "type": {
        "id": 3,
        "name": "License Issuance"
    }
  }
}
```
**Response (404 Not Found):** If service does not exist, is inactive, or soft deleted.
