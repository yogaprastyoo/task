---

name: commit-standard
description: Standardizes git commit messages using a consistent format. Use when committing code changes to ensure clarity, traceability, and integration with GitHub issues.
---

# Commit Standard Skill

## Goal

Ensure all commits are:

* Clear
* Consistent
* Linked to GitHub issues
* Easy to understand

---

## When To Use

Use this skill when:

* Creating a new commit
* Finishing a feature
* Fixing a bug
* Refactoring code

---

## Core Format

All commits MUST follow:

```bash
<type>: <short description> (#issue-number)
```

---

## Types

### Feature

```bash
feat: add workspace creation API (#12)
```

---

### Fix

```bash
fix: correct task parent validation (#15)
```

---

### Refactor

```bash
refactor: simplify workspace service logic (#18)
```

---

### Test

```bash
test: add workspace service tests (#20)
```

---

### Chore

```bash
chore: update dependencies (#25)
```

---

## Decision Tree

### New Feature

→ use `feat`

---

### Bug Fix

→ use `fix`

---

### Code Improvement (no behavior change)

→ use `refactor`

---

### Adding Tests

→ use `test`

---

### Maintenance

→ use `chore`

---

## Execution Steps

### Create Commit

1. Identify change type
2. Write clear description (what was done)
3. Attach issue number
4. Commit using standard format

---

## Description Rules

* MUST be short and clear
* MUST describe WHAT was done
* MUST NOT be vague

---

### Good

```bash
feat: implement workspace creation endpoint (#12)
```

---

### Bad

```bash
update code
fix bug
done
```

---

## Constraints

* Must include issue number
* Must use defined type
* Must be one logical change per commit
* Must not be vague

---

## Anti-Patterns

### Missing Issue Link

```bash
feat: add workspace API
```

---

### Too Vague

```bash
fix: bug
```

---

### Multiple Changes

```bash
feat: add workspace and fix task and update UI
```

---

## Expected Behavior

* All commits are consistent
* Easy to trace back to issue
* Clean git history
