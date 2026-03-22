---

name: repository-generator
description: Generates Repository classes that handle all database operations using Eloquent. Use when performing data access such as create, read, update, delete, and query operations.
---

# Repository Generator Skill

## When To Use

Use this skill when:

* Accessing database records
* Creating, updating, or deleting data
* Querying data with filters or relations
* Fetching data for Service layer

---

## Core Concepts

### Repository Layer

Repository is responsible for:

* Executing database queries
* Interacting with Eloquent Models
* Returning data to Service layer

---

### Model Binding

Each repository MUST be tied to one Model.

Examples:

* WorkspaceRepository → Workspace
* TaskRepository → Task

---

## Core Rules

* Repository MUST use Eloquent ORM
* Repository MUST NOT contain business logic
* Repository MUST NOT enforce domain rules
* Repository MUST NOT access HTTP request
* Repository MUST NOT return JSON

---

## Decision Tree

### Creating Data

→ Receive clean data from Service

→ Call Model::create()

→ Return Model

---

### Finding Data

IF single record:
→ use findOrFail()

IF multiple records:
→ use where() / get()

IF need relations:
→ use with()

---

### Updating Data

→ Receive Model instance

→ Call update()

→ Return refreshed model

---

### Deleting Data

→ Receive Model instance

→ Call delete()

→ Return boolean

---

## Execution Steps

### Create

1. Receive data
2. Call Model::create()
3. Return model

---

### Find

1. Receive identifier
2. Call findOrFail()
3. Return model

---

### Update

1. Receive model + data
2. Call update()
3. Refresh model
4. Return model

---

### Delete

1. Receive model
2. Call delete()
3. Return result

---

### Query

1. Build query using Eloquent
2. Apply filters
3. Return collection

---

## Method Design

Methods MUST be simple and data-focused

### Good

* create()
* findOrFail()
* update()
* delete()
* findByOwner()
* findByWorkspace()
* findWithRelations()

---

### Bad

* createWithValidation()
* updateIfAllowed()
* deleteIfOwner()

---

## Relationship Handling

Repository SHOULD handle eager loading

Example:

```php id="p3otn6"
public function findWithTasks(int $id)
{
    return Workspace::with('tasks')->findOrFail($id);
}
```

---

## Constraints

* No business logic
* No validation logic
* No domain enforcement
* No HTTP logic

---

## Anti-Patterns

### Business Logic in Repository

```php
if ($workspace->owner_id !== $userId)
```

---

### Calling Service from Repository

```php
$this->service->validate(...)
```

---

### Returning JSON

```php
return response()->json(...)
```

---

### Over-abstracting

```php
interface RepositoryInterface
```

---

## Example (Best Practice)

```php id="0aj93d"
namespace App\Repositories;

use App\Models\Workspace;

class WorkspaceRepository
{
    public function create(array $data): Workspace
    {
        return Workspace::create($data);
    }

    public function findOrFail(int $id): Workspace
    {
        return Workspace::findOrFail($id);
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        $workspace->update($data);
        return $workspace->refresh();
    }

    public function delete(Workspace $workspace): bool
    {
        return $workspace->delete();
    }

    public function findByOwner(int $ownerId)
    {
        return Workspace::where('owner_id', $ownerId)->get();
    }
}
```

---

## Output

Repository MUST return:

* Eloquent Model
* Collection
* Boolean

---

## Integration Flow

Controller → Service → Repository → Database

---

## Expected Behavior

* All queries are centralized
* No logic leaks into repository
* Service fully controls business rules
