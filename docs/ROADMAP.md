# План разработки

## Stable functional baseline

Функциональные PR #1–#9, #12, #15, #19 и #20 merged в `main`. Последний functional baseline — PR #20.

```text
repository pointer: origin/main
latest functional PR: #20
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
```

## Завершённые functional этапы

- [x] базовый сайт, authentication, sessions и CSRF;
- [x] RBAC и user lifecycle;
- [x] required password change, rejection audit и archive/restore;
- [x] theme management и три built-in themes;
- [x] directory landing, ranks и organizational element types;
- [x] Organizational Structure v1;
- [x] PR #19 — public military positions catalog;
- [x] PR #19 Automated Testing и Manual Desktop Acceptance;
- [x] PR #19 merge и post-merge state;
- [x] PR #20 — public military occupational specialties catalog;
- [x] PR #20 Automated Testing, Manual Desktop Acceptance и targeted recheck;
- [x] PR #20 repeated Final PR Review PASS;
- [x] PR #20 merge и post-merge Git verification.

## Active documentation increment — Post-PR20 Baseline Refresh

```text
branch: docs/post-pr20-baseline-refresh
PR: #21 OPEN
classification: documentation only
initial allowlist: 22 Markdown paths
final approved allowlist: 25 Markdown paths
merge: NOT AUTHORIZED
branch deletion: NOT AUTHORIZED
```

Progress:

- [x] Research и Analysis;
- [x] Architecture, Specification и pre-implementation Formal Review;
- [x] owner Approval;
- [x] initial Documentation Implementation;
- [x] initial Documentation Validation PASS;
- [x] PR #21 created;
- [x] Final PR Review attempt 1 — CHANGES REQUIRED;
- [x] owner approved remediation and scope expansion 22 → 25;
- [x] PR #19 operational closure added;
- [x] current-state markers synchronized with PR #21;
- [ ] repeat Documentation Validation;
- [ ] repeat Final PR Review;
- [ ] separate merge approval;
- [ ] merge PR #21;
- [ ] post-merge verification;
- [ ] fresh remote/local branch inventory;
- [ ] exact cleanup approval;
- [ ] remote-first and approved local branch cleanup;
- [ ] terminal verification.

## Последние catalogs

### Military Positions — migration 010

```text
tables: 14
triggers: 41
canonical types: 34
variants: 35
automated testing: PASS
manual desktop acceptance: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

### Public VUS — migration 011

```text
tables: 9
triggers: 26
searchable records: 17
automated testing: PASS
manual desktop acceptance: PASS
targeted manual recheck: PASS
final PR review: PASS
post-merge verification: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

## Следующий functional increment

Не выбран и не утверждён. Возможные направления требуют отдельного Research → Approval cycle:

- карточка военнослужащего;
- штатные структуры и кадровые назначения;
- общий Documents domain;
- общий Audit domain;
- production/CI infrastructure;
- отдельный mobile verification increment.

## Постоянные ограничения

- Публичные catalogs не являются кадровым или персональным воинским учётом.
- Mobile PASS не заявляется без фактической acceptance.
- PR creation, merge и branch deletion требуют отдельных approvals.
- `SAFE TO DELETE` не является deletion authorization.
