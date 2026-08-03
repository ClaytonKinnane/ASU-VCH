# План разработки

## Stable baseline

```text
repository pointer: origin/main
latest functional PR: #24
latest technical PR: #25
PR #24 merge: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #25 merge: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
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

## Завершённый technical Stage A — PR #25

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

## Documentation Current-State Reconciliation v2 — completed

- [x] read-only audit;
- [x] Architecture;
- [x] Specification;
- [x] Formal Review;
- [x] owner Approval;
- [x] documentation-only Implementation;
- [x] semantic Documentation Validation;
- [x] separate permission for Pull Request;
- [x] Pull Request #26;
- [x] Final PR Review on exact head `7f9d0c0b04de2930abb00a0feedc5d2e375dbaea`;
- [x] separate merge approval;
- [x] merge commit `d9cb74245e09d8be6cd80fc5d7972e426d0aaaf7`;
- [x] post-merge push run `30846778001` — SUCCESS;
- [x] post-merge verification — PASS;
- [x] separate branch deletion approval;
- [x] original documentation branch cleanup.

```text
PR_26_STATE=CLOSED / MERGED
POST_MERGE_VERIFICATION=PASS
ORIGINAL_BRANCH=DELETED AFTER SEPARATE APPROVAL
NON_MARKDOWN_CHANGES=0
```

The original pre-PR and pre-merge gates remain preserved in process records as historical evidence. Current status is complete.

## Current planning state

```text
active functional increment: none
active technical increment: none
next functional increment: not selected / not approved
open GitHub Pull Requests at closure verification: 0
open GitHub Issues at closure verification: 0
```

Possible future directions are not active tasks until a separate documentation-first cycle is approved.

## Possible future directions

Each requires a separate Research → Approval cycle:

- personnel card;
- staffing structure and personnel assignments;
- common Documents domain;
- common Audit domain;
- production deployment infrastructure;
- branch protection Stage B / required status check;
- separate mobile verification increment.

Static CI Stage A is already implemented and is not repeated as future scope.

## Permanent constraints

- Public catalogs are not staffing or personal military accounting.
- Static CI does not replace DB/deploy/browser/manual testing.
- Required status check is not enabled.
- Mobile PASS is not claimed without actual acceptance.
- PR creation, merge and branch deletion require separate approvals.
- `SAFE TO DELETE` is not deletion authorization.