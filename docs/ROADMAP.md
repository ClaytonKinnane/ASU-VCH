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
- [x] Final PR Review и separate merge approval PR #24;
- [x] PR #24 merge and post-merge verification;
- [x] separate deletion approval и feature-branch cleanup PR #24.

## Завершённый technical Stage A — PR #25

- [x] Architecture, Specification, Review и Approval;
- [x] workflow implementation;
- [x] PR-triggered exact-head static verification;
- [x] Final PR Review;
- [x] separate merge approval;
- [x] merge commit;
- [x] post-merge push run `30837637886` — SUCCESS;
- [x] post-merge workflow_dispatch `30839122892` — SUCCESS;
- [x] separate deletion approval и feature-branch cleanup.

```text
GitHub Actions static verification: implemented
required status check: not enabled
branch protection Stage B: not implemented / separately gated
```

## Текущее documentation reconciliation

`Documentation Current-State Reconciliation v2` синхронизирует living documentation и operational closure после PR #24/#25.

Текущий workflow:

- [x] read-only audit;
- [x] Architecture;
- [x] Specification;
- [x] Formal Review;
- [x] owner Approval;
- [ ] Implementation;
- [ ] Documentation Validation;
- [ ] separate permission for Pull Request;
- [ ] Pull Request;
- [ ] Final PR Review;
- [ ] separate merge approval;
- [ ] merge and post-merge verification;
- [ ] separate branch deletion approval.

Отметки обновляются только после фактического gate; текущий document не заявляет собственный future merge.

## Текущее плановое состояние

```text
active functional increment: none
active technical increment: none
next functional increment: not selected / not approved
```

## Возможные будущие направления

Каждое направление требует отдельного Research → Approval cycle:

- personnel card;
- staffing structure и personnel assignments;
- общий Documents domain;
- общий Audit domain;
- production deployment infrastructure;
- branch protection Stage B / required status check;
- отдельный mobile verification increment.

Static CI Stage A уже реализован и не включается в future scope повторно.

## Постоянные ограничения

- Public catalogs не являются staffing или personal military accounting.
- Static CI не заменяет DB/deploy/browser/manual testing.
- Required status check не считается включённым.
- Mobile PASS не заявляется без фактической acceptance.
- PR creation, merge и branch deletion требуют отдельных approvals.
- `SAFE TO DELETE` не является deletion authorization.