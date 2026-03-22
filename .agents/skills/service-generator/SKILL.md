---

name: service-generator
description: Generates Laravel Service classes that handle all business logic, enforce domain rules, and coordinate repositories. Use when implementing application use-cases such as creating, updating, or deleting resources.
---

# Service Generator Skill

## When To Use

Use this skill when:

* Implementing business logic for any feature
* Coordinating multiple repository operations
* Enforcing domain rules (workspace-domain, task-domain)
* Handling use-case logic (create, update, delete, assign, etc.)

---

## Core Concepts

### Service Layer

Service is responsible for:

* Handling ALL business logic
* Enforcing domain rules
* Coordinating Repository operations
* Managing transactions (if needed)

---

### Separation of Responsibility

* Controller → HTTP layer
* Service → Business logic
* Repository → Database access

---

## Core Rules

* ALL business logic MUST be inside Service
* Service MUST call Repository for DB operations
* Service MUST enforce domain rules
* Service MUST NOT access HTTP request directly
* Service MUST NOT return JSON response

---

## Decision Tree

### Creating Data

→ Receive validated data from Controller

→ Apply domain rules
(e.g. workspace-domain, task-domain)

IF validation fails:
→ THROW exception

→ Call repository to create data

---

### Updating Data

→ Find resource using repository

→ Validate ownership / domain constraints

IF invalid:
→ THROW exception

→ Apply updates via repository

---

### Deleting Data

→ Find resource

→ Validate ownership

IF invalid:
→ THROW exception

→ Delete via repository

---

### Complex Operations

IF operation involves multiple steps:
→ Use DB transaction

---

## Execution Steps

### Create Resource

1. Receive validated data
2. Apply domain rules
3. Call repository create()
4. Return model

---

### Update Resource

1. Find model (repository)
2. Validate domain rules
3. Apply update
4. Return updated model

---

### Delete Resource

1. Find model
2. Validate ownership
3. Delete model
4. Return result

---

### Complex Use-case

1. Start DB transaction
2. Execute multiple operations
3. Commit transaction
4. Return result

---

## Method Design

Methods MUST be use-case based:

### Good

* createWorkspace()
* updateTaskStatus()
* assignTask()
* createSubTask()

---

### Bad

* create()
* update()
* process()

---

## Domain Integration

Service MUST use domain skills:

### Workspace

* Validate max depth
* Validate ownership
* Validate parent-child

---

### Task

* Validate sub-task depth
* Validate workspace binding
* Validate parent

---

## Constraints

* No business logic in Controller
* No DB query in Service
* No Request object usage
* No JSON response

---

## Anti-Patterns

### Logic in Controller

```php
if ($workspace->owner_id !== $userId)
```

---

### Direct Model Access

```php
Workspace::create(...)
```

---

### Generic Service Method

```php
public function create(array $data)
```

---

### Skipping Domain Rules

```php
// save directly without in-depth validation
```

---

## Example (Best Practice)

```php
namespace App\Services;

use App\Models\Workspace;
use App\Repositories\WorkspaceRepository;
use Illuminate\Support\Facades\DB;

class WorkspaceService
{
    public function __construct(
        protected WorkspaceRepository $repository
    ) {}

    public function createWorkspace(array $data): Workspace
    {
        return DB::transaction(function () use ($data) {

            $this->validateParent($data);

            return $this->repository->create($data);
        });
    }

    private function validateParent(array $data): void
    {
        // follow workspace-domain rules
    }
}
```

---

## Output

Service MUST return:

* Eloquent Model
* Collection
* Boolean

---

## Integration Flow

Controller → Service → Repository → Database

---

## Expected Behavior

* All business rules are enforced
* No invalid data passes through
* Logic is centralized and reusable
