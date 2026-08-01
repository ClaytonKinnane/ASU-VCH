# Architecture — Post-PR20 Baseline Refresh

## Статус

```text
DATE: 2026-08-01
STATUS: PROPOSED
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
RUNTIME_CHANGE: NONE
DATABASE_CHANGE: NONE
BRANCH_DELETION: OUT OF SCOPE
```

## Контекст

После merge PR #19 и PR #20 стабильный `main` содержит два новых owner-only read-only справочника:

- PR #19 — «Типовые воинские должности», migration 010;
- PR #20 — «Публичные сведения о военно-учётных специальностях», migration 011.

Текущая living documentation по-прежнему в основном описывает baseline после PR #15: migrations 001–009, последний functional PR #15 и tested runtime HEAD Organizational Structure v1. Несколько текущих VUS-документов также сохраняют формулировки `PR OPEN`, `MERGE NOT AUTHORIZED` и `TARGETED RECHECK REQUIRED`, хотя все соответствующие gates завершены и PR #20 merged.

## Цель

Создать documentation-only baseline refresh, который:

1. синхронизирует living documentation с `main` после PR #19/#20;
2. разделяет текущие baseline markers и исторические process artifacts;
3. фиксирует проверенные runtime anchors без заявления, что documentation-only commits были runtime-протестированы;
4. отражает migrations 001–011, неизменные 4 роли и 25 permissions;
5. документирует оба новых справочника и theme asset contract;
6. закрывает текущие VUS-статусы post-merge addendum'ами;
7. сохраняет branch cleanup как отдельный последующий административный gate.

## Источники истины

Приоритет фактов:

1. merged code и migrations в `main`;
2. metadata merged PR #19/#20;
3. профильные Automated Testing и Manual Desktop Acceptance evidence;
4. Final PR Review и post-merge verification evidence;
5. living documentation после настоящего refresh;
6. исторические Architecture/Specification/Review/Approval/Test artifacts.

Текущий `main` определяется динамически через `origin/main`. Exact SHA используется только как исторический anchor настоящего refresh:

```text
PR #19 merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #20 merge commit / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #19 tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #19 final feature HEAD: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
PR #20 tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #20 final feature HEAD: bea147505a85010b61fe938eb07ec474d76cdab5
```

## Классы документации

### Living documentation

Living documents описывают текущее merged состояние и обновляются настоящим инкрементом:

- root README;
- documentation index;
- project/status/environment/runbook/database/themes/access/roadmap/changelog;
- project-wide architectural patterns.

### Исторические artifacts

Architecture, Specification, Review, Approval и датированные Test Evidence сохраняют состояние соответствующего gate. Прежние формулировки `NOT CREATED`, `NOT AUTHORIZED` или `RECHECK REQUIRED` внутри датированного события не переписываются как будто они никогда не существовали.

### Current-state sections внутри increment records

Если increment document одновременно содержит историю попыток и явно текущий верхнеуровневый статус/следующий gate, обновляется только current-state framing и добавляется post-merge closure. Исторические попытки и исходные markers остаются неизменными.

## Целевая baseline-модель

```text
repository pointer: origin/main
latest functional PR: #20
previous functional PR: #19
latest functional merge anchor: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
latest tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
active documentation increment: Post-PR20 Baseline Refresh
mobile testing: OUT OF SCOPE / NOT RUN
```

## Новые функциональные области baseline

### Military Positions Directory v1

- migration 010;
- 14 tables и 41 triggers;
- 1 published catalog version;
- 4 version sources, 24 source entries, 28 source-entry evidence records;
- 4 families, 34 canonical types, 35 variants;
- composition и organizational context evidence;
- 0 automatic military-rank relation tables;
- owner-only GET route `/admin/directories/military-positions.php`.

### Public Military Occupational Specialties v1

- migration 011;
- 9 tables и 26 triggers;
- 1 published version;
- 5 legal sources и 4 official source snapshots;
- 3 code segments, 6 public context domains, 3 personnel scopes;
- 2 normative direct disclosures;
- 4 training organizations и 15 training programs;
- 17 searchable records;
- отсутствие relations к positions, ranks, equipment и personal data;
- owner-only GET route `/admin/directories/military-occupational-specialties.php`.

## Theme contract

После PR #20 каждая тема содержит девять обязательных CSS-assets:

```text
css/theme.css
css/auth.css
css/account.css
css/users.css
css/theme-management.css
css/directories.css
css/military-occupational-specialties.css
css/organization.css
css/operation-result-modal.css
```

Тема `asu-evgeniya-rostova` дополнительно сохраняет четыре обязательных SVG-assets.

## Branch governance

На момент Research после создания настоящей docs-ветки GitHub содержит:

```text
main
docs/post-pr20-baseline-refresh
feature/military-positions-directory
feature/public-military-occupational-specialties-directory
```

Обе feature-ветки технически подтверждены как полностью merged в `main`, но их удаление не входит в настоящий documentation increment. После merge документационного PR требуется fresh inventory, отдельный exact cleanup set и отдельное явное owner approval. Активная docs-ветка не может входить в cleanup до собственного merge и отдельного разрешения.

Локальный audit пользователя подтвердил 13 local feature branches в `git branch --merged origin/main`; локальное удаление также остаётся отдельным последующим scope.

## Validation architecture

Documentation validation должна подтвердить:

- exact changed-path allowlist;
- Markdown-only diff;
- отсутствие runtime/config/database/theme/tool изменений;
- отсутствие stale current-state markers в living docs;
- migrations 001–011;
- PR #19/#20 merged anchors;
- latest tested runtime HEAD `9db06c4...`;
- 4 roles / 25 permissions / 3 themes;
- девять обязательных CSS-assets;
- корректные relative links;
- отсутствие секретов и содержимого `config/local.php`;
- сохранность исторических evidence sections;
- отсутствие Mobile PASS claim;
- branch cleanup not performed / separately gated.

Runtime, deploy, installer, database и browser testing не требуются, поскольку implementation ограничена Markdown.

## Ограничения

- Не изменять runtime, migrations, seed, themes, tools или Git refs.
- Не удалять remote или local branches.
- Не переписывать исторические test results задним числом.
- Не заявлять post-merge local runtime smoke, если он не выполнялся.
- Не заявлять mobile testing PASS.
- Не создавать PR и не выполнять merge без отдельных разрешений.

## Gate

Переход к реализации living-document updates допускается только после Specification, Formal Review и отдельного явного Approval владельца проекта.
