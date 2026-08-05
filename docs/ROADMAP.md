# План разработки

## Stable baseline

```text
repository pointer: origin/main
latest functional runtime baseline: PR #24 / migration 012
static CI baseline: PR #25
documentation governance baseline: PR #28
local automation foundation: PR #29
local automation corrected baseline: PR #30
durable technical capability coverage: through PR #30
migrations: 001–012
system roles: 4
system permissions: 25
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
active functional increment: none
active material technical increment: none
next functional increment: not selected / not approved
required status check: not enabled
branch protection Stage B: not implemented
```

Possible future directions не являются active tasks до отдельного Research → Approval cycle.

## Possible future directions

Each requires a separate Research → Approval cycle:

- personnel card;
- staffing structure and personnel assignments;
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
