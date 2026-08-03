# Terminal Documentation Consistency — Architecture

## 1. Record classification

```text
stage: Architecture
classification: historical design gate record
project: ASU-VCH
baseline: main @ e1cc402d697cf1d941bf7dff0781b4c11b3786dd
branch: docs/terminal-documentation-consistency
date: 2026-08-04
implementation authorized by this record: NO
```

This document records the architecture decision at the Architecture gate. It is not a living project-status source and is not updated merely because later gates complete.

## 2. Problem statement

The previous documentation-closure model attempted to copy the complete lifecycle of the most recent documentation Pull Request back into repository Markdown:

- exact PR head;
- Final PR Review result;
- merge commit;
- post-merge workflow run;
- branch deletion.

Those facts become available only after the documentation change is already merged. Recording them through another documentation Pull Request creates a new lifecycle that is itself not yet recorded, producing an unbounded recursive closure chain.

The recursion is a documentation-model defect, not a functional project defect.

## 3. Architecture objective

Adopt a terminal documentation model in which:

1. living documentation contains only durable current project facts;
2. transient repository lifecycle facts are queried dynamically from GitHub/Git;
3. process records are immutable historical gate snapshots;
4. historical `PENDING`, `NEXT GATE`, `NOT AUTHORIZED` and similar statements are not interpreted as current open tasks when temporally scoped to their gate;
5. the lifecycle of the terminal documentation Pull Request is not copied back into living Markdown;
6. merging the terminal increment does not create another documentation-closure increment.

## 4. Sources of truth

### 4.1 Living project state

Repository living documentation remains the source for durable merged facts such as:

- implemented functional capabilities;
- current database/migration inventory;
- roles and permissions;
- theme inventory;
- current development rules;
- approved active work, when one exists.

### 4.2 Runtime and schema state

Executable application code, migrations, installer and profile checkers remain authoritative over prose when determining actual runtime or physical schema state.

### 4.3 Repository lifecycle state

GitHub/Git is canonical for mutable or event-derived lifecycle facts:

- current `main` SHA;
- open/closed/merged Pull Requests;
- current PR head and base;
- review submissions and review threads;
- Actions runs and job logs;
- current branch inventory;
- branch deletion timeline events.

These facts must be queried dynamically and must not be duplicated into living documentation as required current-state fields.

## 5. Documentation classes

### 5.1 Living documentation

Living documentation must describe the current durable merged baseline and must be maintained when that baseline changes.

For this increment the living targets are:

1. `docs/README.md` — documentation index and classification guidance;
2. `docs/CHANGELOG.md` — durable content history, not the live lifecycle tracker of the newest documentation PR;
3. `docs/DEVELOPMENT.md` — normative development and documentation-governance rules.

### 5.2 Historical gate records

The following document classes are historical snapshots of their creation gate:

- Architecture;
- Specification;
- Formal Review;
- Approval;
- Implementation;
- Validation/Test Report;
- Final PR Review.

A historical record may correctly state that a later action was pending or unauthorized at that time. Such a statement remains valid historical evidence after the action later occurs.

### 5.3 GitHub lifecycle evidence

The latest documentation PR lifecycle is retained in:

- PR Conversation timeline;
- review submissions;
- review threads;
- Actions runs and logs;
- merge metadata;
- branch deletion event.

No post-merge Markdown replication is required.

## 6. Semantic interpretation rule

The audit rule is:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

A statement is an open current task only when all of the following are true:

1. it appears in a living/current-status context;
2. it is not explicitly scoped to a historical gate or dated snapshot;
3. it remains actionable under the current approved project state;
4. it has not been superseded by a later canonical source.

Directory placement alone does not determine semantics. Explicit classification and section context take precedence.

## 7. Terminal invariant

The terminal increment is complete when its merged content satisfies all of these properties:

```text
LIVING_DOC_SELF_REFERENCE_TO_TERMINAL_PR=0
LIVING_DOC_FUTURE_MERGE_ASSERTIONS_FOR_TERMINAL_PR=0
LIVING_DOC_FUTURE_BRANCH_DELETION_ASSERTIONS_FOR_TERMINAL_PR=0
HISTORICAL_GATE_RECORD_REWRITE_REQUIREMENT=0
GITHUB_LIFECYCLE_SOURCE=CANONICAL
POST_MERGE_MARKDOWN_CLOSURE_REQUIRED=NO
```

The Architecture, Specification, Formal Review, Approval, Implementation, Validation and Final PR Review records of this terminal increment remain historical after merge. Their gate-local pending statements do not create a new current task.

## 8. Planned living corrections

### 8.1 `docs/README.md`

Planned changes:

- remove the current-looking statement that the PR #27 Final PR Review record will be created in the future;
- link the existing PR #27 Final PR Review record;
- state that latest documentation-PR lifecycle evidence is canonical in GitHub;
- state explicitly that historical gate pending markers are not current tasks;
- avoid recording the lifecycle of this terminal increment.

### 8.2 `docs/CHANGELOG.md`

Planned changes:

- replace the stale statement that PR #27 Pull Request, Final PR Review, merge and branch deletion are future gates;
- describe the durable content outcome of the PR #27 closure;
- point lifecycle verification to GitHub rather than requiring another Markdown closure;
- do not add a self-referential lifecycle entry for the terminal increment.

### 8.3 `docs/DEVELOPMENT.md`

Planned changes:

- add normative terminal documentation model rules;
- define living versus historical gate records;
- define GitHub/Git as the source for mutable lifecycle state;
- prohibit audits from treating temporally scoped historical pending markers as open tasks;
- prohibit recursive post-merge documentation closure unless a real durable living-state defect exists.

## 9. Historical records excluded from corrective editing

The following existing records are intentionally not rewritten to reflect later gates:

- `docs/implementation/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-IMPLEMENTATION.md`;
- `docs/testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-VALIDATION.md`;
- `docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-PR-FINAL-REVIEW.md`.

Their pending/authorization statements are historical facts of their respective gates.

## 10. Scope isolation

The increment is documentation-only.

Prohibited changes:

```text
application runtime
PHP behavior
configuration
DB code or data
migrations
GitHub Actions workflow or Action SHA
themes and assets
deploy scripts
tools/checkers
branch protection
required checks
repository settings
non-Markdown files
```

No runtime, database, deploy, browser, visual or mobile PASS claim may be introduced.

## 11. Exact proposed changed-path allowlist

### Living targets

1. `docs/README.md`
2. `docs/CHANGELOG.md`
3. `docs/DEVELOPMENT.md`

### Process records for this increment

4. `docs/architecture/TERMINAL-DOCUMENTATION-CONSISTENCY-ARCHITECTURE.md`
5. `docs/specification/TERMINAL-DOCUMENTATION-CONSISTENCY-SPECIFICATION.md`
6. `docs/review/TERMINAL-DOCUMENTATION-CONSISTENCY-FORMAL-REVIEW.md`
7. `docs/decisions/TERMINAL-DOCUMENTATION-CONSISTENCY-APPROVAL.md`
8. `docs/implementation/TERMINAL-DOCUMENTATION-CONSISTENCY-IMPLEMENTATION.md`
9. `docs/testing/TERMINAL-DOCUMENTATION-CONSISTENCY-VALIDATION.md`
10. `docs/review/TERMINAL-DOCUMENTATION-CONSISTENCY-PR-FINAL-REVIEW.md`

```text
FINAL_ALLOWLIST_PATHS=10
MARKDOWN_PATHS=10
NON_MARKDOWN_PATHS=0
EXPECTED_PRE_PR_PATHS_AFTER_VALIDATION=9
FINAL_PR_REVIEW_RESERVED_PATHS=1
```

The 10th path is created only during the actual Final PR Review gate after separate permission to create a Pull Request.

## 12. Validation architecture

Documentation Validation must verify:

- exact baseline and merge-base;
- exact approved path set;
- Markdown-only isolation;
- removal of the two living stale assertions;
- presence of terminal-model rules in `docs/DEVELOPMENT.md`;
- absence of self-reference to the terminal PR lifecycle in living documents;
- preservation of excluded historical records;
- relative-link correctness;
- no secret or real personnel data;
- no unsupported mobile/runtime testing claim;
- zero prohibited-path changes.

Final PR Review must repeat scope and semantic checks on the exact final head.

## 13. Lifecycle after merge

After this increment is merged:

- GitHub records its exact head, review, merge commit, push run and branch cleanup;
- living Markdown does not need another edit merely to copy those events;
- historical process records remain historical;
- a new documentation increment is opened only for a genuine durable project-state change or a real living-document defect.

## 14. Current gate

```text
ARCHITECTURE_STATUS=READY FOR SPECIFICATION AND FORMAL REVIEW
IMPLEMENTATION_AUTHORIZED=NO
PULL_REQUEST_AUTHORIZED=NO
MERGE_AUTHORIZED=NO
BRANCH_DELETION_AUTHORIZED=NO
```
