# Terminal Documentation Consistency — Specification

## 1. Record classification

```text
stage: Specification
classification: historical requirements gate record
project: ASU-VCH
baseline: main @ e1cc402d697cf1d941bf7dff0781b4c11b3786dd
branch: docs/terminal-documentation-consistency
date: 2026-08-04
implementation authorized by this record: NO
```

This Specification is a historical snapshot of the approved requirements proposal at the Specification gate. Later gate completion does not require rewriting this file.

## 2. Purpose

Eliminate the recursive documentation-closure model by correcting the remaining living-document inconsistencies and establishing a permanent terminal documentation policy.

The completed result must not create a requirement to document the lifecycle of its own Pull Request through another Markdown change.

## 3. Baseline facts

At design start:

```text
MAIN=e1cc402d697cf1d941bf7dff0781b4c11b3786dd
PR27_STATE=CLOSED / MERGED
PR27_REVIEWED_HEAD=3119ed0d79a64a324f784333013e696118c6984a
PR27_MERGE_COMMIT=e1cc402d697cf1d941bf7dff0781b4c11b3786dd
PR27_PUSH_RUN=30852688004 / SUCCESS
PR27_POST_MERGE_VERIFICATION=PASS
PR27_BRANCH=DELETED AFTER SEPARATE APPROVAL
OPEN_PULL_REQUESTS=0
OPEN_ISSUES=0
ACTIVE_BRANCHES_BEFORE_THIS_INCREMENT=main only
```

These facts motivate the two living corrections. They are not required to be copied as a complete lifecycle ledger into every living document.

## 4. Normative terminology

### 4.1 Living documentation

A document or section whose semantics claim to describe the current durable merged project state.

### 4.2 Historical gate record

A dated or gate-scoped record of evidence, decisions, permissions or status as observed at a specific stage.

Historical gate records include Architecture, Specification, Formal Review, Approval, Implementation, Validation/Test Report and Final PR Review.

### 4.3 Mutable lifecycle state

Repository state that can change independently of durable project functionality, including PR status, exact current heads, workflow runs and branch inventory.

### 4.4 Terminal documentation model

A model in which living documentation does not require post-merge replication of the latest documentation PR lifecycle, and historical gate records are not rewritten merely because later gates complete.

## 5. Global requirements

### TDC-G-001 — Terminal model

The implementation MUST adopt the terminal documentation model defined by the Architecture.

### TDC-G-002 — No recursive closure

The implementation MUST NOT create a living statement that requires a later Markdown update solely because this terminal PR is reviewed, merged, verified or cleaned up.

### TDC-G-003 — Dynamic lifecycle source

Current PR, SHA, review, workflow-run and branch state MUST be obtained dynamically from GitHub/Git when needed.

### TDC-G-004 — Historical interpretation

Temporally scoped `PENDING`, `NEXT GATE`, `NOT AUTHORIZED`, `NOT PERFORMED` and equivalent statements in historical gate records MUST NOT be classified as current open tasks.

Canonical rule:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

### TDC-G-005 — Genuine defect threshold

A new documentation closure increment MAY be opened only when there is a genuine durable living-state defect, broken normative rule, incorrect link, or other substantive documentation error. Missing replication of the newest documentation PR lifecycle is not such a defect.

### TDC-G-006 — Historical records preserved

Existing PR #27 Implementation, Validation and Final PR Review records MUST NOT be rewritten merely to reflect subsequent PR, merge, verification or branch-deletion events.

### TDC-G-007 — Documentation-only isolation

Only approved Markdown paths may change. Runtime and repository settings are out of scope.

## 6. File requirements

## 6.1 `docs/README.md`

### TDC-R-001 — Existing Final PR Review link

The PR #27 closure section MUST link:

`review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-PR-FINAL-REVIEW.md`

### TDC-R-002 — Remove stale future assertion

The statement that the PR #27 Final PR Review record will be created in the future MUST be removed or replaced with historically correct wording.

### TDC-R-003 — Lifecycle source rule

The document MUST state that mutable lifecycle evidence for the latest documentation PR is canonical in GitHub PR timeline, reviews, Actions and branch inventory.

### TDC-R-004 — Historical pending rule

The document MUST state that gate-scoped historical pending/authorization markers do not represent current open tasks.

### TDC-R-005 — No terminal self-reference

The document MUST NOT contain a current-looking future assertion about the Pull Request, Final PR Review, merge, post-merge run or branch deletion of this terminal increment.

### TDC-R-006 — Stable baseline preservation

Existing durable functional/technical baseline facts MUST remain unchanged unless a verified factual correction is required. No such additional correction is currently approved.

## 6.2 `docs/CHANGELOG.md`

### TDC-C-001 — Remove stale PR #27 future-gate assertion

The statement that PR #27 Pull Request, Final PR Review, merge and branch deletion remain future gates MUST be removed.

### TDC-C-002 — Durable content outcome

The PR #27 closure entry MUST describe the durable documentation content outcome:

- stale PR #26 living-status statements were corrected;
- historical gate facts were preserved;
- documentation-only/runtime isolation was retained;
- terminal classification principles supersede recursive closure expectations.

### TDC-C-003 — Lifecycle evidence boundary

The entry MAY identify PR #27 as completed, but MUST state or imply that detailed mutable lifecycle evidence remains canonical in GitHub rather than requiring another repository closure record.

### TDC-C-004 — No terminal lifecycle ledger

No new changelog section may attempt to predeclare or later require exact review, merge, workflow or branch-cleanup facts for the terminal increment itself.

### TDC-C-005 — Historical chronology preservation

Existing prior dated entries and verified facts MUST remain intact except for the approved stale PR #27 wording.

## 6.3 `docs/DEVELOPMENT.md`

### TDC-D-001 — Documentation classes

The document MUST define at least:

- living documentation;
- historical gate records;
- GitHub lifecycle evidence.

### TDC-D-002 — Terminal invariant

The document MUST prohibit recursive post-merge Markdown closure when the only missing information is lifecycle evidence of the newest documentation PR.

### TDC-D-003 — Audit interpretation

The document MUST require semantic classification before declaring stale documentation or open tasks.

An audit MUST check:

1. document/section class;
2. temporal scope;
3. current source of truth;
4. whether the statement is still actionable;
5. whether a later canonical source supersedes it.

### TDC-D-004 — Dynamic checks

The document MUST identify GitHub/Git as the source for current PRs, branches, SHAs, reviews and Actions runs.

### TDC-D-005 — Historical immutability

The document MUST state that Architecture, Specification, Formal Review, Approval, Implementation, Validation and Final PR Review are not rewritten merely because later gates complete.

### TDC-D-006 — Real living defect exception

The document MUST allow a new documentation increment for a genuine living-state error while excluding mere absence of the newest PR lifecycle copy.

### TDC-D-007 — Existing mandatory process preserved

The mandatory Architecture → Specification → Review → Approval → Implementation → Testing → PR → Final PR Review → Merge → post-merge verification → separately approved cleanup process MUST remain unchanged.

## 7. Historical exclusions

The following paths are explicitly excluded from corrective edits:

```text
docs/implementation/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-IMPLEMENTATION.md
docs/testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-VALIDATION.md
docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-CLOSURE-PR-FINAL-REVIEW.md
```

Their gate-local statements are interpreted historically.

## 8. Exact changed-path allowlist

1. `docs/README.md`
2. `docs/CHANGELOG.md`
3. `docs/DEVELOPMENT.md`
4. `docs/architecture/TERMINAL-DOCUMENTATION-CONSISTENCY-ARCHITECTURE.md`
5. `docs/specification/TERMINAL-DOCUMENTATION-CONSISTENCY-SPECIFICATION.md`
6. `docs/review/TERMINAL-DOCUMENTATION-CONSISTENCY-FORMAL-REVIEW.md`
7. `docs/decisions/TERMINAL-DOCUMENTATION-CONSISTENCY-APPROVAL.md`
8. `docs/implementation/TERMINAL-DOCUMENTATION-CONSISTENCY-IMPLEMENTATION.md`
9. `docs/testing/TERMINAL-DOCUMENTATION-CONSISTENCY-VALIDATION.md`
10. `docs/review/TERMINAL-DOCUMENTATION-CONSISTENCY-PR-FINAL-REVIEW.md`

```text
APPROVED_FINAL_PATH_COUNT_PROPOSED=10
MARKDOWN_PATH_COUNT=10
NON_MARKDOWN_PATH_COUNT=0
EXPECTED_IMPLEMENTATION_PATHS_BEFORE_VALIDATION=8
EXPECTED_PRE_PR_PATHS_AFTER_VALIDATION=9
FINAL_PR_REVIEW_RESERVED_PATHS=1
```

Before owner Approval, only paths 4–6 may exist in the branch.

## 9. Prohibited scope

The implementation MUST NOT change:

```text
application runtime
PHP source
configuration
DB code or data
migrations
workflow files
Action revisions
themes/assets
deploy scripts
tools/checkers
branch protection
required checks
repository settings
non-Markdown files
```

No secret, local configuration, real temporary password, token, private key, session data, or real personnel/unit data may be added.

## 10. Validation requirements

Documentation Validation MUST verify:

### Git/scope

- exact approved baseline;
- merge-base equals approved baseline;
- branch is not behind `main`;
- changed paths match the approved gate subset;
- all changed paths are Markdown;
- prohibited paths count is zero.

### Semantic correctness

- stale README future Final PR Review assertion absent;
- existing PR #27 Final PR Review link present;
- stale CHANGELOG future-gates assertion absent;
- terminal-model rules present in DEVELOPMENT;
- `HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK` rule present;
- no living self-reference to the terminal increment lifecycle;
- excluded historical records unchanged.

### Safety and claims

- relative links resolve;
- no secrets or real personnel data;
- no runtime/database/deploy/browser/visual/mobile PASS claim is introduced;
- mobile remains `OUT OF SCOPE / NOT RUN` where relevant.

## 11. Final PR Review requirements

On the exact final PR head, Final PR Review MUST verify:

- exact `10 / 10` approved Markdown paths;
- no non-Markdown paths;
- successful applicable GitHub Actions run;
- zero unresolved review threads;
- zero blocking/major/minor findings;
- terminal invariant remains satisfied;
- no requirement for a post-merge Markdown closure is introduced.

The review record is historical. The exact-head verdict may be stored in the PR review submission to avoid recursive head mutation.

## 12. Acceptance criteria

The increment passes when:

```text
README_STALE_ASSERTION=0
README_FINAL_REVIEW_LINK=PASS
CHANGELOG_STALE_ASSERTION=0
DEVELOPMENT_TERMINAL_MODEL=PASS
HISTORICAL_RECORDS_REWRITTEN=0
LIVING_TERMINAL_PR_SELF_REFERENCE=0
GITHUB_LIFECYCLE_SOURCE=PASS
RECURSIVE_CLOSURE_REQUIREMENT=0
CHANGED_PATHS=EXACT APPROVED SET FOR CURRENT GATE
NON_MARKDOWN_PATHS=0
BLOCKING_FINDINGS=0
```

## 13. Authorization boundary

```text
SPECIFICATION_STATUS=READY FOR FORMAL REVIEW
IMPLEMENTATION_AUTHORIZED=NO
PULL_REQUEST_AUTHORIZED=NO
MERGE_AUTHORIZED=NO
BRANCH_DELETION_AUTHORIZED=NO
```
