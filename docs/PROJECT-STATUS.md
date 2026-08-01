# Текущее состояние проекта АСУ-ВЧ

Дата актуализации: `2026-08-01`.

## Репозиторий и контрольные точки

Актуальный HEAD стабильной ветки определяется через `origin/main`:

```powershell
git fetch --prune origin
git rev-parse origin/main
git rev-list --left-right --count main...origin/main
```

Исторические anchors текущего functional baseline:

```text
repository: ClaytonKinnane/ASU-VCH
default branch: main
latest functional PR: #20
PR #19 merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
PR #19 tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
PR #19 final feature HEAD: 5424cefe2f1a6bdc2fa706612040a3985c88f04f
PR #20 merge commit / refresh baseline: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
PR #20 tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
PR #20 final feature HEAD: bea147505a85010b61fe938eb07ec474d76cdab5
migrations: 001–011
system roles: 4
system permissions: 25
built-in themes: 3
active functional increment: none
```

Точный current `main` не фиксируется как самореферентное living-поле. Merge и tested-runtime SHA выше являются историческими anchors.

## Последние функциональные инкременты

### PR #19 — Типовые воинские должности

```text
status: MERGED
merge commit: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
tested runtime HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
automated testing: PASS
manual desktop acceptance: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

Реализован owner-only read-only каталог:

- 14 таблиц и 41 DB trigger;
- 34 canonical position types и 35 normative variants;
- 4 families;
- composition и organizational-context evidence;
- поиск и фильтры;
- отсутствие автоматических связей с воинскими званиями;
- отсутствие кадровых назначений и персональных данных.

### PR #20 — Публичные сведения о ВУС

```text
status: MERGED
merge commit: 3082ec6ecbeddb92bd65e1398f05a9339abb199b
tested runtime HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
automated testing: PASS
manual desktop acceptance: PASS
targeted manual desktop recheck: PASS
final PR review: PASS
post-merge Git verification: PASS
mobile testing: OUT OF SCOPE / NOT RUN
```

Реализован owner-only read-only source-centric каталог:

- 9 таблиц и 26 DB triggers;
- 5 legal sources и 4 official source snapshots;
- 2 нормативных примера и 15 программ подготовки;
- 4 training organizations;
- 17 searchable records;
- фильтры по типу записи, виду идентификатора, составу, организации, уровню доказательности и статусу;
- организация применяется только к программам подготовки;
- отсутствие связей с должностями, званиями, ВВСТ и персональными данными;
- явное предупреждение о неполноте и запрете использования как персонального воинского учёта.

## Завершённые Pull Request

Функциональные PR:

| PR | Инкремент | Статус |
|---:|---|---|
| #1 | Базовый сайт, RBAC и управление пользователями v1 | MERGED |
| #2 | Обязательная смена временного пароля v1 | MERGED |
| #3 | Аудит отклонения пользователей v1 | MERGED |
| #4 | Архивирование и восстановление пользователей v1 | MERGED |
| #5 | Управление темами и АСУ Светлая синяя v1 | MERGED |
| #6 | Выравнивание радиусов и свечения плиток | MERGED |
| #7 | Стартовая страница справочников v1 | MERGED |
| #8 | Справочник составов и воинских званий v1 | MERGED |
| #9 | Справочник типов организационных элементов v1 | MERGED |
| #12 | Evgeniya Rostova Theme v1 | MERGED |
| #15 | Organizational Structure v1 | MERGED |
| #19 | Справочник типовых воинских должностей | MERGED |
| #20 | Публичные сведения о ВУС | MERGED |

Documentation-only PR #10, #11, #13, #14, #16, #17 и #18 также объединены и не создавали нового runtime baseline.

## Реализованные возможности

### Security и пользователи

- bootstrap первого владельца и отключение публичной регистрации;
- аутентификация, защищённые сессии, logout и CSRF;
- четыре системные роли и 25 permissions;
- создание, approval, activation, редактирование, роли, block/unblock;
- обязательная смена временного пароля;
- rejection и archive/restore с аудитом;
- защита последнего активного владельца и privacy-ограничения.

### Themes

- статический trusted registry и глобальная active theme;
- три встроенные темы;
- девять обязательных CSS-assets каждой темы, включая `military-occupational-specialties.css` и `organization.css`;
- четыре локальных SVG-assets темы `Евгения Ростова`;
- desktop acceptance затронутых интерфейсов во всех трёх темах.

### Directories

- owner-only landing page;
- воинские звания: 2 источника, 6 составов, 20 уровней;
- типы организационных элементов: 4 источника, 6 классов, 28 типов, 32 связи;
- типовые воинские должности: 34 canonical types, 35 variants;
- публичные сведения о ВУС: 17 searchable records;
- read-only GET routes, prepared statements, поиск, фильтры и профильные checker'ы.

### Organizational Structure v1

- structures и version lifecycle;
- draft tree и stable elements;
- документы-основания;
- history и compare;
- optimistic revisions, transactions, CSRF и RBAC;
- 7 таблиц, 16 triggers и 6 permissions.

## Проверенный baseline

Последний полный Automated Testing выполнен для runtime PR #20:

```text
PHP lint: 113 files / 0 errors
applied migrations: 11
new migrations after repeat installer: none
VUS integration checker: PASS
VUS UI checker: PASS
organization regression: 58 PASS / 0 FAIL
source/deploy parity: 14 paths / PASS
HTTP smoke: / 200, /health.php 200, /admin/ 302
automated testing: PASS
manual desktop acceptance: PASS
targeted manual recheck: PASS
```

Documentation-only commits после tested runtime не заявляются runtime-протестированными. Post-merge verification PR #20 была Git/GitHub-проверкой и не заменяет локальный deploy/runtime retest.

## Repository cleanup status

Исторический cleanup 2026-07-31 завершён. Позднее были созданы ветки PR #19, PR #20 и настоящего documentation refresh.

Fresh remote inventory после создания `docs/post-pr20-baseline-refresh`:

```text
main
docs/post-pr20-baseline-refresh
feature/military-positions-directory
feature/public-military-occupational-specialties-directory
```

Пользовательский local audit подтвердил, что все 13 локальных feature-веток merged в `origin/main`. Техническая безопасность удаления не является разрешением. Cleanup выполняется только после завершения настоящего refresh, fresh inventory и отдельного явного approval.

## Текущий gate

```text
active documentation increment: Post-PR20 Baseline Refresh
runtime change: none
PR for refresh: not created
merge for refresh: not authorized
branch deletion: not authorized
```
