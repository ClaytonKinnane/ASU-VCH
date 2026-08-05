# Documentation Current-State Reconciliation v3 — Specification

## Status and baseline

```text
DOCUMENT_TYPE=SPECIFICATION
CLASSIFICATION=DOCUMENTATION_ONLY
BASE_BRANCH=main
BASE_SHA=35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
BRANCH=docs/documentation-current-state-reconciliation-v3
ARCHITECTURE_STATUS=APPROVED
SPECIFICATION_STATUS=APPROVED
VALIDATION_MODEL_REMEDIATION_F1=APPROVED
```

## Exact path contract

Разрешены ровно 16 Markdown-путей: 10 living-файлов и 6 process records, перечисленные в Architecture и Approval. Другие пути, включая Final PR Review repository-файл, запрещены.

## Content contract

Living documentation должна использовать разделённую baseline-модель:

```text
latest functional runtime baseline: PR #24 / migration 012
static CI baseline: PR #25
documentation governance baseline: PR #28
local automation foundation: PR #29
local automation corrected baseline: PR #30
durable technical capability coverage: through PR #30
```

Устаревшее двусмысленное поле `latest technical PR: #25` удаляется.

Обязательные durable outcomes:

- PR #28: terminal documentation model;
- PR #29: local Git/GitHub/Codex automation foundation;
- PR #30: PowerShell 5.1 first-run correction, native-process hardening and regression harness.

Functional schema, roles, permissions, themes и application behavior не изменяются.

## Tooling boundary

Документация должна различать:

```text
application runtime
GitHub-hosted static CI
local Windows repository tooling
```

Browser ChatGPT не получает прямого local-machine access.

Historical PR #30 native evidence:

```text
Windows PowerShell 5.1
58 PASS / 0 FAIL
exact-head workflow run 31024419654 / SUCCESS
post-merge push run 31025264683 / SUCCESS
```

Не заявляются real GitHub/Codex authentication, account verification, paid API request или complete target-machine installation acceptance.

## Anti-recursion contract

Living Markdown не содержит lifecycle текущего reconciliation PR. GitHub является canonical source. Recursive post-merge closure solely for lifecycle copying запрещён.

Historical gate records предыдущих increments не изменяются:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

## Immutable Validation contract

Repository-файл `docs/testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-VALIDATION.md` является immutable validation contract, а не журналом результатов.

Он обязан:

- фиксировать approved checks, boundaries и terminal invariants;
- использовать `VALIDATION_CONTRACT_STATUS=APPROVED`;
- не содержать обещания последующего внесения результатов в repository tree;
- не требовать commit после выполнения Validation.

Фактические результаты Documentation Validation после отдельного разрешения на Pull Request фиксируются только в GitHub Pull Request body или comment. После успешной Validation изменение repository-файлов запрещено.

```text
REPOSITORY_VALIDATION_CONTRACT=IMMUTABLE
VALIDATION_RESULT_LOCATION=GITHUB_PR_BODY_OR_COMMENT
VALIDATION_RESULT_COMMIT=PROHIBITED
VALIDATION_CLOSURE_PR=PROHIBITED
```

## Validation contract

```text
expected paths: 16
actual paths: 16
Markdown-only: yes
unexpected paths: 0
missing paths: 0
git diff --check: PASS
relative links: PASS
stale baseline scan: PASS
anti-recursion scan: PASS
secret review: PASS
testing claim review: PASS
runtime/workflow/tool diff: 0
```
