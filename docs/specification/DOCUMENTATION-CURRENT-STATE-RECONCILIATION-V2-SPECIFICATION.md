# Documentation Current-State Reconciliation v2 — Specification

## 1. Статус

```text
stage: Specification
status: PREPARED FOR OWNER REVIEW
classification: documentation-only
approved baseline: main @ c567429b3aa4d629a4e7c11fec7e3dbae907d92e
branch: docs/documentation-current-state-reconciliation-v2
date: 2026-08-03
implementation authorized: NO
```

## 2. Цель

После Implementation все living/current-state sections должны согласованно описывать merged состояние проекта после PR #24 и PR #25, а operational records этих PR должны содержать additive post-merge/branch-lifecycle closure без удаления или подмены исторических gate facts.

## 3. Общий contract

Implementation обязана:

- изменить только exact 29 allowlisted Markdown-путей;
- сохранить runtime, schema, workflow и GitHub settings без изменений;
- обновить current-state facts до functional PR #24, migration 012 и technical PR #25;
- различать functional, technical, tested-runtime, merge и documentation anchors;
- добавлять closure sections, а не переписывать historical evidence;
- не хранить current `main` SHA как самореферентное living field;
- не заявлять mobile PASS;
- не публиковать production/instance secrets;
- пройти documentation validation до создания Pull Request.

## 4. Canonical current-state facts

Во всех затронутых living documents применяются совместимые формулировки:

```text
latest functional PR: #24
latest technical PR: #25
PR #24 merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #25 merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
migrations: 001–012
applied migrations in last PR #24 post-merge verification: 12
system roles: 4
system permissions: 25
built-in themes: 3
required CSS assets per theme: 10
active functional increment: none
active technical increment: none
GitHub Actions static verification: implemented
required status check: not enabled
branch protection mutation: not performed
```

Точный live HEAD определяется динамически через GitHub/Git. Merge SHA хранятся только как historical anchors соответствующих PR.

## 5. Runtime and test anchor contract

### PR #24

```text
runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
Final PR Review remediation head: fe893e8315f7add80ed4d0501b41d8bc39b4b0e8
final feature head: 2e996849ec51be4d83676aa779bf7e797e35932e
merge commit: feac7230616d3a8df98acb48f43a0b60f89f2255
post-merge verification: PASS
mobile: OUT OF SCOPE / NOT RUN
```

Merge commit не объявляется исходным runtime/manual acceptance head. Post-merge evidence описывается отдельно.

### PR #25

```text
validated implementation/remediation head: 7bc170d4673b1143e4b7d149738a4c081e2af476
exact Final PR Review head: 0c6f7338f912e8797868d02d54fc015df7533ad6
merge commit: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
post-merge push run: 30837637886 / SUCCESS
post-merge workflow_dispatch run: 30839122892 / SUCCESS
mobile: OUT OF SCOPE / NOT RUN
```

PR #25 не создаёт DB/deploy/browser runtime PASS и не заменяет профильные проверки функциональных инкрементов.

## 6. Living documentation requirements

### 6.1 `README.md`

Обязательно:

- заменить latest functional PR #20 на #24;
- добавить latest technical PR #25;
- обновить migration range 001–011 на 001–012;
- добавить Military Ranks Directory v2 behavior;
- добавить реализованный GitHub Actions static workflow;
- сохранить 4 roles, 25 permissions и 3 themes;
- обновить testing boundary: PR #24 desktop/runtime PASS, PR #25 static CI PASS, mobile not run;
- не хранить `main` SHA как current pointer.

### 6.2 `docs/README.md`

Обязательно:

- обновить functional baseline до PR #24 / migration 012;
- добавить technical baseline PR #25;
- добавить ссылки на новый audit и process records;
- включить CI capability и Stage B boundary;
- сохранить semantic classification rules;
- не превращать historical PR #21/#23 records в current project status.

### 6.3 `docs/PROJECT-STATUS.md`

Документ становится главным кратким current-state summary после PR #24/#25.

Обязательно включить:

- дату reconciliation;
- latest functional PR #24;
- latest technical PR #25;
- migrations 001–012;
- Military Ranks Directory v2 summary;
- runtime-tested and post-merge anchors PR #24;
- CI workflow and post-merge runs PR #25;
- 124 tracked PHP files только как PR #25 CI evidence, не как полный local runtime runner;
- current required check = not enabled;
- active increments = none;
- dynamic Git state rule.

Устаревшие assertions о latest PR #20 и last runtime baseline 113 PHP / 11 migrations удаляются из current sections либо переносятся в явный historical context.

### 6.4 `docs/PROJECT.md`

Обязательно:

- migration count 12;
- описание current/historical Military Ranks v2;
- 10 required CSS-assets;
- убрать GitHub CI из `Не реализовано`;
- добавить static CI в реализованную Infrastructure capability;
- явно оставить production deployment и required branch protection как не реализованные/не включённые;
- обновить контрольные точки PR #24/#25.

### 6.5 `docs/ROADMAP.md`

Обязательно:

- отметить PR #24 implementation/testing/merge/post-merge/branch cleanup завершёнными;
- отметить PR #25 Architecture through post-merge/branch cleanup завершёнными;
- убрать общий `production/CI infrastructure` как единый future item;
- разделить future production infrastructure и separately gated branch-protection Stage B;
- сохранить отсутствие выбранного следующего functional increment.

### 6.6 `docs/CHANGELOG.md`

Добавить датированный раздел 2026-08-03 минимум с подразделами:

- Military Ranks Directory v2 — PR #24;
- PR #24 post-merge verification and branch lifecycle;
- GitHub Actions Static Verification v1 — PR #25;
- PR #25 post-merge push/manual runs and branch lifecycle;
- Documentation Current-State Reconciliation v2 — текущий documentation-only increment со статусом, соответствующим фактическому gate.

Changelog не должен заявлять merge текущего reconciliation до его выполнения.

### 6.7 `docs/DATABASE-CURRENT.md`

Обязательно:

- обновить applied migrations 11 → 12;
- добавить строку migration 012;
- заменить migration 007-only current model на versioned v1/v2 current physical outcome;
- перечислить lifecycle columns/guards и две новые tables;
- зафиксировать 8 compositions, 8 semantics, 20 ranks, 2 version sources, 8 composition sources;
- зафиксировать 18 v2 triggers как added lifecycle/integrity/immutability set;
- описать compatibility loader и marker fail-closed;
- указать repeat installer `12 / no new migrations`;
- сохранить roles 4 / permissions 25.

### 6.8 `docs/ENVIRONMENT.md`

Обязательно:

- migrations 001–012;
- repeat installer 12;
- required CSS assets 10, включая `military-ranks-v2.css`;
- добавить профильный Military Ranks v2 checker/test evidence либо ссылку на Test Report;
- сохранить PHP 8.5.4 local runtime evidence отдельно от GitHub runner PHP 8.5.9;
- не объявлять GitHub-hosted version локальной production/runtime requirement.

### 6.9 `docs/LOCAL-RUNBOOK.md`

Обязательно:

- functional anchors PR #24 и technical anchor PR #25;
- migrations 001–012 и repeat installer 12;
- Military Ranks v2 post-merge verification summary;
- static CI inspection commands/expectations без изменения workflow;
- required check = not enabled;
- historical PR #21 cleanup section сохранить как dated snapshot;
- branch inventory оставлять dynamic;
- local fixture/secret boundary сохранить.

### 6.10 `docs/THEMES.md`

Обязательно:

- заменить `девять` на `десять` required CSS-assets;
- добавить `css/military-ranks-v2.css` в exact list;
- описать назначение asset;
- обновить checker coverage и last results PR #24;
- сохранить четыре SVG для `asu-evgeniya-rostova`.

### 6.11 `docs/ARCHITECTURAL-PATTERNS.md`

Обязательно:

- обновить status coverage через functional PR #24 / migration 012 и technical PR #25;
- добавить version evolution pattern для immutable superseded catalog version и derived application semantics;
- добавить static CI Stage A boundary как governance/verification pattern;
- сохранить правило, что CI не заменяет DB/runtime/manual testing;
- сохранить semantic classification и closure pattern.

### 6.12 `docs/domains/README.md`

Обязательно:

- latest functional PR #24;
- список functional increments с #24;
- Reference summary: четыре специализированных routes, при этом ranks catalog теперь имеет current v2 и historical v1;
- migration column для ranks отражает 007 + 012 evolution;
- добавить compatibility service как Reference-owned read-only contract;
- сохранить отсутствие Staffing tables, Organization bindings и personnel data.

### 6.13 `docs/migrations/README.md`

Обязательно:

- range 001–012;
- строка migration 012;
- отдельный раздел migration 012 с approved records, compatibility loader, marker, DDL/publication/recovery и outcome;
- repeat installer 12;
- permissions unchanged at 25;
- packaging section расширить на migration 012, не приписывая ей gzip/base64 packaging, если её mechanism отличается;
- сохранить requirements для backup и fail-closed recovery.

### 6.14 `docs/DEVELOPMENT.md`

Обязательно:

- добавить GitHub Actions Static Verification как обязательный дополнительный static signal для PR/push, когда workflow применим;
- явно указать, что workflow не заменяет local DB/deploy/browser testing;
- required check/branch protection не считать включёнными;
- сохранить manual owner approval gates.

### 6.15 `docs/ACCESS.md`

Обязательно:

- уточнить current Military Ranks v2 route behavior: owner-only, read-only, historical version view;
- сохранить 4 roles / 25 permissions;
- заменить ограниченную формулировку `PR #19/#20 directories` на current directory baseline;
- подтвердить, что migration 012 не добавляет permissions;
- согласовать secret terminology с local-only fixture policy, не публикуя реальные temporary passwords.

## 7. Operational closure requirements

### 7.1 PR #24 records

В каждый из трёх файлов добавляется отдельный closure section:

1. `docs/implementation/MILITARY-RANKS-DIRECTORY-V2-IMPLEMENTATION.md`
2. `docs/testing/MILITARY-RANKS-DIRECTORY-V2-TEST-REPORT.md`
3. `docs/review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md`

Обязательные поля:

```text
PR: #24 CLOSED / MERGED
FINAL_FEATURE_HEAD=2e996849ec51be4d83676aa779bf7e797e35932e
MERGE_COMMIT=feac7230616d3a8df98acb48f43a0b60f89f2255
POST_MERGE_VERIFICATION=PASS
REPEAT_INSTALLER=12 / NO NEW MIGRATIONS
DATABASE_REGRESSION=PASS
DEPLOY_AND_PARITY=PASS
HTTP_SMOKE=PASS
FEATURE_BRANCH=DELETED AFTER SEPARATE APPROVAL
MOBILE=OUT OF SCOPE / NOT RUN
```

Original pre-merge statements remain intact and explicitly temporal.

### 7.2 PR #25 records

В каждый из трёх файлов добавляется отдельный closure section:

1. `docs/implementation/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-IMPLEMENTATION.md`
2. `docs/testing/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-TEST-REPORT.md`
3. `docs/review/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-PR-FINAL-REVIEW.md`

Обязательные поля:

```text
PR: #25 CLOSED / MERGED
EXACT_FINAL_PR_HEAD=0c6f7338f912e8797868d02d54fc015df7533ad6
MERGE_COMMIT=c567429b3aa4d629a4e7c11fec7e3dbae907d92e
FINAL_PR_REVIEW=PASS
PUSH_RUN=30837637886 / SUCCESS
WORKFLOW_DISPATCH_RUN=30839122892 / SUCCESS
BRANCH_PROTECTION_CHANGED=NO
REQUIRED_STATUS_CHECK_ENABLED=NO
FEATURE_BRANCH=DELETED AFTER SEPARATE APPROVAL
DB_DEPLOY_BROWSER_MOBILE=OUT OF SCOPE / NOT RUN
```

Original attempt history, pending rule and pre-merge verdict remain preserved as historical sequence.

## 8. New audit and process records

### 8.1 Immutable audit

`docs/DOCUMENTATION-CURRENT-STATE-AUDIT-2026-08-03.md` должен содержать:

- approved baseline;
- audit methodology and coverage;
- 6 Major + 1 Minor findings;
- source-of-truth matrix;
- approved remediation summary;
- exact allowlist;
- validation authority;
- explicit immutable snapshot classification.

Он не должен хранить transient current PR state как perpetually live field.

### 8.2 Approval record

Approval document создаётся только после отдельного owner approval и цитирует точное разрешение на Implementation и 29-path allowlist.

### 8.3 Implementation record

Implementation record фиксирует actual changed paths, exact implementation head, применённые edits и отсутствие non-Markdown diff.

### 8.4 Validation record

Validation фиксирует exact implementation head и результаты всех checks. Evidence commit после validated head маркируется как documentation-only.

### 8.5 Final PR Review record

Final PR Review record создаётся в пределах allowlist и получает exact-head verdict только после финального workflow run и GitHub-side review. Его создание само по себе не разрешает merge.

## 9. Exact changed-path allowlist

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/DATABASE-CURRENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/THEMES.md
docs/ARCHITECTURAL-PATTERNS.md
docs/domains/README.md
docs/migrations/README.md
docs/DEVELOPMENT.md
docs/ACCESS.md
docs/implementation/MILITARY-RANKS-DIRECTORY-V2-IMPLEMENTATION.md
docs/testing/MILITARY-RANKS-DIRECTORY-V2-TEST-REPORT.md
docs/review/MILITARY-RANKS-DIRECTORY-V2-PR-FINAL-REVIEW.md
docs/implementation/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-IMPLEMENTATION.md
docs/testing/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-TEST-REPORT.md
docs/review/GITHUB-ACTIONS-STATIC-VERIFICATION-V1-PR-FINAL-REVIEW.md
docs/DOCUMENTATION-CURRENT-STATE-AUDIT-2026-08-03.md
docs/architecture/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-ARCHITECTURE.md
docs/specification/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-SPECIFICATION.md
docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-FORMAL-REVIEW.md
docs/decisions/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-APPROVAL.md
docs/implementation/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-IMPLEMENTATION.md
docs/testing/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-VALIDATION.md
docs/review/DOCUMENTATION-CURRENT-STATE-RECONCILIATION-V2-PR-FINAL-REVIEW.md
```

```text
EXPECTED_PATH_COUNT=29
EXPECTED_MARKDOWN_PATH_COUNT=29
EXPECTED_NON_MARKDOWN_PATH_COUNT=0
```

## 10. Forbidden changes

Diff обязан быть нулевым для:

```text
.github/** except none
app/**
config/**
database/**
deploy/**
public/**
themes/**
tools/**
```

Запрещены также branch protection, repository settings, Actions settings, workflow dispatch mutation, deploy, installer, DB operations и branch deletion в рамках Implementation.

## 11. Validation requirements

### 11.1 Git and scope

- approved baseline exact match before Implementation;
- branch merge-base = approved baseline либо owner-approved updated baseline;
- behind main = 0 at validation time;
- changed paths exactly 29;
- non-Markdown paths = 0;
- `git diff --check` = PASS.

### 11.2 Link validation

Проверяются все relative Markdown links в changed files. Каждый target обязан существовать на implementation head. Raw textual paths, используемые как code examples, должны быть различимы с Markdown links.

### 11.3 Stale assertion scan

В current sections запрещены как текущие:

```text
latest functional PR: #20
migrations: 001–011
applied migrations: 11
required CSS assets: 9
GitHub CI: not implemented
Final PR Review PR #25: pending
PR #25 merge: not performed
PR #24/#25 feature branch: retained
```

Такие значения допускаются только внутри явно исторических snapshots/attempt sections.

### 11.4 Historical anchors

Обязаны сохраниться:

- PR #19/#20/#21/#23 historical merge/test anchors;
- PR #24 runtime, remediation, final feature and merge anchors;
- PR #25 implementation, exact review and merge anchors;
- failed CI attempt 1 and successful subsequent evidence;
- backup deviation PR #24;
- original merge-not-authorized gate statements.

### 11.5 Capability boundaries

Проверяются markers:

```text
CI_IMPLEMENTED=YES
REQUIRED_CHECK_ENABLED=NO
BRANCH_PROTECTION_CHANGED=NO
CI_REPLACES_RUNTIME_TESTING=NO
MOBILE_PASS_CLAIMED=NO
PRODUCTION_DEPLOYMENT_CLAIMED=NO
```

### 11.6 Secret boundaries

Разрешено упоминание утверждённого public local-only fixture только с existing restrictions. Запрещены:

- production credentials;
- instance-specific credentials;
- real temporary user passwords;
- `config/local.php` contents;
- session data;
- private keys and tokens.

## 12. Acceptance criteria

- AC-01: implementation begins only after separate owner approval.
- AC-02: approved baseline is rechecked before writes.
- AC-03: exact 29-path allowlist is respected.
- AC-04: changed files are Markdown only.
- AC-05: latest functional PR is #24.
- AC-06: latest technical PR is #25.
- AC-07: migrations are 001–012.
- AC-08: `DATABASE-CURRENT.md` includes exact migration 012 outcome.
- AC-09: Military Ranks v1/v2 distinction is consistent.
- AC-10: required CSS asset count is 10.
- AC-11: `military-ranks-v2.css` is documented in all applicable theme contracts.
- AC-12: GitHub Actions Static Verification is documented as implemented Stage A.
- AC-13: push and manual post-merge runs are recorded.
- AC-14: required status check remains not enabled.
- AC-15: branch protection/settings mutation is not claimed.
- AC-16: PR #24 operational records receive additive closure.
- AC-17: PR #25 operational records receive additive closure.
- AC-18: original historical gate verdicts remain preserved.
- AC-19: changelog includes PR #24 and PR #25.
- AC-20: domain index includes ranks v2 evolution.
- AC-21: migration index includes migration 012.
- AC-22: all changed relative links resolve.
- AC-23: stale-current-state scan passes.
- AC-24: exact historical anchors are preserved.
- AC-25: no runtime-tested merge-head overclaim exists.
- AC-26: no Mobile PASS is claimed.
- AC-27: secret boundary review passes.
- AC-28: runtime/config/database/migration/workflow/theme/deploy/tool diff is zero.
- AC-29: Documentation Validation passes on exact implementation head.
- AC-30: Final PR Review is performed on exact current PR head.
- AC-31: merge requires separate explicit owner approval.
- AC-32: branch deletion requires separate post-merge owner approval.

## 13. Test classification

```text
DOCUMENTATION_SEMANTIC_REVIEW=REQUIRED
MARKDOWN_LINK_VALIDATION=REQUIRED
STALE_ASSERTION_SCAN=REQUIRED
HISTORICAL_ANCHOR_REVIEW=REQUIRED
SECRET_BOUNDARY_REVIEW=REQUIRED
GIT_DIFF_CHECK=REQUIRED
GITHUB_STATIC_WORKFLOW=EXPECTED WHEN PR EXISTS
PHP_RUNTIME_RETEST=NOT_REQUIRED
MYSQL_RETEST=NOT_REQUIRED
INSTALLER_EXECUTION=NOT_REQUIRED
DEPLOY=NOT_REQUIRED
HTTP_BROWSER_TESTING=NOT_REQUIRED
VISUAL_ACCEPTANCE=NOT_REQUIRED
MOBILE_TESTING=OUT_OF_SCOPE / NOT RUN
```

Этот Specification document не разрешает Implementation.
