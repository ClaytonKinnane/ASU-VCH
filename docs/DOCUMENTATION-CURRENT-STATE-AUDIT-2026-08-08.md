# Аудит текущего состояния документации АСУ-ВЧ — 2026-08-08

## 1. Назначение

Документ фиксирует immutable read-only audit состояния документации перед reconciliation 2026-08-08. Он не является living current-state документом и не переписывается после завершения собственного PR lifecycle.

## 2. Canonical live snapshot

```text
repository: ClaytonKinnane/ASU-VCH
main: b3dda6cae88072c1e74c25de28f7023a8d73620d
main tree: 4a0a6da68448be7a53e9532dd8b520607d9c3000
open pull requests: 0
open issues: 0
remote branches: 2
  main @ b3dda6cae88072c1e74c25de28f7023a8d73620d
  research/military-accounting-order-700 @ 69bf9c9e1609a40c7f4c27ff41b0ddeebabe2ffe
latest main static run: 31234849967 / SUCCESS
```

Current `main` has the same repository tree as PR #36 merge commit `a6cfceb421fac8d0985e409770bb26a62fac0b14`; later history-only noop/revert commits restored the exact tree. No history rewrite is part of this reconciliation.

## 3. Durable functional baseline

```text
latest merged functional increment: PR #36 / Military Positions Directory v1
PR #35: Lowest Unit Staffing Structure v1
migrations on main: 001–014
system roles: 4
system permissions: 35
built-in themes: 3
required CSS assets per theme: 10
active implementation increment: none
mobile: NOT RUN / OUT OF SCOPE
production deployment: NOT PERFORMED
```

PR #36 runtime validation was performed on exact runtime head `c647a933011873048866c75978d3f506634011fd`: `167 PASS`, PHP lint 171 files, migrations 001–014, repeat initialization, HTTP `200/200/302`, three managed desktop themes PASS, mutual exclusion PASS, UI-F04/UI-F05 closed, open findings 0, real Staffing data mutation NONE.

## 4. Documentation classification method

Audit uses semantic classification rather than directory-only classification:

1. living/current-state documents and indexes must match durable merged state;
2. target architecture may intentionally describe future state and is not rewritten merely because runtime differs;
3. historical Architecture/Specification/Review/Approval/Implementation/Testing records remain immutable gate snapshots;
4. dated audit/cleanup records remain immutable;
5. GitHub/Git remains canonical for mutable branch/PR/review/Actions lifecycle.

Canonical interpretation:

```text
HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK
```

## 5. Findings

### Living baseline defects

The following living documents contain stale current-state assertions from before PR #36 and require reconciliation:

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/CHAT-HANDOFF.md
docs/PROJECT.md
docs/DATABASE-CURRENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/ACCESS.md
docs/ROADMAP.md
docs/ARCHITECTURAL-PATTERNS.md
docs/TRACEABILITY.md
docs/domains/README.md
docs/migrations/README.md
```

Typical defects: migrations `001–012`/`001–013` presented as current, permissions `25`/`31` presented as current, PR #24/#35 presented as latest functional baseline, migration 014 presented as pending, deleted feature/design/handoff branches presented as current, and Military Positions Directory v1 presented as unfinished.

### Governance defects

`docs/PROJECT-WORKING-RULES.md` already defines permanent rules and handoff maintenance, but its standing two-document authorization currently includes branch deletion. The owner’s current instruction explicitly authorizes required documentation actions and merge while excluding branch deletion. Standing governance must therefore exclude branch deletion and require a separate explicit deletion authorization.

`docs/DEVELOPMENT.md` must point to the permanent rules document for the standing governance exception while retaining the ordinary separate-gate rule for material increments.

### Current capability clarification

The following documents need additive current-state clarification, not architectural redesign:

```text
docs/THEMES.md
docs/CHANGELOG.md
docs/domains/REFERENCE.md
docs/domains/STAFFING.md
```

They must record PR #35/#36 completion and current validation without rewriting historical contracts.

## 6. Historical and target documents

Historical gate records under `docs/architecture`, `docs/specification`, `docs/review`, `docs/decisions`, `docs/implementation`, `docs/testing`, completed increment design records, and earlier dated audit records are intentionally preserved. Stale-looking gate markers inside those snapshots are not current defects when they accurately describe their historical gate.

Broad target documents such as `docs/DATABASE.md` and domain target specifications are not converted into current-physical-schema documents. Current physical state remains owned by `docs/DATABASE-CURRENT.md` plus executable migrations and checkers.

## 7. Reconciliation scope

Classification: documentation-only.

No changes are authorized or required to PHP runtime, SQL migrations, configuration, workflow, themes/assets, deploy scripts, tools, repository settings or branch protection.

The reconciliation must:

- update all identified living defects;
- establish `docs/PROJECT-WORKING-RULES.md` and `docs/CHAT-HANDOFF.md` as permanent operational entry points;
- preserve immutable historical records;
- record the unique unmerged research branch without importing or deleting it;
- preserve mobile and production-testing boundaries;
- create PR, perform exact-head Actions/Final PR Review and merge under the owner’s current authorization;
- not delete any branch.
