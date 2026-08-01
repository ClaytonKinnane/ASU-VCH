# Architecture — Post-PR20 Baseline Refresh

## Статус

```text
DATE: 2026-08-01
STATUS: APPROVED / IMPLEMENTED / PR OPEN
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
PR: #21 OPEN
RUNTIME_CHANGE: NONE
DATABASE_CHANGE: NONE
BRANCH_DELETION: OUT OF SCOPE
```

## Контекст

После merge PR #19 и PR #20 стабильный `main` содержит два новых owner-only read-only справочника:

- PR #19 — «Типовые воинские должности», migration 010;
- PR #20 — «Публичные сведения о военно-учётных специальностях», migration 011.

Living documentation до настоящего инкремента в основном описывала baseline после PR #15. Первый Final PR Review PR #21 дополнительно установил, что current operational records должны быть закрыты post-merge не только для PR #20, но и для PR #19.

## Цель

Documentation-only refresh:

1. синхронизирует living documentation с merged PR #19/#20;
2. отражает migrations 001–011, 4 роли, 25 permissions и 3 темы;
3. фиксирует проверенные runtime anchors без заявления, что documentation-only commits были runtime-протестированы;
4. разделяет living current state и historical process/test artifacts;
5. добавляет post-merge closure для operational records PR #19 и PR #20;
6. оставляет branch cleanup отдельным post-merge административным workflow.

## Источники истины

Приоритет:

1. merged code и migrations в `main`;
2. metadata merged PR #19/#20;
3. Automated Testing и Manual Desktop Acceptance evidence;
4. Final PR Review и post-merge verification evidence;
5. living documentation после refresh;
6. historical Architecture/Specification/Review/Approval/Test artifacts.

```text
PR #19 merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #19 final feature HEAD: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
PR #20 merge / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #20 final feature HEAD: bea147505a85010b61fe938eb07ec474d76cdab5
```

Current `main` определяется динамически через `origin/main`; exact SHA выше являются historical anchors.

## Классы документации

### Living documentation

Описывает текущий merged baseline и обновляется после material merge.

### Historical artifacts

Architecture, Specification, Review, Approval и датированные Test Evidence сохраняют состояние соответствующего gate. Исторические `NOT CREATED`, `NOT AUTHORIZED` и `RECHECK REQUIRED` не переписываются как будто их не существовало.

### Operational increment records

Содержат current-status framing и историческую хронологию. Post-merge closure обновляет current framing, не удаляя историю попыток.

## Целевая baseline-модель

```text
repository pointer: origin/main
latest functional PR: #20
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
active documentation increment: Post-PR20 Baseline Refresh
active documentation PR: #21
mobile testing: OUT OF SCOPE / NOT RUN
```

## Functional baseline

### Military Positions Directory v1

- migration 010;
- 14 tables, 41 triggers;
- 34 canonical types, 35 variants;
- no automatic military-rank relations;
- owner-only GET route `/admin/directories/military-positions.php`.

### Public Military Occupational Specialties v1

- migration 011;
- 9 tables, 26 triggers;
- 5 legal sources, 4 official snapshots;
- 4 training organizations, 15 training programs;
- 17 searchable records;
- no relations to positions, ranks, equipment or personal data;
- owner-only GET route `/admin/directories/military-occupational-specialties.php`.

## Theme contract

Каждая тема содержит девять обязательных CSS-assets, включая:

```text
css/military-occupational-specialties.css
css/organization.css
```

## Scope architecture

После Final PR Review PR #21 exact allowlist расширен с 22 до 25 Markdown-путей. Добавлены operational records PR #19:

```text
docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md
docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-LOCAL-RUNBOOK.md
docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md
```

Runtime, config, database, migrations, themes, tools и Git refs исключены.

## Branch governance

Создание PR #21 не разрешает merge. Branch cleanup выполняется только после merge refresh, post-merge verification, fresh inventory, exact cleanup batch и отдельного owner approval.

## Validation architecture

Validation подтверждает:

- exact allowlist 25;
- Markdown-only diff;
- отсутствие runtime/config/database/theme/tool изменений;
- корректные PR #19/#20 anchors;
- migrations 001–011;
- living/current-state consistency с PR #21;
- post-merge closures PR #19/#20;
- relative links и secret scan;
- отсутствие Mobile PASS claim;
- отсутствие merge и branch deletion.

## Ограничения

- Runtime/deploy/database retest не требуется для Markdown-only diff.
- Merge PR #21 требует отдельного разрешения после Final PR Review PASS.
- Remote и local ветки не удаляются в этом инкременте.
