---

name: issue-generator
description: Generates structured GitHub issues from user requests. Use when creating a new feature, bug report, or refactor task before implementation.
---

# Issue Generator Skill

## Goal

Convert user request into a clear, structured GitHub issue that can be directly implemented.

---

## When To Use

Use this skill when:

* User requests a new feature
* User asks to build an API/module
* User wants to fix a bug
* User wants to refactor code

---

## Core Concept

An issue MUST:

* Be clear and actionable
* Define what needs to be built
* Include acceptance criteria
* Be implementable without additional clarification

---

## Decision Tree

### Feature Request

→ Generate feature issue

---

### Bug Fix

→ Generate bug issue with reproduction steps

---

### Refactor

→ Generate improvement issue

---

## Issue Structure

Each issue MUST include:

### 1. Title

Format:

```text
[Feature] Create Workspace API
```

---

### 2. Description

Explain:

* What feature is
* Why it is needed
* Context (if any)

---

### 3. Scope

Define what is INCLUDED and EXCLUDED

---

### 4. Acceptance Criteria

Checklist of requirements

Example:

```md
- [ ] Create workspace endpoint
- [ ] Validate parent_id
- [ ] Enforce max depth = 3
- [ ] Assign owner_id from authenticated user
- [ ] Return standardized API response
```

---

### 5. Technical Notes

(Optional but recommended)

Include:

* Service methods to create
* Repository methods needed
* Domain rules to enforce

---

### 6. Definition of Done

Clear condition when task is complete

Example:

```md
- Feature works via API
- All validation applied
- Code follows architecture rules
- No business logic in controller
```

---

## Execution Steps

### Generate Issue

1. Understand user request
2. Identify feature type (feature/bug/refactor)
3. Break into actionable tasks
4. Add acceptance criteria
5. Add technical hints (based on skills)
6. Output issue.md

---

## Example Output

```md
## Title
[Feature] Create Workspace API

## Description
Implement API to create workspace with optional parent-child hierarchy.

## Scope
- Create workspace
- Support parent_id
- Enforce max depth

## Acceptance Criteria
- [ ] Endpoint POST /workspaces
- [ ] Validate name and parent_id
- [ ] Enforce max depth = 3
- [ ] Assign owner_id from auth user
- [ ] Return standard API response

## Technical Notes
- Use WorkspaceService::createWorkspace()
- Use WorkspaceRepository::create()
- Apply workspace-domain rules

## Definition of Done
- API works correctly
- Validation applied
- Response consistent
```

---

## Constraints

* Do not create vague issues
* Do not skip acceptance criteria
* Do not create overly large scope
* Do not mix multiple features in one issue

---

## Anti-Patterns

### Too vague

```md
buat workspace
```

---

### No criteria

```md
implement API
```

---

### No structure

```md
random text tanpa format
```

---

## Expected Behavior

* Every issue is structured
* Every issue is actionable
* AI can implement without confusion
