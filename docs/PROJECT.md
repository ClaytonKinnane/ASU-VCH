# О проекте

## Наименование и назначение

**АСУ-ВЧ** — автоматизированная система учёта военнослужащих «Войсковая часть».

Проект автоматизирует управление доступом, нормативные справочники, организационные структуры и связанные процессы. Он развивается инкрементально; material scope проходит documentation-first workflow. Закрытые, ограниченные и фактические сведения не включаются без отдельного утверждения data/security model.

## Основные требования

- GitHub repository `ClaytonKinnane/ASU-VCH` — источник истины.
- Current HEAD определяется через `origin/main`.
- Изменения выполняются в отдельных ветках.
- Merge и branch deletion имеют отдельные owner gates.
- Runtime data хранится в MySQL; secrets и local config не хранятся в Git.
- Mobile PASS не заявляется без фактической mobile acceptance.

## Реализованное состояние

### Platform и Security

- migrations 001–012;
- bootstrap первого owner и отключение public registration после его создания;
- authentication, sessions, CSRF;
- RBAC: 4 system roles / 25 permissions;
- full user lifecycle и required password change;
- audit критических user operations;
- themed HTTP 403 и operation-result modal.

### Themes

- static trusted registry;
- global active theme;
- default/fallback `asu-blue`;
- 3 built-in themes;
- 10 required CSS-assets per theme, включая `military-ranks-v2.css`;
- 4 additional SVG-assets для `asu-evgeniya-rostova`;
- desktop acceptance затронутых interfaces.

### Reference directories

Owner-only read-only routes:

- military ranks: current v2 + historical v1;
- organizational element types;
- military positions;
- public military occupational specialties.

Military Ranks v2 сохраняет 20 ranks, добавляет 8 version-scoped compositions/categories, 8 semantics, 2 version sources, 8 composition sources и read-only compatibility service. Staffing schema, Organization bindings, assignments и real personnel data отсутствуют.

### Organizational Structure v1

Реализованы structure/version lifecycle, draft tree, stable elements, document metadata, history, compare, transactions, revisions, CSRF и RBAC.

### Static CI

GitHub Actions workflow `ASU-VCH Static Verification` выполняет:

- PR/push/manual triggers;
- Ubuntu 24.04 / PHP 8.5;
- read-only permissions;
- event-aware `git diff --check`;
- lint tracked PHP;
- 9 CI-safe checker'ов;
- final clean-worktree check.

Post-merge push и manual runs PR #25 завершены SUCCESS. Required status check и branch protection Stage B не включены. CI не заменяет DB/deploy/browser/manual testing.

## Контрольные точки

```text
latest functional PR: #24
latest technical PR: #25
PR #24 merge: feac7230616d3a8df98acb48f43a0b60f89f2255
PR #24 runtime/manual acceptance: b44aed14ee1a54be213cbc939322ba21b02e7a58
PR #25 merge: c567429b3aa4d629a4e7c11fec7e3dbae907d92e
migrations: 12
roles: 4
permissions: 25
themes: 3
required CSS assets: 10
active functional increment: none
active technical increment: none
```

## Не реализовано

- personnel cards и full personal military accounting;
- staffing tables/schedules и personnel assignments;
- общий Documents domain и universal orders workflow;
- общий immutable cross-domain audit log;
- medical, equipment, transport и training operational domains;
- production deployment;
- required GitHub status check / branch protection Stage B;
- arbitrary theme installation и browser CSS/JS editor.

## Testing boundaries

PR #24: automated/runtime, DB, deploy/parity, HTTP smoke, manual desktop и post-merge verification — PASS.

PR #25: exact-head PR workflow, post-merge push run и manual workflow_dispatch — SUCCESS.

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```