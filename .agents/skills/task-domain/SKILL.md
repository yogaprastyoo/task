---

name: task-domain
description: Enforces task business rules including workspace binding, sub-task constraints (max depth 1), status management, and assignment logic. Use when creating, updating, assigning, or deleting tasks.
---

# Task Domain Skill

## When To Use

Use this skill when:

* Creating a task
* Creating a sub-task
* Updating a task
* Changing task status
* Assigning task to a user
* Deleting a task
* Validating task hierarchy or workspace binding

---

## Core Concepts

### Task

Task is a unit of work inside a workspace.

Each task has:

* workspace_id (required)
* creator_id (required)
* parent_id (nullable)
* status
* priority
* assignee_id (nullable)

---

### Task Hierarchy (Sub-task)

Structure:

Parent Task
→ Sub-task

Rules:

* Maximum depth = 1 level
* Sub-task CANNOT have child
* Parent MUST NOT be a sub-task

---

### Workspace Binding

* Every task MUST belong to a workspace
* Sub-task MUST belong to the SAME workspace as parent

---

### Assignment

* Task can be assigned (assignee_id)
* Task can be unassigned (null)
* (MVP) No strict validation for membership yet

---

## Core Rules

* Task MUST belong to a workspace
* creator_id is required
* Sub-task depth MUST NOT exceed 1
* Parent and child MUST be in same workspace
* Parent MUST NOT be a sub-task
* Status must be valid enum
* Priority must be valid enum

---

## Decision Tree

### Create Task

IF parent_id is NULL:
→ create normal task

ELSE:
→ find parent

IF parent NOT found:
→ ERROR

IF parent.workspace_id != workspace_id:
→ REJECT (cross-workspace not allowed)

IF parent.parent_id IS NOT NULL:
→ REJECT (parent is already sub-task)

→ create sub-task

---

### Update Task

→ find task

IF updating workspace_id:
→ REJECT (task cannot move workspace in MVP)

IF updating parent_id:

```
IF parent_id is NULL:
→ convert to root task

ELSE:
→ find parent

IF parent NOT found:
→ ERROR

IF parent.workspace_id != task.workspace_id:
→ REJECT

IF parent.parent_id IS NOT NULL:
→ REJECT
```

---

### Delete Task

→ find task

IF task has sub-tasks:
→ delete all sub-tasks (cascade handled by DB)

→ delete task

---

### Assign Task

→ find task

IF assignee_id is NULL:
→ unassign task

ELSE:
→ assign user

(MVP: no workspace membership validation yet)

---

### Change Status

Allowed values:

* todo
* in_progress
* done

→ update status freely

---

## Execution Steps

### Create Task

1. Validate workspace_id exists
2. Validate creator_id exists
3. Check parent_id (if exists)
4. Validate parent rules
5. Save task

---

### Create Sub-task

1. Find parent task
2. Validate parent is not sub-task
3. Validate same workspace
4. Save sub-task

---

### Update Task

1. Find task
2. Apply changes
3. Validate parent (if changed)
4. Save task

---

### Delete Task

1. Find task
2. Delete task
3. Cascade delete handled by DB

---

### Assign Task

1. Find task
2. Set assignee_id (nullable)
3. Save task

---

### Update Status

1. Find task
2. Validate status enum
3. Update status
4. Save task

---

## Edge Cases

* Creating sub-task under sub-task → REJECTED
* Cross-workspace parent → REJECTED
* Assigning null → allowed
* Updating workspace_id → REJECTED
* Deleting parent → sub-task deleted automatically

---

## Constraints

* Max sub-task depth = 1
* Task MUST belong to workspace
* No cross-workspace hierarchy
* Workspace cannot be changed after creation (MVP)

---

## Anti-Patterns

### Do NOT allow nested sub-task

```php
if ($parent->parent_id !== null) {
    // should reject, not allow
}
```

---

### Do NOT allow cross-workspace parent

```php
// parent.workspace_id != task.workspace_id
```

---

### Do NOT validate in Controller

```php
if ($task->workspace_id !== $workspaceId)
```

---

### Do NOT move task between workspace (MVP)

```php
$task->workspace_id = $newWorkspaceId;
```

---

## Service Integration

Service MUST implement:

* createTask()
* updateTask()
* deleteTask()
* updateTaskStatus()
* assignTask()
* createSubTask()

Service MUST:

* enforce all rules above
* call repository for DB operations

---

## Repository Expectation

Repository MUST support:

* create()
* findOrFail()
* update()
* delete()
* findByWorkspace()
* findWithSubTasks()

---

## Output Expectation

After operation:

* Task belongs to correct workspace
* No invalid sub-task hierarchy exists
* Status and priority are valid
* Parent-child relationship is valid
