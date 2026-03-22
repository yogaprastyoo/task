---

name: request-validator
description: Handles HTTP request validation using Laravel Form Request. Use when validating input data for create or update operations before passing to Service layer.
---

# Request Validator Skill

## When To Use

Use this skill when:

* Validating request input for create operations
* Validating request input for update operations
* Ensuring required fields are present
* Validating data types and formats
* Validating foreign keys existence

---

## Core Concept

Validation is handled using Laravel Form Request classes.

Each request:

* Validates input structure
* Returns validated data
* Does NOT contain business logic

---

## Naming Convention

* Store{Feature}Request → for create
* Update{Feature}Request → for update

Examples:

* StoreWorkspaceRequest
* UpdateWorkspaceRequest
* StoreTaskRequest
* UpdateTaskRequest

---

## Folder Structure

* app/Http/Requests/

---

## Example (Best Practice)

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:workspaces,id'],
        ];
    }
}
```

---

## Rules Definition

Validation MUST include:

* required fields
* data types
* max length
* enum values
* foreign key existence

---

## Allowed Responsibilities

Request MAY:

* Validate required fields
* Validate string/integer/boolean
* Validate enums (status, priority)
* Validate foreign key existence (exists rule)

---

## Forbidden Responsibilities

Request MUST NOT:

* Contain business logic
* Validate ownership
* Validate hierarchy (depth, parent-child)
* Access database manually
* Call Service or Repository

---

## Decision Tree

### Creating Data

IF field is required:
→ use 'required'

IF field is optional:
→ use 'nullable'

IF field references another table:
→ use 'exists:table,column'

IF field is enum:
→ use 'in:value1,value2'

---

### Updating Data

→ all fields should be optional

Example:

* 'name' => ['sometimes', 'string']
* 'parent_id' => ['nullable', 'exists:workspaces,id']

---

## Execution Steps

### Create Request

1. Define required fields
2. Define field types
3. Add constraints (max, enum)
4. Add exists validation for foreign keys

---

### Update Request

1. Use 'sometimes' for optional fields
2. Keep same validation rules
3. Do NOT enforce required unless necessary

---

## Common Validation Rules

### Workspace

* name → required|string|max:255
* parent_id → nullable|exists:workspaces,id

---

### Task

* title → required|string|max:255
* workspace_id → required|exists:workspaces,id
* parent_id → nullable|exists:tasks,id
* status → in:todo,in_progress,done
* priority → in:low,medium,high,urgent
* assignee_id → nullable|exists:users,id

---

## Edge Cases

* parent_id exists but invalid hierarchy → handled in Service
* user does not own resource → handled in Service
* cross-workspace relation → handled in Service

---

## Anti-Patterns

### Do NOT validate business rules here

```php
if ($parent->depth > 3)
```

---

### Do NOT check ownership

```php
if ($workspace->owner_id !== auth()->id())
```

---

### Do NOT query manually

```php
Workspace::find($id)
```

---

### Do NOT use Validator inline in controller

```php
Validator::make(...)
```

---

## Integration with Controller

Controller MUST:

* Type-hint Form Request
* Use $request->validated()

Example:

```php
public function store(StoreWorkspaceRequest $request)
{
    $data = $request->validated();

    return $this->service->createWorkspace($data);
}
```

---

## Output

* Returns validated array via $request->validated()

---

## Constraints

* Validation only for structure, not logic
* No database logic beyond 'exists'
* No business rule enforcement
* No cross-layer responsibility
