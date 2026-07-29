# Post-Organizational-Structure v1 Baseline Refresh — Architecture

## 1. Статус документа

```text
Проект: АСУ-ВЧ
Инкремент: Post-Organizational-Structure v1 Baseline Refresh
Тип: documentation-only
Ветка: docs/post-organizational-structure-v1-baseline-refresh
База: main @ 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
Стадия: Architecture
Дата: 2026-07-29
Реализация living documentation: не начата
Runtime / DB / checker changes: запрещены данным инкрементом
```

Документ подготовлен после утверждения владельцем проекта scope документационного инкремента.

Переход к обновлению living documentation запрещён до завершения Specification, Formal Review и отдельного явного Approval.

## 2. Контекст

PR #15 `feat(organization): add organizational structure v1` объединён в `main` merge commit:

```text
5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
```

Протестированный runtime HEAD инкремента:

```text
238868950c5f7417ea3d1c283610f2d282d4395a
```

Фактический merged baseline содержит:

- migrations `001–009`;
- 4 системные роли;
- 25 системных permissions;
- 3 встроенные темы;
- Organizational Structure v1;
- 7 таблиц organizational structure;
- 16 защитных triggers;
- 6 permissions `organization.structures.*`;
- принятый desktop-интерфейс во всех трёх темах;
- автоматическое и ручное тестирование со статусом PASS;
- mobile acceptance вне scope и не выполнялась.

При этом значительная часть living documentation продолжает описывать baseline до PR #15: migrations `001–008`, 19 permissions, отсутствие runtime организационной структуры и более ранние PR.

## 3. Проблема

Репозиторий содержит три разных класса документации:

1. **Living documentation** — должна описывать актуальный merged baseline.
2. **Исторические process-artifacts** — фиксируют состояние конкретного gate и не переписываются задним числом.
3. **Целевая архитектура** — может описывать модель шире текущей реализации и не является доказательством наличия runtime.

Текущее рассогласование возникло в living documentation. Изменение исторических документов для устранения такого рассогласования архитектурно неверно, поскольку уничтожило бы процессную историю.

Дополнительно выполнен аудит веток репозитория:

- `main` — стабильная default branch;
- 16 non-main веток;
- 15 веток не имеют уникальных commits относительно `main`;
- `docs/evgeniya-rostova-theme-v1-design` имеет 2 уникальных commits в графе, но оба содержащихся Markdown-файла побайтово идентичны файлам в `main`;
- открытых Pull Request нет;
- удаление веток не входит в данный инкремент и требует отдельного явного разрешения.

## 4. Архитектурная цель

Создать единый, проверяемый и непротиворечивый living baseline после Organizational Structure v1, не изменяя runtime и не переписывая исторические документы.

Результат должен позволять однозначно ответить:

- какой commit является текущим merged `main`;
- какой runtime HEAD был протестирован;
- какие PR завершены;
- какие migrations применяются;
- сколько ролей и permissions реализовано;
- какие темы входят в baseline;
- что именно реализовано в Organization;
- какие ограничения сохраняются;
- какие ветки существуют и почему их удаление признано технически безопасным;
- какие действия ещё требуют отдельного gate.

## 5. Выбранное архитектурное решение

### 5.1 Отдельный docs-only инкремент

Все изменения выполняются в ветке:

```text
docs/post-organizational-structure-v1-baseline-refresh
```

Ветка создана непосредственно от:

```text
main @ 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
```

Инкремент не смешивается с техническим долгом checker-файлов и не включает branch cleanup.

### 5.2 Источник истины

При обновлении living documentation используется следующий приоритет:

1. merged содержимое `main`;
2. исполняемые migrations в `database/migrations`;
3. runtime registry и source code;
4. профильные integration checker'ы и финальный Test Report;
5. merged PR metadata;
6. локальные read-only Git-проверки, предоставленные владельцем проекта.

Исторические design/review/test-attempt документы не используются как текущий baseline без проверки их итогового lifecycle.

### 5.3 Разделение commit-идентификаторов

Документация различает:

```text
merged main commit:
5aaf0a7aca51cae575b3765309b2bf3ad7d76d28

tested runtime HEAD:
238868950c5f7417ea3d1c283610f2d282d4395a

final feature documentation HEAD before merge:
dd2586dab7a3b3d8b3683d60e2c7eedce002eb54
```

`merged main commit` описывает состояние ветки после merge.

`tested runtime HEAD` фиксирует точный commit, на котором выполнялся полный runtime/deploy/test cycle.

Поздние documentation-only commits и merge commit не объявляются повторно протестированным runtime без фактического запуска.

### 5.4 Классификация документов

#### Living documentation

Подлежит обновлению при изменении baseline:

- `README.md`;
- `docs/README.md`;
- `docs/PROJECT-STATUS.md`;
- `docs/PROJECT.md`;
- `docs/ROADMAP.md`;
- `docs/CHANGELOG.md`;
- `docs/DATABASE-CURRENT.md`;
- `docs/ACCESS.md`;
- `docs/THEMES.md`;
- `docs/ENVIRONMENT.md`;
- `docs/LOCAL-RUNBOOK.md`;
- `docs/domains/README.md`;
- `docs/migrations/README.md`.

#### Новый audit artifact

Добавляется:

```text
docs/REPOSITORY-AUDIT-2026-07-29.md
```

Он фиксирует:

- проверенный baseline;
- выявленные документальные расхождения;
- inventory всех 17 веток, включая `main`;
- классификацию 16 non-main веток;
- отдельную побайтовую проверку двух файлов ветки `docs/evgeniya-rostova-theme-v1-design`;
- запрет фактического удаления веток без отдельного Approval;
- технический долг старых checker-файлов как отдельный будущий инкремент.

#### Исторические документы

Не изменяются:

- Architecture / Specification / Review завершённых инкрементов;
- Approval records;
- Implementation records;
- Test Attempts;
- Manual Acceptance records;
- Final Test Reports;
- PR Final Reviews;
- исторические audit documents.

Их промежуточные формулировки отражают состояние соответствующего gate и не являются ошибкой living baseline.

#### Целевая архитектура

Не переписывается только ради отражения текущего runtime. При необходимости в индексах явно сохраняется разделение между target model и implemented baseline.

## 6. Канонический baseline после refresh

Все living documents должны согласованно отражать:

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
merged main commit: 5aaf0a7aca51cae575b3765309b2bf3ad7d76d28
tested runtime HEAD: 238868950c5f7417ea3d1c283610f2d282d4395a
last functional PR: #15
migrations: 001–009
system roles: 4
system permissions: 25
built-in themes: 3
organizational structure v1: IMPLEMENTED / TESTED / ACCEPTED / MERGED
active functional increment: NONE
mobile acceptance for Organizational Structure v1: OUT OF SCOPE / NOT RUN
```

## 7. Архитектура repository audit

### 7.1 Полный inventory

Audit перечисляет все ветки, показанные GitHub:

```text
main
feature/initial-site
feature/required-password-change
feature/user-rejection-audit
feature/user-archive-restore
feature/theme-asu-light-blue
feature/asu-blue-tile-hover
feature/directories-landing
feature/military-ranks-directory
feature/organizational-element-types-directory
docs/project-documentation-audit-2026-07-27
docs/fix-project-status-audit-state
docs/evgeniya-rostova-theme-v1-design
feature/theme-evgeniya-rostova
docs/evgeniya-rostova-theme-v1-post-merge-status
docs/runtime-baseline-self-reference-fix
feature/organizational-structure-v1
```

### 7.2 Классификация

`main`:

```text
KEEP
```

Пятнадцать merged feature/docs branches без уникального содержимого:

```text
SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL
```

`docs/evgeniya-rostova-theme-v1-design`:

```text
SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL
```

с обязательным пояснением:

- ветка имеет 2 уникальных commits в commit graph;
- уникальными являются только два исторических Markdown-файла;
- Git blob SHA и размер каждого файла совпадают с `main`;
- локальная проверка завершилась `BYTEWISE_DOCUMENT_COMPARISON_STATUS=PASS`.

### 7.3 Отсутствие автоматического cleanup

Audit не является командой удаления.

Запрещено в рамках данного инкремента:

- удалять remote branches;
- удалять local branches;
- перемещать refs;
- force-push;
- менять default branch;
- создавать branch protection rules;
- закрывать или изменять PR.

## 8. Scope boundary

### 8.1 Включено

- обновление перечисленной living documentation;
- добавление repository audit;
- исправление текущих baseline numbers и lifecycle status;
- обновление ссылок между документами;
- уточнение runbook для текущего `main` без изменения исполняемых инструментов;
- фиксация известного technical debt checker'ов;
- документальная проверка отсутствия runtime diff.

### 8.2 Исключено

- PHP, SQL, JavaScript и CSS changes;
- изменение migrations;
- изменение schema/data;
- изменение permissions или role assignments;
- изменение checker-файлов;
- удаление `PermissionBaselineRegressionAdapter`;
- deploy;
- installer;
- database backup;
- runtime testing;
- branch deletion;
- tag/release creation;
- GitHub Actions/CI;
- production deployment;
- mobile testing или заявление Mobile PASS.

## 9. Безопасность и конфиденциальность

Документация не должна содержать:

- содержимое `config/local.php`;
- DB password;
- test-user credentials;
- session data;
- реальные персональные данные;
- реальные номера, дислокацию, численность, вооружение или закрытые сведения;
- приватные URL или токены.

Допустимы только безопасные технические идентификаторы commits, PR, migrations, файлов и веток.

## 10. Validation architecture

Проверка документационного инкремента разделяется на пять уровней.

### 10.1 Scope validation

Git diff относительно базового commit должен содержать только:

```text
README.md
docs/**
```

Любой diff в `app`, `config`, `database`, `deploy`, `public`, `themes` или `tools` является блокирующей ошибкой.

### 10.2 Baseline consistency

Во всех living documents должны совпадать:

- merge commit;
- tested runtime HEAD;
- PR #15;
- migrations `001–009`;
- 4 роли;
- 25 permissions;
- 3 темы;
- статус Organizational Structure v1;
- отсутствие активного функционального инкремента;
- mobile out-of-scope statement.

### 10.3 Obsolete-current-claim scan

Должны быть устранены утверждения, которые представляют как текущие:

- `migrations: 001–008`;
- `system permissions: 19`;
- `built-in themes: 2`;
- `last functional PR: #9` или `#12`;
- Organizational Structure как нереализованную функцию;
- `feature/theme-evgeniya-rostova` как активную ветку;
- тему «Евгения Ростова» как не объединённую.

Исторические упоминания этих значений допускаются только с явной датой, PR или контекстом прошлой контрольной точки.

### 10.4 Link and structure validation

Проверяются:

- существование всех относительных Markdown-ссылок;
- отсутствие ссылок на отсутствующие документы;
- корректность индекса `docs/README.md`;
- уникальность заголовков audit sections;
- отсутствие случайных пустых Test Report replacements;
- UTF-8 content.

### 10.5 Secret and runtime safety validation

Проверяются:

- отсутствие secret patterns;
- отсутствие содержимого локальной конфигурации;
- отсутствие runtime files в diff;
- отсутствие branch mutation;
- чистота feature checkout после синхронизации.

## 11. Commit strategy

Рекомендуется последовательность:

1. Architecture;
2. Specification;
3. Formal Review;
4. отдельный Approval record после явного утверждения;
5. living documentation updates;
6. repository audit artifact;
7. validation report;
8. Pull Request;
9. PR Review;
10. отдельное merge approval;
11. merge.

Architecture, Specification и Review могут фиксироваться отдельными commits для прозрачности gate history.

## 12. Rollback и recovery

Инкремент не меняет runtime или DB.

Recovery выполняется стандартным Git revert документационных commits либо закрытием PR без merge.

SQL backup и deploy backup не требуются.

Удаление веток не является частью rollback и не выполняется.

## 13. Риски и меры контроля

### Риск 1. Подмена исторического статуса текущим

Мера: исторические process-artifacts не изменяются.

### Риск 2. Указание merge commit как протестированного runtime

Мера: отдельные поля `merged main commit` и `tested runtime HEAD`.

### Риск 3. Неполное обновление living docs

Мера: единый canonical baseline и cross-document scan.

### Риск 4. Случайное включение checker cleanup

Мера: запрет изменений в `tools` и `database`; technical debt только документируется.

### Риск 5. Преждевременное удаление веток

Мера: audit использует статус `SAFE TO DELETE AFTER SEPARATE EXPLICIT APPROVAL`; delete actions отсутствуют.

### Риск 6. Некорректное заявление mobile testing

Мера: в каждом релевантном документе сохраняется `OUT OF SCOPE / NOT RUN`.

## 14. Architecture acceptance criteria

Architecture считается выполненной, если:

- выбран docs-only подход;
- зафиксировано разделение living/history/target documentation;
- определён canonical baseline;
- определён полный scope файлов;
- repository audit охватывает все 17 веток;
- ветка с двумя уникальными commits описана отдельно;
- branch deletion исключён;
- checker cleanup исключён;
- validation model определена;
- rollback безопасен;
- отсутствуют скрытые runtime решения.

## 15. Gate

```text
ARCHITECTURE STATUS: READY FOR SPECIFICATION AND FORMAL REVIEW
IMPLEMENTATION STATUS: NOT STARTED
RUNTIME CHANGES: PROHIBITED
BRANCH DELETION: PROHIBITED
```
