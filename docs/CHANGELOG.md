# История изменений

## 2026-08-03

### Military Ranks Directory v2 — PR #24

- добавлена migration `012_military_ranks_directory_v2.sql` через compatibility loader и fail-closed marker;
- v1 переведена в historical/superseded lifecycle;
- опубликована current v2 с 8 compositions/categories, 8 semantics, 20 неизменными ranks, 2 version sources и 8 composition sources;
- добавлены lifecycle fields, generated guards, 2 source/semantics tables и 18 integrity/immutability triggers;
- добавлен Reference-owned read-only compatibility service;
- реализованы version switch, historical view, search, filters, source cards и controlled error states;
- добавлен required theme asset `css/military-ranks-v2.css` во все 3 themes;
- automated/runtime, DB, deploy/parity, HTTP smoke и manual desktop acceptance: PASS;
- mobile: `OUT OF SCOPE / NOT RUN`;
- PR #24 merge commit: `feac7230616d3a8df98acb48f43a0b60f89f2255`;
- post-merge verification: PASS;
- repeat installer: 12 migrations / no new migrations;
- feature branch удалена после отдельного owner approval.

### GitHub Actions Static Verification v1 — PR #25

- добавлен workflow `.github/workflows/static-verification.yml`;
- triggers: Pull Request в `main`, push в `main`, `workflow_dispatch`;
- runner Ubuntu 24.04, PHP 8.5, `contents: read`;
- event-aware `git diff --check`;
- NUL-safe lint tracked PHP;
- 9 explicit CI-safe checker'ов;
- final clean-worktree verification;
- exact-head Final PR Review: PASS;
- PR #25 merge commit: `c567429b3aa4d629a4e7c11fec7e3dbae907d92e`;
- automatic push run `30837637886`: SUCCESS;
- manual workflow_dispatch run `30839122892`: SUCCESS;
- branch protection и repository settings не изменялись;
- required status check не включён;
- feature branch удалена после отдельного owner approval;
- DB/deploy/browser/visual/mobile testing не выполнялось в scope static-only increment.

### Documentation Current-State Reconciliation v2 — PR #26

- выполнен read-only audit documentation baseline после PR #24/#25;
- выявлено 0 blocking, 6 major и 1 minor finding;
- утверждены Architecture, Specification, Formal Review и exact 29-path Markdown allowlist;
- 15 living documents актуализированы до functional PR #24, migration 012 и technical PR #25;
- 6 operational records PR #24/#25 получили additive post-merge and branch-lifecycle closure;
- исправлены migration count 11 → 12 и required CSS asset count 9 → 10;
- documented static CI Stage A and explicit Stage B boundary;
- Approval, immutable audit, Implementation and Validation evidence added;
- semantic Documentation Validation: PASS on implementation head `7968ca979e5a37fafc93470b450d9724b3707b03`;
- exact Final PR Review head: `7f9d0c0b04de2930abb00a0feedc5d2e375dbaea`;
- exact-head workflow run `30846434476`: SUCCESS;
- Final PR Review: PASS;
- PR #26 merge commit: `d9cb74245e09d8be6cd80fc5d7972e426d0aaaf7`;
- automatic post-merge push run `30846778001`: SUCCESS;
- post-merge verification: PASS;
- merged changed paths: 29 / 29 approved Markdown paths;
- non-Markdown changes: 0;
- original branch `docs/documentation-current-state-reconciliation-v2` удалена после отдельного owner approval;
- runtime, DB, migrations, workflow, themes, deploy, branch protection, required checks and settings unchanged;
- mobile: `OUT OF SCOPE / NOT RUN`.

### Documentation Current-State Reconciliation v2 Closure — PR #27

- выполнен отдельный documentation-only cycle для устранения stale living-status сведений PR #26;
- Architecture, Specification, Formal Review и exact 13-path Markdown allowlist утверждены владельцем;
- living index, roadmap, changelog и три process records v2 синхронизированы с durable completed outcome PR #26;
- historical pending/pre-merge facts и permission boundaries сохранены как gate-scoped evidence;
- Documentation Validation и exact-head Final PR Review завершены с PASS;
- runtime, DB, migrations, workflow, themes, deploy, tools, branch protection, required checks и repository settings не изменялись;
- closure-ветка удалена после отдельного owner approval;
- mutable lifecycle evidence PR #27 остаётся canonical в GitHub PR timeline, reviews, Actions и branch inventory;
- отсутствие повторной копии lifecycle последнего documentation PR в Markdown не является новой documentation task.

## 2026-08-02

### Full Documentation Consistency Reconciliation

- выполнен полный read-only audit Markdown-документации относительно `main @ 1eef56b50a8d2278a62c5b70a471663b12132354` и merged PR #1–#22;
- подтвержден baseline PR #20 / migrations 001–011 на момент audit;
- введено правило semantic classification;
- domain/migration indexes обновлены;
- target/historical framing исправлен;
- создан immutable audit record `DOCUMENTATION-CONSISTENCY-AUDIT-2026-08-02.md`;
- runtime/database/browser retesting не требовался;
- mobile: `OUT OF SCOPE / NOT RUN`.

## 2026-08-01

### Military Positions — PR #19

- migration 010;
- 14 tables, 41 triggers, 34 canonical types и 35 variants;
- automated and manual desktop testing: PASS;
- merge commit: `99f9f283768ca418fb7ff86d55b7d73e7a6c3510`;
- mobile: `OUT OF SCOPE / NOT RUN`.

### Public VUS — PR #20

- migration 011;
- 9 tables, 26 triggers и 17 searchable records;
- automated, manual desktop and targeted recheck: PASS;
- merge commit: `3082ec6ecbeddb92bd65e1398f05a9339abb199b`;
- post-merge verification: PASS;
- mobile: `OUT OF SCOPE / NOT RUN`.

### Post-PR20 Baseline Refresh — PR #21

- documentation-only baseline refresh;
- repeat Documentation Validation and Final PR Review: PASS;
- merge commit: `f5b53f2ee4453f293b58cbe486e0943ab602335b`;
- post-merge Git verification and approved cleanup: PASS.

## 2026-07-31

### Repository cleanup closure

- merged documentation reconciliation;
- approved remote/local cleanup completed;
- dated terminal snapshot recorded;
- runtime, deploy and database unchanged.

## Earlier changes

Historical Security, Theme, Reference and Organization records are retained in `docs/design`, `docs/architecture`, `docs/decisions`, `docs/review`, `docs/testing` and merged PR history.