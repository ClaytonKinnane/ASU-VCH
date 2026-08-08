# План разработки

## Stable baseline

```text
repository pointer: origin/main
latest merged functional baseline: PR #35 / migration 013
current main: 9ae05b9928903cc483ce415d7378b546e419264c
static CI baseline: PR #25
documentation governance baseline: PR #28
local automation foundation: PR #29
local automation corrected baseline: PR #30
durable technical capability coverage: through PR #30
migrations on main: 001–013
active implementation target: migration 014
system roles: 4
system permissions on main: 31
built-in themes: 3
required CSS assets: 10
```

## Завершённые functional stages

- [x] initial site, authentication, sessions и CSRF;
- [x] RBAC и user lifecycle;
- [x] required password change, rejection audit и archive/restore;
- [x] theme management и 3 built-in themes;
- [x] directory landing, ranks и organizational element types;
- [x] Organizational Structure v1;
- [x] public military positions catalog — migration 010;
- [x] public VUS catalog — migration 011;
- [x] Military Ranks Directory v2 — migration 012;
- [x] current v2 / historical v1;
- [x] automated/runtime/DB/deploy/HTTP testing PR #24;
- [x] manual desktop acceptance PR #24;
- [x] Final PR Review and separate merge approval PR #24;
- [x] PR #24 merge, post-merge verification and separately approved branch cleanup.
- [x] Lowest Unit Staffing Structure v1 — migration 013;
- [x] Staffing PR #35 merge and successful post-merge Actions;

## Завершённый Static CI Stage A — PR #25

- [x] Architecture, Specification, Review and Approval;
- [x] workflow implementation;
- [x] PR exact-head static verification;
- [x] Final PR Review and separate merge approval;
- [x] merge commit;
- [x] post-merge push run `30837637886` — SUCCESS;
- [x] post-merge workflow_dispatch `30839122892` — SUCCESS;
- [x] separately approved feature-branch cleanup.

```text
GitHub Actions static verification: implemented
required status check: not enabled
branch protection Stage B: not implemented / separately gated
```

## Завершённый Documentation Governance Stage — PR #28

- [x] terminal documentation model;
- [x] GitHub/Git designated canonical for mutable lifecycle;
- [x] historical gate records preserved;
- [x] `HISTORICAL_GATE_PENDING != OPEN_PROJECT_TASK`;
- [x] recursive post-merge closure solely for lifecycle copying prohibited;
- [x] runtime, database, workflow, tools and settings unchanged.

## Завершённый Local Automation Foundation — PR #29

- [x] one-command Windows PowerShell 5.1 installer;
- [x] Git and GitHub CLI setup flows;
- [x] Node.js LTS and Codex CLI setup flows;
- [x] Codex authentication modes;
- [x] integrity manifest;
- [x] atomic local helper installation;
- [x] fail-closed remote branch cleanup helper;
- [x] user guide and Codex project instructions.

Repository/static validation не объявлялась полной real target-machine authentication acceptance.

## Завершённый PowerShell 5.1 Hardening — PR #30

- [x] authoritative native-process exit codes;
- [x] separated stdout/stderr;
- [x] safe `.cmd`/`.bat` invocation through `%ComSpec%`;
- [x] collection normalization and bounded timeouts;
- [x] explicit ChatGPT/API-key mode enforcement;
- [x] secure API-key stdin handling;
- [x] atomic install rollback hardening;
- [x] Cleanup Doctor enforcement;
- [x] native Windows PowerShell 5.1 regression harness;
- [x] native validation `58 PASS / 0 FAIL`;
- [x] exact-head workflow run `31024419654` — SUCCESS;
- [x] post-merge push run `31025264683` — SUCCESS;
- [x] separately approved branch cleanup.

## Documentation consistency history

Current-State Reconciliation v2 и его closure завершены как historical documentation records. PR #28 установил terminal model: будущий documentation PR lifecycle не копируется обратно в living Markdown только ради фиксации собственного merge/run/cleanup.

## Current planning state

```text
active functional increment: Military Positions Directory v1
active material technical increment: none
implementation approval: granted on main@9ae05b9928903cc483ce415d7378b546e419264c
implementation branch: feature/military-positions-directory-v1
implementation target: migration 014 / maximum 38 paths
local validation: pending exact-head execution
Pull Request: not authorized
required status check: not enabled
branch protection Stage B: not implemented
```

## Active Military Positions Directory v1

- [x] Research and existing-state analysis;
- [x] Architecture 0.2;
- [x] Specification 0.2;
- [x] Formal Review PASS / zero open findings;
- [x] post-Staffing reconciliation;
- [x] exact owner Implementation Approval;
- [x] branch created from exact main and implementation prepared;
- [ ] exact-head static/automated validation;
- [ ] local MySQL backup/migration/repeat/deploy/HTTP checks;
- [ ] desktop visual acceptance in three themes;
- [ ] separate PR approval;
- [ ] PR exact-head Actions and Final PR Review;
- [ ] separate merge approval and post-merge verification.

Possible future directions не являются active tasks до отдельного Research → Approval cycle.

## Possible future directions

Each requires a separate Research → Approval cycle:

- personnel card;
- personnel core and assignments (staffing structure itself is implemented);
- common Documents domain;
- common Audit domain;
- production deployment infrastructure;
- branch protection Stage B / required status check;
- separate mobile verification increment.

Static CI Stage A и local automation through PR #30 уже реализованы и не повторяются как future scope.

## Permanent constraints

- Public catalogs are not staffing or personal military accounting.
- Static CI does not replace DB/deploy/browser/manual testing.
- Local automation does not grant browser ChatGPT direct local-machine access.
- Required status check is not enabled.
- Mobile PASS is not claimed without actual acceptance.
- PR creation, merge and branch deletion require separate approvals.
- `SAFE TO DELETE` is not deletion authorization.
- Documentation PR lifecycle alone is not a trigger for recursive Markdown closure.
