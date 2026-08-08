# Текущее состояние проекта АСУ-ВЧ

Дата актуализации durable state: `2026-08-08`.

## Live snapshot at this reconciliation

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
main snapshot: b3dda6cae88072c1e74c25de28f7023a8d73620d
open Pull Requests: 0
open Issues: 0
remote branches before documentation reconciliation: 2
  main
  research/military-accounting-order-700
latest main Static Verification: 31234849967 / SUCCESS
```

Live values are rechecked through GitHub/Git before actions. The SHA above is an audit anchor, not a permanent pointer.

Current `main` tree is identical to PR #36 merge tree. History-only noop/revert commits occurred after PR #36 and restored the exact content tree; no force rewrite is authorized or required by documentation reconciliation.

## Durable baseline

```text
latest merged functional increment: PR #36 / Military Positions Directory v1
previous merged functional increment: PR #35 / Lowest Unit Staffing Structure v1
migrations: 001–014
system roles: 4
system permissions: 35
built-in themes: 3
required CSS assets per theme: 10
active functional implementation: none
active material technical implementation: none
mobile: NOT RUN / OUT OF SCOPE
production deployment: NOT PERFORMED
```

## Реализованные области

### Platform / Security

- owner bootstrap and public-registration closure;
- authentication, protected sessions, CSRF;
- 4 roles / 35 permissions;
- user approval/rejection/block/archive/restore lifecycle;
- required temporary-password change;
- operation audit and themed access/error UX.

### Themes

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`;
- 10 required CSS assets per theme.

### Reference / Directories

- Military Ranks v2: current published v2 + historical v1;
- organizational element types;
- public VUS information;
- legacy military-position classifier from migration 010;
- managed Military Positions Directory v1 from migration 014.

Managed positions support draft/version lifecycle, create/update, logical archive/restore, explicit publish/cancel, readable append-only history and four dedicated permissions. Migration 014 seeds one canonical 24-entry synthetic draft and does not publish it automatically.

### Organization

Organizational Structure v1 implements structures, versions, draft tree, stable elements, document metadata, history and compare.

### Staffing

Lowest Unit Staffing Structure v1 / migration 013 implements registers, version lifecycle, documents metadata, stable individual slots, rank/VUS requirements, Organization/catalog pins, history and compare.

Not implemented in Staffing v1: people, assignments, occupied/vacant facts, real unit/personnel data.

## PR #36 validation evidence

Exact runtime head: `c647a933011873048866c75978d3f506634011fd`.

```text
total allowlist: 38/38
corrective inventories: 12/12, 9/9, 8/8, 9/9
PHP lint: 171 files / PASS
migrations: 001–014
initialization runs: 2
DB/runtime checker: 167 PASS
HTTP smoke: 200, 200, expected 302
asu-blue desktop: PASS
asu-light-blue desktop: PASS
asu-evgeniya-rostova desktop: PASS
mutual exclusion: PASS
UI-F04: CLOSED
UI-F05: CLOSED
open findings: 0
real Staffing data mutation: NONE
mobile: NOT RUN / OUT OF SCOPE
```

PR #36 final feature head `3756b2ec53a00f68d5c1f5c098d1c274f6b8d769`; merge commit `a6cfceb421fac8d0985e409770bb26a62fac0b14`; post-merge Actions SUCCESS. Runtime evidence belongs to the runtime head and is not transferred to later documentation-only commits as a new test.

## Branch state and research

Obsolete branches already removed after explicit owner approvals:

- `design/military-positions-directory-v1`;
- `feature/military-positions-directory-v1`;
- `docs/handoff-lowest-unit-staffing-design`;
- `docs/handoff-military-accounting-research`.

`research/military-accounting-order-700` remains and must not be treated as cleanup residue. It is diverged from main with 8 unique commits and six unique research files. Its conclusions are research/analysis material for `PersonnelServiceAccounting`, not merged implementation.

## Current planning

No product implementation increment is active. Possible next directions require a new Research → Approval cycle, for example:

- Personnel Core / military servicemember card;
- person → Staffing assignment and derived vacancy/occupancy;
- common Documents/Orders capabilities;
- common Audit domain;
- reports/import/export;
- production deployment infrastructure;
- branch protection Stage B / required status check;
- separate mobile verification increment.

Presence in this list is not implementation approval.

## Permanent gates

Ordinary material work follows documentation-first lifecycle. Standing no-prompt maintenance for `docs/PROJECT-WORKING-RULES.md` and `docs/CHAT-HANDOFF.md` is defined in the rules document. Branch deletion always requires separate exact owner authorization.
