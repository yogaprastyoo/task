---
name: workspace-domain
description: Enforces workspace business rules including hierarchy (max depth 3), ownership validation, circular dependency prevention, and parent-child constraints. Use when handling any workspace-related operations such as create, update, move, or delete.
---

# Workspace Domain Skill

## When To Use

Use this skill when:
* Creating a workspace
* Updating workspace (rename, change parent)
* Moving workspace to another parent
* Deleting workspace
* Validating workspace hierarchy and preventing circular dependencies
* Checking workspace ownership and ensuring unique naming

---

## Core Concepts

### Workspace

Workspace is a hierarchical container with:
* `name` (required, unique per level per owner)
* `owner_id` (required)
* `parent_id` (nullable - null means root level)
* `depth` (1 to 3, root is always 1)

---

### Hierarchy Structure

Level 1 → Root (`parent_id` is null, `depth` = 1)
Level 2 → Child (`depth` = 2)
Level 3 → Sub-child (`depth` = 3)

Maximum depth = 3

---

### Ownership & Integrity

* Each workspace belongs to one user (`owner_id`).
* Only owner can update or delete.
* Siblings (workspaces with same `owner_id` and `parent_id`) MUST have unique names.

---

## Core Rules

1. Workspace MUST have an `owner_id`.
2. Workspace MUST have a uniquely named `name` under the same `parent_id` and `owner_id`.
3. Root workspace (`parent_id` is NULL) MUST always have `depth` = 1.
4. Parent (if exists) MUST belong to the same owner (strict validation).
5. Depth MUST NOT exceed 3.
6. NO Circular Hierarchy: A workspace MUST NOT be moved to an existing child or any of its descendants.
7. Subtree Depth Update: When a workspace is moved, the `depth` of all its descendants MUST be updated to reflect the new hierarchy.
8. Only owner can update/delete workspace.
9. No Orphan Data: Delete MUST cascade to all children and associated tasks.

---

## Decision Tree

### Create Workspace

IF `parent_id` is NULL:
→ `depth` = 1

ELSE:
→ find parent in DB
IF parent NOT found:
→ THROW error (404/422)
IF parent.owner_id != owner_id:
→ REJECT (cross-user parent not allowed)
IF parent.depth >= 3:
→ REJECT (max depth reached)
→ `depth` = parent.depth + 1

IF existing workspace found with same `owner_id`, `parent_id`, and `name`:
→ REJECT (Duplicate name in level)

---

### Update Workspace (Rename)

→ find workspace
IF workspace.owner_id != user_id:
→ REJECT

IF updating `name`:
IF existing workspace found with same `owner_id`, `parent_id` (current), and new `name`:
→ REJECT (Duplicate name in level)

---

### Move Workspace (Change Parent)

→ find workspace
IF workspace.owner_id != user_id:
→ REJECT

IF changing `parent_id`:
IF new `parent_id` is current workspace ID or any of its descendants:
→ REJECT (Circular Hierarchy Error)

IF `parent_id` is NULL:
→ new_depth = 1
ELSE:
→ find parent in DB
IF parent NOT found:
→ ERROR
IF parent.owner_id != user_id:
→ REJECT
→ new_depth = parent.depth + 1

IF (new_depth + max_descendant_depth_relative_to_current) > 3:
→ REJECT (Moving this subtree exceeds max depth 3)

IF existing workspace found with same `owner_id`, new `parent_id`, and `name`:
→ REJECT (Duplicate name in level)

→ update workspace `parent_id` and `depth` = new_depth
→ recursively update `depth` of all descendants (subtree)

---

### Delete Workspace

→ find workspace
IF workspace.owner_id != user_id:
→ REJECT

→ delete workspace
→ cascade delete all descendants (children, sub-children) and all tasks within them (handled by DB Foreign Keys or Application-level cascade to ensure no orphans).

---

## Execution Steps

### Create Workspace

1. Validate `owner_id` exists.
2. Validate `name` is unique for this `owner_id` and `parent_id`.
3. If `parent_id` is null -> set `depth` = 1.
4. If `parent_id` exists -> find parent, strictly validate parent belongs to `owner_id`, calculate `depth`.
5. Validate `depth` ≤ 3.
6. Save workspace.

---

### Update Workspace (Rename)

1. Find workspace by ID.
2. Validate ownership.
3. Validate new `name` uniqueness against `owner_id` and current `parent_id`.
4. Save changes.

---

### Move Workspace

1. Find workspace by ID.
2. Validate ownership.
3. Validate circular hierarchy (new parent CANNOT be self or descendant).
4. If new parent exits, validate it belongs to the same owner.
5. Calculate new depth.
6. Check if placing the entire subtree under the new parent exceeds max depth 3.
7. Validate name uniqueness under the new parent.
8. Save changes to current workspace.
9. Recursively update the depth of all descendant workspaces.

---

### Delete Workspace

1. Find workspace by ID.
2. Validate ownership.
3. Delete workspace.
4. Ensure cascade delete removes all child workspaces and related entities (no orphans).

---

## Edge Cases

* Moving a workspace with children to a level 2 parent might cause its children to become level 4 → REJECTED.
* Moving workspace into its own child → REJECTED.
* Naming two root workspaces the same for the same user → REJECTED.
* Naming two root workspaces the same for different users → ALLOWED.

---

## Constraints

* Max depth = 3.
* Unique `(owner_id, parent_id, name)` combination.
* Strict Ownership Check: `owner_id` match for both subject and target parent.
* Strict Circular Dependency Check.
* Consistent Root Depth: Parent NULL exactly implies Depth 1.

---

## Anti-Patterns

### Do NOT do this in Controller

```php
if ($parent->depth > 3) {
    return error(); // Should be in Service/Domain Layer
}
```

### Do NOT allow duplicate names in same scope

```php
// User creating "Projects" twice in the root level without validation
```

### Do NOT forget subtree when moving

```php
// Updating the parent's ID but leaving children's depth outdated
```

---

## Service Integration

Service MUST implement:
* `createWorkspace(data)`
* `renameWorkspace(id, name)`
* `moveWorkspace(id, new_parent_id)`
* `deleteWorkspace(id)`

Service MUST enforce all above rules and handle subtree depth updates transactionally.

---

## Repository Expectation

Repository MUST support:
* `findOrFail()`
* `create()`
* `update()`
* `delete()`
* `findByOwnerAndParent()`: for unique name checking.
* `getDescendants(id)`: for circular dependency check and subtree depth update.

---

## Output Expectation

After operation:
* Workspace has correct `depth` and `parent_id`.
* All descendants have accurate `depth` updated.
* No duplicate names exist under the same parent for the same user.
* No invalid or circular hierarchy exists.
* No orphan records remain after deletion.
