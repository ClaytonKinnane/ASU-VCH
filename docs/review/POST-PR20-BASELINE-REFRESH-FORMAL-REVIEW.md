# Formal Review — Post-PR20 Baseline Refresh

## Current status

```text
DATE: 2026-08-01
ARCHITECTURE: APPROVED
SPECIFICATION: 0.2 APPROVED
INITIAL_PRE_IMPLEMENTATION_REVIEW: PASS
PR: #21 OPEN
FINAL_PR_REVIEW_ATTEMPT_1: CHANGES REQUIRED
REMEDIATION_APPROVAL: GRANTED
REMEDIATION_STATUS: IMPLEMENTED
REPEAT_DOCUMENTATION_VALIDATION: REQUIRED
REPEAT_FINAL_PR_REVIEW: REQUIRED
MERGE: NOT AUTHORIZED
BRANCH_DELETION: NOT AUTHORIZED
```

## Initial pre-implementation review

Initial Architecture and Specification correctly established a documentation-only refresh, dynamic `origin/main` pointer, historical merge/test anchors, migrations 001–011, 4 roles, 25 permissions, 3 themes and separation of living documentation from historical evidence.

Initial verdict:

```text
BLOCKING_FINDINGS: 0
MAJOR_FINDINGS: 0
MINOR_FINDINGS: 0
VERDICT: PASS
```

## Final PR Review attempt 1 — PR #21

Review выполнялся на PR head:

```text
060ba1e71d8791dac0a85fd9dd257d9b2cf21cfe
```

### Blocking finding 1 — incomplete PR #19 operational closure

Initial allowlist из 22 путей включал operational closure PR #20, но не включал три current operational record PR #19:

```text
docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md
docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-LOCAL-RUNBOOK.md
docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md
```

Implementation PR #19 продолжал показывать `PR NOT CREATED / MERGE NOT AUTHORIZED`, а increment runbook оставался pre-merge operational instruction без current stable closure.

### Blocking finding 2 — stale post-PR current markers

После создания PR #21 несколько current-state документов продолжали утверждать `PR not created` либо описывали Implementation/Validation как будущие:

- `docs/PROJECT-STATUS.md`;
- `docs/ROADMAP.md`;
- `docs/README.md`;
- `docs/CHANGELOG.md`;
- `docs/LOCAL-RUNBOOK.md`;
- current refresh Implementation/Validation records.

### Minor finding — implementation head

Implementation record не содержал фактический implementation/PR head и не отделял его от последующих evidence-only commits.

### Attempt 1 verdict

```text
BLOCKING_FINDINGS: 2
MAJOR_FINDINGS: 0
MINOR_FINDINGS: 1
VERDICT: CHANGES REQUIRED
REVIEW_ID: 4835099195
```

## Remediation approval

Владелец отдельно разрешил:

- расширить allowlist с 22 до 25 Markdown-путей;
- добавить три operational records PR #19;
- синхронизировать current-state документы с PR #21;
- обновить process records и PR body;
- провести повторную Documentation Validation и Final PR Review.

Merge и branch deletion не разрешены.

## Remediation acceptance criteria

- exact changed paths: 25;
- Markdown-only diff;
- PR #19 и PR #20 operational closure complete;
- PR #21 current markers synchronized;
- implementation head recorded without self-reference;
- repeat Documentation Validation PASS;
- PR open, non-draft, mergeable, not merged;
- runtime/config/database/migrations/themes/tools/Git refs unchanged;
- branch deletion not performed;
- Mobile PASS not claimed.

## Gate

После remediation требуется повторный Final PR Review на точном актуальном head. Merge допускается только после его PASS и отдельного owner approval.
