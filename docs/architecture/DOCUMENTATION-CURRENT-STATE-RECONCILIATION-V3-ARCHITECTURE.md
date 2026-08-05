# Documentation Current-State Reconciliation v3 — Architecture

## Status

```text
CLASSIFICATION=DOCUMENTATION_ONLY
BASE_BRANCH=main
BASE_SHA=35abf7e29395bc662eeb2cecf5b1ea5a30fd7c77
LIVING_PATHS=10
PROCESS_PATHS=6
TOTAL_ALLOWLIST=16
ARCHITECTURE_STATUS=APPROVED
```

## Purpose

Однократно синхронизировать durable living-документацию с merged состоянием через PR #30:

```text
functional runtime baseline: PR #24 / migration 012
static CI baseline: PR #25
documentation governance baseline: PR #28
local automation foundation: PR #29
local automation corrected baseline: PR #30
```

Runtime, config, database, migrations, workflow, Action SHA, themes, deploy, automation tools, manifest и repository settings не изменяются.

## Terminal anti-recursion model

Living Markdown не хранит lifecycle текущего reconciliation PR:

```text
PR number
branch head
merge commit
Actions run
branch cleanup
latest documentation PR
post-merge closure placeholder
```

Canonical lifecycle evidence остаётся в GitHub PR, reviews, Actions и branch inventory. Отсутствие Markdown-копии после merge не является defect.

Файл `docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-PR-FINAL-REVIEW.md` запрещён. Final PR Review фиксируется только в GitHub.

## Scope

Living files:

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/ARCHITECTURAL-PATTERNS.md
docs/domains/README.md
```

Process records:

```text
docs/architecture/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-ARCHITECTURE.md
docs/specification/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-SPECIFICATION.md
docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-FORMAL-REVIEW.md
docs/decisions/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-APPROVAL.md
docs/implementation/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-IMPLEMENTATION.md
docs/testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V3-VALIDATION.md
```

## Validation architecture

Required:

```text
exact base and merge-base
behind main = 0
exact 16-path allowlist
Markdown-only diff
relative links
stale baseline scan
anti-recursion scan
historical preservation
secret/testing-claim review
git diff --check
no runtime/workflow/tool diff
```

MySQL, migrations, deploy, HTTP/browser, visual, mobile, real authentication и paid API testing не требуются и не объявляются PASS.
