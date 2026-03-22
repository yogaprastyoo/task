---

name: api-response
description: Standardizes all API JSON responses in Laravel. Use when returning success or error responses from controllers to ensure consistent response structure.
---

# API Response Skill

## When To Use

Use this skill when:

* Returning data from controller
* Sending success responses
* Sending error responses
* Formatting API output consistently

---

## Core Concept

All API responses MUST follow a standard format:

```json id="x4sh1g"
{
  "success": true|false,
  "data": any,
  "message": string
}
```

---

## Core Rules

* Controller MUST NOT build response manually
* Response MUST use helper or formatter
* Structure MUST be consistent across all endpoints

---

## Decision Tree

### Success Response

IF operation successful:
→ return success response

---

### Error Response

IF exception occurs:
→ handled globally (Exception Handler)

---

### Empty Data

IF no data:
→ data = null

---

## Execution Steps

### Returning Success

1. Receive result from Service
2. Pass result to response helper
3. Return formatted JSON

---

### Returning Error

1. Throw exception in Service
2. Let global handler format response

---

## Implementation Pattern

### Option A (Helper Function)

Create helper:

```php id="x6zjv5"
function successResponse($data = null, string $message = 'Success')
{
    return response()->json([
        'success' => true,
        'data' => $data,
        'message' => $message
    ]);
}
```

---

### Option B (Response Class)

Create class:

```php id="1n7zt0"
namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ]);
    }
}
```

---

## Example Usage

### Controller

```php id="x6y3i5"
use App\Helpers\ApiResponse;

public function store(StoreWorkspaceRequest $request)
{
    $workspace = $this->service->createWorkspace(
        $request->validated()
    );

    return ApiResponse::success($workspace, 'Workspace created');
}
```

---

## Constraints

* No manual JSON response in controller
* No inconsistent response structure
* No mixing formats across endpoints

---

## Anti-Patterns

### Manual Response

```php id="ylps3g"
return response()->json([
    'data' => $workspace
]);
```

---

### Missing Fields

```php id="lkl9gr"
return [
    'success' => true
];
```

---

### Different Format per Endpoint

```php id="3v12e3"
return ['result' => $data];
```

---

## Expected Behavior

* All responses look identical in structure
* Easy for frontend to consume
* Easy for AI to generate consistently
