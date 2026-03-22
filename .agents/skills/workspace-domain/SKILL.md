---

name: workspace-domain
description: Enforces workspace business rules including hierarchy (max depth 3), ownership validation, and parent-child constraints. Use when handling any workspace-related operations such as create, update, move, or delete.
---

# Workspace Domain Skill

## When To Use

Use this skill when:

* Creating a workspace
* Updating workspace (rename, change parent)
* Moving workspace to another parent
* Deleting workspace
* Validating workspace hierarchy
* Checking workspace ownership

---

## Core Concepts

### Workspace

Workspace is a hierarchical container with:

* owner_id (required)
* parent_id (nullable)
* depth (1 to 3)

---

### Hierarchy Structure

Level 1 → Root
Level 2 → Child
Level 3 → Sub-child

Maximum depth = 3

---

### Ownership

* Each workspace belongs to one user (owner_id)
* Only owner can update or delete

---

## Core Rules

* Workspace MUST have owner_id
* Parent (if exists) MUST belong to same owner
* Depth MUST NOT exceed 3
* Only owner can update/delete workspace
* Parent MUST exist if parent_id is provided

---

## Decision Tree

### Create Workspace

IF parent_id is NULL:
→ depth = 1

ELSE:
→ find parent

IF parent NOT found:
→ THROW error

IF parent.owner_id != owner_id:
→ REJECT (cross-user parent not allowed)

IF parent.depth >= 3:
→ REJECT (max depth reached)

→ depth = parent.depth + 1

---

### Update Workspace

→ find workspace

IF workspace.owner_id != user_id:
→ REJECT

IF updating parent_id:

```
IF parent_id is NULL:
→ depth = 1

ELSE:
→ find parent

IF parent NOT found:
→ ERROR

IF parent.owner_id != user_id:
→ REJECT

IF parent.depth >= 3:
→ REJECT

→ depth = parent.depth + 1
```

---

### Move Workspace

(Same as update parent logic)

Additional rule:

IF moving workspace causes depth > 3:
→ REJECT

---

### Delete Workspace

→ find workspace

IF workspace.owner_id != user_id:
→ REJECT

→ delete workspace
→ cascade delete children and tasks (handled by DB)

---

## Execution Steps

### Create Workspace

1. Validate owner_id exists
2. Check parent_id (optional)
3. Validate parent ownership
4. Calculate depth
5. Validate depth ≤ 3
6. Save workspace

---

### Update Workspace

1. Find workspace
2. Validate ownership
3. If parent changed → validate parent
4. Recalculate depth
5. Save changes

---

### Move Workspace

1. Find workspace
2. Validate ownership
3. Validate new parent
4. Recalculate depth
5. Ensure depth ≤ 3
6. Save changes

---

### Delete Workspace

1. Find workspace
2. Validate ownership
3. Delete workspace
4. Cascade handled by DB

---

## Edge Cases

* Creating workspace at depth 3 → allowed
* Creating child under depth 3 → REJECTED
* Moving workspace to deeper level → REVALIDATE
* Parent_id pointing to another user → REJECTED
* Parent_id not found → ERROR

---

## Constraints

* Max depth = 3
* No cross-user hierarchy
* No orphan parent reference
* No skipping ownership validation

---

## Anti-Patterns

### Do NOT do this in Controller

```php
if ($parent->depth > 3) {
    return error();
}
```

### Do NOT skip ownership validation

```php
// direct update without checking the owner
```

### Do NOT allow cross-user parent

```php
// workspace user A uses parent user B
```

---

## Service Integration

Service MUST implement:

* createWorkspace()
* updateWorkspace()
* deleteWorkspace()
* moveWorkspace()

Service MUST:

* enforce all rules above
* call repository for DB operations

---

## Repository Expectation

Repository MUST support:

* findOrFail()
* create()
* update()
* delete()
* findByOwner()

---

## Output Expectation

After operation:

* Workspace has correct depth
* Workspace belongs to correct owner
* No invalid hierarchy exists
