# Implementation — Составы военнослужащих и воинские звания v2

## Historical implementation status

```text
status at implementation gate: IMPLEMENTED
branch at implementation gate: feature/military-ranks-directory-v2
runtime/manual acceptance head: b44aed14ee1a54be213cbc939322ba21b02e7a58
```

Этот раздел сохраняет исходное pre-merge состояние. Current closure приведён ниже.

## Application layer

`MilitaryRankCatalogRepository` расширен current/visible version APIs, version-scoped sources/compositions, search/filtering и integrity errors.

`MilitaryRankCompatibilityService` добавлен как Reference-owned read-only service со статусами:

- `compatible`;
- `incompatible`;
- `invalid-catalog-version`;
- `composition-not-selectable`;
- `record-not-found`;
- `integrity-error`.

Service использует same-version ancestry и не зависит от Organization.

## Database layer

Добавлены compatibility loader, versioned DDL/publication/recovery modules, marker migration 012 и trigger templates.

Published outcome:

```text
v1: superseded / valid_to 2026-08-02
v2: published/current / valid_from 2026-08-03
compositions/categories: 8
semantics: 8
ranks: 20
version sources: 2
composition sources: 8
triggers: 18
```

## UI and themes

Owner-only route supports version switching, current/historical lifecycle metadata, derived/staffing badges, source cards, version-aware search/filters, empty state and controlled HTTP 503.

Visual remediation replaced a stretched two-column grid with one start-aligned hierarchy, child indentation/connectors and concise labels.

`css/military-ranks-v2.css` is required in all three themes.

## Checkers and boundaries

Source, loader, compatibility-service, core DB, UI-layout, theme, permission and Organization compatibility checks were added/updated.

Not added:

- Staffing schema or slots;
- Organization relations;
- personnel assignments;
- real unit/personnel data;
- mutation routes;
- new permissions;
- increment B.

## Historical operational note

Migration 012 was applied in local Open Server/MySQL 8.4; repeat installer reported 12 migrations and no new migrations. Documentation commits after runtime head did not change runtime/database behavior.

## Post-merge and branch-lifecycle closure

```text
PR: #24 CLOSED / MERGED
FINAL_FEATURE_HEAD=2e996849ec51be4d83676aa779bf7e797e35932e
MERGE_COMMIT=feac7230616d3a8df98acb48f43a0b60f89f2255
RUNTIME_MANUAL_ACCEPTANCE_HEAD=b44aed14ee1a54be213cbc939322ba21b02e7a58
FINAL_PR_REVIEW_REMEDIATION_HEAD=fe893e8315f7add80ed4d0501b41d8bc39b4b0e8
POST_MERGE_VERIFICATION=PASS
REPEAT_INSTALLER=12 / NO NEW MIGRATIONS
DATABASE_REGRESSION=PASS
DEPLOY_AND_SOURCE_PARITY=PASS
HTTP_SMOKE=PASS
FEATURE_BRANCH=DELETED AFTER SEPARATE APPROVAL
MOBILE=OUT OF SCOPE / NOT RUN
```

Merge was executed only after separate owner permission. Post-merge verification confirmed `main`, feature-tree inclusion/parity, static checks, deploy, installer, DB regression, HTTP smoke and clean worktree.

Branch deletion was a separate later operation. The deleted branch name remains historical evidence and is not an operational checkout dependency.

```text
CURRENT_INCREMENT_OUTCOME=IMPLEMENTED / TESTED / ACCEPTED / REVIEWED / MERGED / VERIFIED / BRANCH_CLEANED
```