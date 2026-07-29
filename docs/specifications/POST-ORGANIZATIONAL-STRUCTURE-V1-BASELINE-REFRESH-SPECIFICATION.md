# Post-Organizational-Structure v1 Baseline Refresh — Specification

## 1. Статус документа

```text
Проект: АСУ-ВЧ
Инкремент: Post-Organizational-Structure v1 Baseline Refresh
Тип: documentation-only
Ветка: docs/post-organizational-structure-v1-baseline-refresh
Architecture commit: a577fa5d0cd68d25a6f3c09b79369178c6f539ef
База: main @ 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
Стадия: Specification
Дата: 2026-07-29
Implementation: NOT STARTED
```

Specification конкретизирует утверждённый scope. Она не разрешает обновление living documentation без завершённого Formal Review и отдельного Approval владельца проекта.

## 2. Цель

Привести living documentation в соответствие с фактическим merged baseline после PR #15 и создать формальный repository audit, сохранив неизменными runtime, schema, test tooling, исторические документы и все Git refs.

## 3. Обязательные исходные данные

Документационная реализация должна использовать следующие проверенные значения:

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
merged main commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
final feature documentation HEAD: dd2586dab7a3b3d8b3683d60e2c7eedce002eb54
last functional PR: #15
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
organizational structure tables: 7
organizational structure triggers: 16
organizational structure permissions: 6
active functional increment: NONE
Organizational Structure v1 mobile acceptance: OUT OF SCOPE / NOT RUN
```

Финальный Organizational Structure Test Report остаётся историческим доказательством и не переписывается.

## 4. Deliverables

### 4.1 Living documentation updates

Обязательно обновляются:

```text
README.md
docs/README.md
docs/PROJECT-STATUS.md
docs/PROJECT.md
docs/ROADMAP.md
docs/CHANGELOG.md
docs/DATABASE-CURRENT.md
docs/ACCESS.md
docs/THEMES.md
docs/ENVIRONMENT.md
docs/LOCAL-RUNBOOK.md
docs/domains/README.md
docs/migrations/README.md
```

### 4.2 Новый audit document

Обязательно создаётся:

```text
docs/REPOSITORY-AUDIT-2026-07-29.md
```

### 4.3 Process documents данного инкремента

Обязательны:

```text
docs/architecture/POST-ORGANIZATIONAL-STRUCTURE-V1-BASELINE-REFRESH-ARCHITECTURE.md
docs/specifications/POST-ORGANIZATIONAL-STRUCTURE-V1-BASELINE-REFRESH-SPECIFICATION.md
docs/reviews/POST-ORGANIZATIONAL-STRUCTURE-V1-BASELINE-REFRESH-REVIEW.md
```

Approval record создаётся только после отдельного явного утверждения Architecture / Specification / Review.

## 5. Общие требования к формулировкам

### 5.1 Текущий и исторический контекст

Любое значение `8 migrations`, `19 permissions`, `2 themes`, `PR #9`, `PR #12` допускается только в явно историческом контексте с датой, PR или прежней контрольной точкой.

Запрещено представлять их как текущий baseline.

### 5.2 Commit terminology

Используются точные термины:

- `merged main commit` — `5aaf0a7...`;
- `tested runtime HEAD` — `2388689...`;
- `final feature documentation HEAD` — `dd2586d...`.

Запрещено утверждать, что merge commit был повторно runtime-протестирован, если такого запуска не было.

### 5.3 Mobile statement

Для Organizational Structure v1 используется только:

```text
mobile testing: OUT OF SCOPE / NOT RUN
mobile PASS: NOT CLAIMED
```

### 5.4 Ветки

В living documentation допускается упоминание завершённых feature/docs branches только как исторических веток.

Ни одна завершённая ветка не называется текущей рабочей веткой.

## 6. Требования по документам

## 6.1 `README.md`

Должен содержать краткий актуальный обзор:

- назначение АСУ-ВЧ;
- текущий стабильный `main`;
- last functional PR #15;
- migrations 001–009;
- 4 роли и 25 permissions;
- 3 встроенные темы;
- реализованный Organizational Structure v1;
- локальная среда и ссылка на runbook;
- предупреждение, что секреты не хранятся в Git;
- mobile testing statement.

Не должен сохранять текущий baseline PR #9, 8 migrations, 19 permissions или 2 themes.

## 6.2 `docs/README.md`

Должен:

- сохранить разделение living / target / historical documentation;
- включить новые каталоги `architecture`, `specifications`, `reviews`, `implementation`;
- включить ссылку на `REPOSITORY-AUDIT-2026-07-29.md`;
- обозначить аудит 2026-07-27 как исторический;
- подтвердить, что `PROJECT-STATUS.md` и `DATABASE-CURRENT.md` описывают текущий baseline;
- сохранить запрет на переписывание исторических process-artifacts.

## 6.3 `docs/PROJECT-STATUS.md`

Должен стать каноническим current-state документом и включать:

```text
Дата актуализации: 2026-07-29
branch: main
merged main commit: 5aaf0a7...
tested runtime HEAD: 2388689...
last functional PR: #15
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

Должен:

- добавить PR #15 в завершённые функциональные PR;
- сохранить PR #10, #11, #13 и #14 как documentation-only;
- описать Organizational Structure v1 как реализованный и принятый;
- перечислить основные возможности structures, versions, tree, documents и history;
- удалить Organizational Structure из раздела «Не реализовано»;
- сохранить реально не реализованные области;
- сослаться на новый repository audit;
- отметить 16 non-main branches как оценённые, но не удалённые;
- указать, что следующий функциональный инкремент не выбран.

## 6.4 `docs/PROJECT.md`

Должен:

- описать актуальное назначение проекта;
- обновить реализованное состояние Security, Themes, Directories и Organization;
- указать 9 migrations, 25 permissions и 3 themes;
- зафиксировать текущую контрольную точку PR #15;
- убрать фактическую organizational structure из списка отсутствующих функций;
- сохранить ограничения по реальным закрытым сведениям;
- сохранить documentation-first и отдельный merge approval.

## 6.5 `docs/ROADMAP.md`

Должен:

- добавить Organizational Structure v1 в завершённые этапы;
- зафиксировать завершение PR #15 и всех UI Polish 1–4;
- убрать конкретную organizational structure из списка возможного следующего инкремента;
- оставить будущими: personnel cards, positions/assignments, general documents/orders, audit domain, production/CI и отдельное mobile testing;
- указать, что новый функциональный инкремент не выбран;
- сохранить полный mandatory workflow.

## 6.6 `docs/CHANGELOG.md`

В начале добавляется раздел `2026-07-29`, содержащий:

- merge PR #15;
- merge commit `5aaf0a7...`;
- migration 009;
- 7 tables и 16 triggers;
- 6 новых permissions и итоговые 25;
- structures/version lifecycle;
- editable draft tree;
- document links и history;
- UI Polish 1–4;
- automated PASS и manual desktop acceptance PASS;
- mobile out of scope;
- repository audit и предстоящий baseline refresh как docs-only процесс.

Исторические записи 2026-07-25–2026-07-28 сохраняются.

## 6.7 `docs/DATABASE-CURRENT.md`

Должен:

- обновить дату проверки;
- добавить migration 009 в таблицу migrations;
- изменить installer baseline на 9 migrations;
- добавить раздел Organizational Structure v1;
- перечислить 7 таблиц:

```text
organizational_structures
organizational_structure_elements
organizational_structure_versions
organizational_structure_documents
organizational_structure_version_documents
organizational_structure_nodes
organizational_structure_change_events
```

- описать 16 triggers как DB-level guards;
- указать итоговые 25 permissions;
- описать lifecycle versions и immutable history в пределах фактической реализации;
- отметить catalog-version binding;
- убрать concrete structures/tree/documents из раздела «Не реализовано»;
- сохранить не реализованные personnel, positions, assignments и общий Documents runtime;
- сохранить backup policy.

Документ не должен придумывать таблицы или функции сверх migration 009 и merged runtime.

## 6.8 `docs/ACCESS.md`

Должен:

- обновить system permissions до 25;
- сохранить 4 роли и owner wildcard;
- добавить полный список:

```text
organization.structures.view
organization.structures.create
organization.structures.update
organization.structures.publish
organization.structures.archive
organization.structures.history
```

- описать, что новые permissions не назначаются автоматически administrator/operator/viewer;
- сохранить абсолютный доступ owner через `system.*.*`;
- описать соответствие действий permissions;
- сохранить authentication, CSRF, validation, transaction и revision checks;
- не утверждать наличие отдельной role model Organization.

## 6.9 `docs/THEMES.md`

Должен:

- описывать все три темы как merged built-in themes;
- убрать формулировки об ожидании merge/acceptance темы «Евгения Ростова»;
- расширить общий CSS contract до восьми файлов, добавив:

```text
css/organization.css
```

- сохранить четыре обязательных SVG для `asu-evgeniya-rostova`;
- описать desktop acceptance Organizational Structure во всех трёх темах;
- сохранить default/fallback `asu-blue`;
- сохранить отсутствие arbitrary theme upload/editor и theme-specific JS;
- не заявлять mobile acceptance.

## 6.10 `docs/ENVIRONMENT.md`

Должен:

- описать поддерживаемую локальную среду Windows 10/11;
- указать фактически использованный baseline:

```text
Open Server Panel 6.5.1
Apache
PHP 8.5.4
MySQL 8.4.x
Windows PowerShell 5.1
```

- сохранить пути clone/deploy/web root;
- обновить installer на migrations 001–009;
- добавить `organization.css` к тематическим assets;
- указать актуальный Organizational Structure runner;
- сохранить `config/local.php` protection;
- не раскрывать секреты.

Версия MySQL может быть записана как `8.4` либо точная фактически подтверждённая patch version при наличии доказательства. Документы не должны противоречить друг другу.

## 6.11 `docs/LOCAL-RUNBOOK.md`

Должен быть переработан из increment-specific runbook темы в current-baseline runbook.

Обязательно:

- stable sync только через `main`;
- никаких завершённых feature branches как активного примера;
- ожидаемые 9 migrations;
- ожидаемые 25 permissions;
- три темы;
- полный актуальный smoke/regression набор;
- `tools/Test-OrganizationalStructureV1.ps1` как проверенный комплексный runner;
- указание `-AllowInvalidCertificate` для локального self-signed HTTPS;
- сохранение backup и `config/local.php` rules;
- mobile out-of-scope statement.

Известный technical debt старых direct checker'ов с exact `19` фиксируется явно:

- текущий полный runner применяет compatibility adapter;
- прямой запуск отдельных legacy checker'ов может быть несовместим с 25 permissions;
- исправление checker source выделено в отдельный будущий технический инкремент;
- runbook не должен предлагать заведомо неверный direct sequence как основной baseline test.

## 6.12 `docs/domains/README.md`

Должен:

- обновить функциональные PR до #15;
- изменить Organization status на частично реализованный runtime;
- описать фактический scope Organizational Structure v1;
- сохранить отсутствие personnel, staffing positions и assignments;
- уточнить, что Organization использует Reference catalog;
- не объявлять общий Documents domain реализованным только из-за metadata documents внутри Organization;
- сохранить целевые зависимости и documentation-first порядок.

## 6.13 `docs/migrations/README.md`

Должен:

- добавить migration 009;
- указать её назначение;
- обновить текущую нумерацию на 001–009;
- связать migration 009 с approved Organizational Structure design/migration specification;
- сохранить общие правила idempotency, backup и MySQL 8.4;
- не изменять target migration specifications.

## 7. Требования к `docs/REPOSITORY-AUDIT-2026-07-29.md`

Документ должен иметь следующие разделы.

### 7.1 Audit metadata

```text
date: 2026-07-29
repository: ClaytonKinnane/ASU-VCH
main: 5aaf0a7...
local main divergence: 0/0
working tree: clean
open PR: 0
```

### 7.2 Repository content findings

- runtime соответствует merged Organizational Structure v1;
- historical process documents сохранены;
- living documentation выявлена как устаревшая до refresh;
- legacy checker exact-count debt выделен отдельно.

### 7.3 Полный branch inventory

Таблица должна включать все 17 веток:

1. `main`;
2. `feature/initial-site`;
3. `feature/required-password-change`;
4. `feature/user-rejection-audit`;
5. `feature/user-archive-restore`;
6. `feature/theme-asu-light-blue`;
7. `feature/asu-blue-tile-hover`;
8. `feature/directories-landing`;
9. `feature/military-ranks-directory`;
10. `feature/organizational-element-types-directory`;
11. `docs/project-documentation-audit-2026-07-27`;
12. `docs/fix-project-status-audit-state`;
13. `docs/evgeniya-rostova-theme-v1-design`;
14. `feature/theme-evgeniya-rostova`;
15. `docs/evgeniya-rostova-theme-v1-post-merge-status`;
16. `docs/runtime-baseline-self-reference-fix`;
17. `feature/organizational-structure-v1`.

Для каждой указываются:

- тип;
- связанный PR либо отсутствие PR;
- merged/content state;
- unique commit assessment;
- классификация `KEEP` или `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL`.

### 7.4 Special branch proof

Для `docs/evgeniya-rostova-theme-v1-design` обязательно фиксируются:

```text
branch HEAD: 988d803f5659d9d9bf4b23fc24ee83dc0faf4fd1
unique commits: 2
behind main: 116
```

Файл 1:

```text
docs/design/EVGENIYA-ROSTOVA-THEME-V1-DESIGN.md
blob: 709e6fb6896425c5f377e801f379fcb66eb4623f
size: 38901 bytes
byte-identical: true
```

Файл 2:

```text
docs/design/EVGENIYA-ROSTOVA-THEME-V1-REVIEW.md
blob: e19229a50ee10ee8ed1d7496896d73baee6d08f0
size: 24113 bytes
byte-identical: true
```

Финальный локальный маркер:

```text
BYTEWISE_DOCUMENT_COMPARISON_STATUS=PASS
```

### 7.5 Cleanup conclusion

Audit должен использовать точную формулировку:

```text
Все 16 non-main веток признаны технически безопасными для удаления без потери файлового содержимого проекта.
Фактическое удаление не выполнялось.
Удаление требует отдельного явного разрешения владельца проекта после завершения baseline refresh.
```

Запрещено писать, что branch cleanup уже завершён.

## 8. Запрещённые изменения

В diff не допускаются:

```text
app/**
config/**
database/**
deploy/**
public/**
themes/**
tools/**
```

Также запрещены:

- удаление или перемещение существующих исторических документов;
- переименование migrations;
- исправление checker source;
- изменение Git refs;
- создание tags/releases;
- branch deletion;
- изменение PR;
- merge.

## 9. Validation requirements

### 9.1 Git scope

Относительно `5aaf0a7...` changed paths должны быть только `README.md` и `docs/**`.

### 9.2 Required value scan

В living documents должны присутствовать:

```text
5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
238868950c5f7417ea3d1c283610f2d282d4395a
PR #15
001–009
25
3 themes / три темы
Organizational Structure v1
```

### 9.3 Obsolete claim scan

Не должно оставаться current-state утверждений:

```text
last functional PR: #9
last functional PR: #12
migrations: 001–008
system permissions: 19
built-in themes: 2
active branch: feature/theme-evgeniya-rostova
organizational structure: not implemented
```

Исторические строки не считаются ошибкой при явном контексте.

### 9.4 Markdown links

Все относительные `.md` ссылки должны разрешаться в существующие repository paths.

### 9.5 Secret scan

Запрещены реальные значения:

- password;
- database credentials;
- cookies/session IDs;
- local.php content;
- персональные данные.

### 9.6 No runtime test claim

Validation report должен явно указать:

```text
Documentation-only validation performed.
Runtime/deploy/database were not changed and were not re-tested by this increment.
```

## 10. Acceptance criteria

Инкремент готов к PR только если:

- все 13 living documents обновлены;
- repository audit создан;
- canonical values совпадают;
- historical artifacts не изменены;
- runtime/tooling diff отсутствует;
- Markdown links проходят;
- secret scan проходит;
- obsolete current claims устранены;
- ветки не удалены;
- branch cleanup остаётся отдельным gate;
- checker cleanup остаётся отдельным инкрементом;
- documentation validation report зафиксирован.

## 11. Testing classification

```text
PHP lint: NOT REQUIRED by documentation-only diff
SQL test: NOT REQUIRED
Deploy: NOT REQUIRED
DB backup: NOT REQUIRED
HTTP smoke: NOT REQUIRED
Desktop browser acceptance: NOT REQUIRED
Mobile testing: NOT REQUIRED / NOT RUN
Documentation consistency validation: REQUIRED
Git scope validation: REQUIRED
Branch inventory validation: REQUIRED
```

Если фактический diff затронет runtime или tooling, данная классификация становится недействительной, а инкремент должен быть остановлен и перепроектирован.

## 12. Gate

```text
SPECIFICATION STATUS: READY FOR FORMAL REVIEW
IMPLEMENTATION STATUS: NOT STARTED
APPROVAL REQUIRED BEFORE LIVING DOCUMENTATION UPDATE
```
