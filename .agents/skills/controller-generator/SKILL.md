---

name: controller-generator
description: Generates Laravel Controllers that handle HTTP requests, delegate logic to Service layer, and return standardized JSON responses. Use when creating API endpoints.
---

# Controller Generator Skill

## When To Use

Use this skill when:

* Creating API endpoints
* Handling HTTP request/response
* Connecting routes to Service layer
* Returning standardized JSON responses

---

## Core Concepts

### Controller Layer

Controller is responsible for:

* Receiving HTTP request
* Validating input via Form Request
* Calling Service methods
* Returning JSON response

---

### Separation of Responsibility

* Controller → HTTP handling
* Service → Business logic
* Repository → Database access

---

## Core Rules

* Controller MUST call Service only
* Controller MUST NOT contain business logic
* Controller MUST NOT access database directly
* Controller MUST NOT use Model directly
* Controller MUST use Form Request for validation

---

## Decision Tree

### Handling Request

IF request has input data:
→ use Form Request

→ call Service with validated data

---

### Returning Response

IF operation successful:
→ return standardized JSON

IF error occurs:
→ let exception bubble up (handled globally)

---

## Execution Steps

### Create Endpoint

1. Receive request (Form Request)
2. Get validated data
3. Call Service method
4. Return JSON response

---

### Update Endpoint

1. Receive request
2. Get validated data
3. Call Service
4. Return JSON response

---

### Delete Endpoint

1. Receive ID
2. Call Service
3. Return JSON response

---

### Get Endpoint

1. Receive ID or query params
2. Call Service
3. Return JSON response

---

## Method Design

Controller methods MUST follow REST pattern:

* index()
* store()
* show()
* update()
* destroy()

---

## Response Format

All responses MUST follow:

```json
{
  "success": true|false,
  "data": any,
  "message": string
}
```

---

## Example (Best Practice)

```php
namespace App\Http\Controllers;

use App\Services\WorkspaceService;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use Illuminate\Http\JsonResponse;

class WorkspaceController extends Controller
{
    public function __construct(
        protected WorkspaceService $service
    ) {}

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->service->createWorkspace(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $workspace,
            'message' => 'Workspace created successfully'
        ]);
    }

    public function update(int $id, UpdateWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->service->updateWorkspace(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $workspace,
            'message' => 'Workspace updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteWorkspace($id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Workspace deleted successfully'
        ]);
    }
}
```

---

## Constraints

* No business logic
* No database query
* No validation logic inline
* No direct Model usage

---

## Anti-Patterns

### Business Logic in Controller

```php
if ($task->status === 'done')
```

---

### Direct DB Access

```php
Task::create(...)
```

---

### Manual Validation

```php
Validator::make(...)
```

---

### Calling Repository

```php
$this->repository->create(...)
```

---

## Integration Flow

Controller → Service → Repository → Database

---

## Expected Behavior

* Controller remains thin and predictable
* All logic centralized in Service
* All responses consistent
