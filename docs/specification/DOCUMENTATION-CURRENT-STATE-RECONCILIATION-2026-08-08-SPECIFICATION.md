# Documentation Current-State Reconciliation — Specification — 2026-08-08

## 1. Baseline

```text
EXPECTED_MAIN=b3dda6cae88072c1e74c25de28f7023a8d73620d
EXPECTED_MAIN_TREE=4a0a6da68448be7a53e9532dd8b520607d9c3000
LATEST_FUNCTIONAL_PR=36
MIGRATIONS=001-014
SYSTEM_ROLES=4
SYSTEM_PERMISSIONS=35
REMOTE_BRANCHES=main,research/military-accounting-order-700
OPEN_PRS=0
OPEN_ISSUES=0
MOBILE=NOT_RUN_OUT_OF_SCOPE
PRODUCTION_DEPLOYMENT=NOT_PERFORMED
```

## 2. Required corrections

Living documents must not present PR #24/#35, migrations 012/013, 25/31 permissions, deleted feature/design/handoff branches, migration 014 pending validation, or an unfinished Military Positions Directory v1 as current project state.

The durable current baseline must state:

- Lowest Unit Staffing Structure v1 merged via PR #35 / migration 013;
- Military Positions Directory v1 merged via PR #36 / migration 014;
- migrations 001–014 on main;
- 35 system permissions;
- Staffing registers/versions/slots/doc metadata are implemented, but Personnel/Assignments/occupancy are not;
- Military Positions managed catalog is implemented; initial canonical version is seeded as draft and is not auto-published;
- no active product implementation increment;
- `research/military-accounting-order-700` remains separate and unique/unmerged;
- production deployment is not claimed;
- mobile remains NOT RUN / OUT OF SCOPE.

## 3. Rules and handoff

`docs/PROJECT-WORKING-RULES.md` must explicitly:

- remain the permanent project work-rules document;
- require it and `docs/CHAT-HANDOFF.md` at new-chat start;
- grant standing no-prompt maintenance only for those two documentation files;
- permit documentation commits/PR/review/merge for that two-file maintenance;
- exclude branch deletion from standing authorization.

`docs/CHAT-HANDOFF.md` must be rewritten from the obsolete active-feature log into the current operational snapshot and must be maintained after meaningful state changes.

## 4. Historical preservation

Historical Architecture, Specification, Review, Approval, Implementation, Testing, dated audits and completed design records are not rewritten merely because later lifecycle gates completed. Historical `PENDING` or `NOT AUTHORIZED` remains valid when scoped to its original gate.

## 5. Validation

Before PR:

- exact branch base verified;
- changed paths are Markdown only;
- no unexpected path;
- all current-state counts/anchors internally consistent;
- living docs no longer expose the identified stale baseline as current;
- historical records remain untouched;
- no secret or Mobile PASS claim;
- no runtime/config/DB/migration/workflow/theme/tool changes.

PR gate requires exact-head GitHub Actions success and Final PR Review PASS before merge.
