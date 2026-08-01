# Specification — Post-PR20 Baseline Refresh

## Статус

```text
DATE: 2026-08-01
VERSION: 0.1
STATUS: PROPOSED
BASELINE: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
BRANCH: docs/post-pr20-baseline-refresh
CLASSIFICATION: DOCUMENTATION ONLY
```

## 1. Назначение

Актуализировать текущую документацию АСУ-ВЧ после merge функциональных PR #19 и #20, не изменяя runtime, database, deploy, themes, tools или Git refs.

## 2. Exact changed-path allowlist

Финальный инкремент должен содержать ровно 22 пути:

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/DEVELOPMENT.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/DATABASE-CURRENT.md
docs/THEMES.md
docs/ACCESS.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/ARCHITECTURAL-PATTERNS.md
docs/architecture/POST-PR20-BASELINE-REFRESH-ARCHITECTURE.md
docs/specification/POST-PR20-BASELINE-REFRESH-SPECIFICATION.md
docs/review/POST-PR20-BASELINE-REFRESH-FORMAL-REVIEW.md
docs/decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md
docs/implementation/POST-PR20-BASELINE-REFRESH-IMPLEMENTATION.md
docs/testing/POST-PR20-BASELINE-REFRESH-VALIDATION.md
docs/implementation/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-IMPLEMENTATION.md
docs/testing/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-LOCAL-RUNBOOK.md
docs/review/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-FORMAL-REVIEW.md
```

Любой дополнительный путь требует отдельного Review и Approval.

## 3. Required baseline facts

Living documentation должна согласованно отражать:

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
repository pointer: origin/main
refresh baseline / PR #20 merge: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #19: MERGED
PR #19 merge: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #19 final feature head: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
PR #20: MERGED
PR #20 merge: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #20 final feature head: bea147505a85010b61fe938eb07ec474d76cdab5
latest functional PR: #20
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

Exact current `main` не должен храниться как самореферентное постоянно актуальное поле; `3082ec6...` указывается как исторический refresh/merge anchor.

## 4. Living-document requirements

### 4.1 `README.md`

Обновить:

- перечень реализованных справочников;
- latest functional PR и merge/test anchors;
- migrations 001–011;
- theme CSS contract до девяти assets;
- ссылку на новый validation/implementation record при необходимости;
- границы тестирования.

### 4.2 `docs/README.md`

Обновить индекс и правила актуальности:

- добавить post-PR20 refresh artifacts;
- исправить фактические каталоги `architecture`, `specification`, `review`;
- сохранить правило, что исторические artifacts не переписываются задним числом;
- описать post-merge closure/addendum pattern.

### 4.3 `docs/PROJECT-STATUS.md`

Сделать каноническим текущим статусом:

- дата 2026-08-01;
- PR #19/#20 merged;
- latest functional baseline PR #20;
- migrations 001–011;
- оба новых directory domains;
- latest tested runtime HEAD PR #20;
- current branch inventory только как датированный snapshot либо динамическая команда;
- branch cleanup pending separate gate.

### 4.4 `docs/PROJECT.md`

Дополнить реализованное состояние:

- Military Positions Directory v1;
- Public Military Occupational Specialties v1;
- публичные/нормативные границы данных;
- отсутствие automatic relations и personal data;
- обновлённые anchors и testing boundaries.

### 4.5 `docs/DEVELOPMENT.md`

Синхронизировать обязательный процесс:

```text
Research → Analysis → Architecture → Specification → Review → Approval
→ Implementation → Testing → Commit → Push → Pull Request
→ Final PR Review → separate merge approval → Merge
→ post-merge verification → separate branch deletion approval
```

Явно разделить technical deletion safety и фактическое разрешение на удаление.

### 4.6 `docs/ENVIRONMENT.md`

Обновить:

- installer до migrations 001–011 / applied migrations 11;
- девять CSS-assets темы;
- актуальные профильные runners PR #19/#20;
- минимальный regression/HTTP smoke contract;
- latest tested runtime classification.

### 4.7 `docs/LOCAL-RUNBOOK.md`

Обновить stable baseline runbook:

- anchors PR #19/#20;
- installer expected count 11;
- профильные runners для positions и VUS;
- latest full runtime evidence;
- fresh branch inventory и cleanup gates;
- отсутствие обязательного повторного runtime testing для docs-only refresh.

### 4.8 `docs/DATABASE-CURRENT.md`

Обновить schema baseline:

- migrations 010 и 011 в таблице;
- отдельные разделы обоих каталогов;
- подтверждённые table/trigger/seed counts;
- permissions остаются 25;
- compatibility loader/hash packaging;
- отсутствие forbidden relation tables.

### 4.9 `docs/THEMES.md`

Обновить CSS contract с 8 до 9 assets, добавив:

```text
css/military-occupational-specialties.css
```

Зафиксировать desktop acceptance обоих новых каталогов во всех трёх темах. Mobile PASS не заявлять.

### 4.10 `docs/ACCESS.md`

Добавить owner-only/read-only directory boundary:

- оба маршрута используют `system.*.*`;
- ordinary roles получают HTTP 403 без дополнительных permissions;
- permissions остаются 25;
- mutation endpoints отсутствуют.

### 4.11 `docs/ROADMAP.md`

Отметить завершёнными PR #19/#20, migrations 010/011, testing, acceptance, Final Review, merge и post-merge verification. Следующий functional increment оставить невыбранным. Documentation refresh и последующий branch cleanup показать отдельными этапами.

### 4.12 `docs/CHANGELOG.md`

Добавить секцию 2026-08-01:

- Military Positions Directory v1;
- Public Military Occupational Specialties v1;
- оба Automated/Manual PASS;
- Final PR remediation и targeted recheck PR #20;
- merge commits #19/#20;
- post-merge verification;
- начало documentation refresh;
- ветки не удалялись.

### 4.13 `docs/ARCHITECTURAL-PATTERNS.md`

Дополнить project-wide workflow стадиями Research/Analysis, Final PR Review, post-merge verification и separate branch deletion approval. Зафиксировать pattern для source-centric immutable public catalogs и compatibility-packaged large migrations без превращения конкретного implementation в универсальное правило.

## 5. VUS current-state closure requirements

### 5.1 Implementation record

В `PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-IMPLEMENTATION.md`:

- обновить верхний current status на merged/post-merge verified;
- сохранить все исторические попытки и markers;
- добавить Post-Merge Closure с merge commit `3082ec6...`;
- отметить latest tested runtime `9db06c4...`;
- указать, что последующие evidence commits были docs-only;
- закрыть прежний next gate;
- branch deletion оставить pending separate approval.

### 5.2 Increment local runbook

В `PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-LOCAL-RUNBOOK.md`:

- пометить документ как historical increment runbook;
- добавить post-merge note и ссылку на общий `docs/LOCAL-RUNBOOK.md` для stable baseline;
- не переписывать исходные команды/ожидания, относящиеся к pre-merge testing.

### 5.3 Formal review record

В `PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-FORMAL-REVIEW.md`:

- сохранить оба review attempts;
- добавить Post-Merge Verification Closure;
- зафиксировать PR merged и branch preserved;
- не изображать merge как часть review verdict задним числом.

Датированный Manual Desktop Acceptance evidence не изменяется.

## 6. New process artifacts

После Approval создать:

- `docs/decisions/POST-PR20-BASELINE-REFRESH-APPROVAL.md`;
- `docs/implementation/POST-PR20-BASELINE-REFRESH-IMPLEMENTATION.md`;
- `docs/testing/POST-PR20-BASELINE-REFRESH-VALIDATION.md`.

Implementation record должен перечислить exact changed paths, фактический head, diff classification и результаты validation.

## 7. Validation requirements

Обязательные проверки:

1. branch основана на `3082ec6...`;
2. merge-base с `main` равен `3082ec6...` до возможных новых main changes;
3. exact changed-path set = 22;
4. все changed files имеют расширение `.md`;
5. `git diff --check` PASS;
6. runtime/config/database/themes/tools diff отсутствует;
7. living docs не содержат current-state утверждений `last functional PR #15`, `migrations 001–009`, `applied migrations 9`;
8. PR #19/#20 anchors согласованы;
9. migrations 010/011 и counts согласованы с evidence;
10. roles 4 / permissions 25 / themes 3 согласованы;
11. theme CSS contract = 9;
12. relative Markdown links разрешаются;
13. secret scan PASS;
14. historical evidence sections сохранены;
15. branch deletion не выполнялась;
16. mobile PASS не заявляется.

## 8. Test classification

```text
PHP LINT: NOT REQUIRED
SQL / INSTALLER: NOT REQUIRED
DEPLOY: NOT REQUIRED
DATABASE TESTING: NOT REQUIRED
HTTP / BROWSER: NOT REQUIRED
DOCUMENTATION VALIDATION: REQUIRED
MOBILE TESTING: OUT OF SCOPE / NOT RUN
```

Основание: финальный diff должен быть Markdown-only и не менять runtime behavior.

## 9. Branch cleanup boundary

Настоящий инкремент не удаляет ветки.

После его merge требуется:

1. fresh remote/local inventory;
2. подтверждение отсутствия unique commits;
3. exact cleanup set;
4. отдельное явное owner approval;
5. сначала удаление remote branches на GitHub;
6. `fetch --prune`;
7. затем безопасное локальное удаление через `git branch -d`;
8. terminal verification.

## 10. Implementation gate

Реализация разрешена только после Formal Review PASS и отдельного явного Approval владельца проекта.
