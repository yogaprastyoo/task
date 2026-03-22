---

name: github-workflow
description: Manages development workflow using GitHub CLI. Use when creating new features to ensure proper issue creation, branch management, and implementation flow.
---

# GitHub Workflow Skill

## Goal

Standardize development workflow:

Prompt → GitHub Issue → Branch → Implementation

---

## When To Use

Use this skill when:

* Starting a new feature
* Implementing a new module
* Fixing a bug
* Refactoring code

---

## Core Rules

* MUST create GitHub issue first
* MUST create branch per feature
* MUST implement based on issue
* MUST link work to issue

---

## Decision Tree

### New Feature Request

→ Create GitHub issue

→ Create branch from issue

→ Implement feature

---

### Bug Fix

→ Create GitHub issue

→ Create branch

→ Fix bug

---

## Execution Steps

### Step 1: Create Issue

Generate issue content:

* Title
* Description
* Acceptance Criteria
* Technical Notes (optional)

---

### Step 2: Create GitHub Issue

Use GitHub CLI:

```bash id="l9z2qk"
gh issue create --title "..." --body-file issue.md
```

---

Branch name format:

```bash id="a7d4fr"
{prefix}/{issue-number}-{slug}
```

Prefix options:
- `feature/` - for new features or implementations
- `bugfix/` - for resolving bugs or issues

Example (Feature):

```bash id="a8u3sd_feat"
feature/12-create-workspace-api
```

Example (Bugfix):

```bash id="a8u3sd_bug"
bugfix/3-fix-case-insensitive-email
```

---

### Step 4: Checkout Branch

```bash id="xv0w7g"
git checkout -b {prefix}/{issue-number}-{slug}
```

---

### Step 5: Implementation

* Read issue carefully
* Follow acceptance criteria
* Use existing skills:

  * controller-generator
  * service-generator
  * repository-generator
  * domain skills

---

### Step 6: Commit

Commit format:

```bash id="ykz8dd"
feat: implement workspace API (#12)
```

---

## Issue Template

Issue MUST include:

### Title

Clear feature name

### Description

What needs to be built

### Acceptance Criteria

Checklist of requirements

Example:

```md id="z0z1p4"
## Acceptance Criteria

- [ ] Create workspace endpoint
- [ ] Validate parent_id
- [ ] Enforce max depth
- [ ] Return standard API response
```

---

## Constraints

* Do not implement without issue
* Do not skip branch creation
* Do not mix multiple features in one branch
* Do not ignore acceptance criteria

---

## Anti-Patterns

### Direct Implementation

```text id="u1c3q8"
langsung coding tanpa issue
```

---

### No Branch

```text id="z3l9p1"
langsung commit di main
```

---

### Ambiguous Issue

```text id="3q9h2k"
"fix something"
```

---

## Expected Behavior

* Every feature starts from issue
* Every issue has its own branch
* Implementation follows defined requirements
* Codebase remains structured and traceable
