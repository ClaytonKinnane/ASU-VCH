# Implementation — Public Military Occupational Specialties v1

## Статус

```text
PHASE: FINAL PR REVIEW REMEDIATION AUTOMATED TESTING PASS / TARGETED MANUAL RECHECK REQUIRED
BASELINE: 99f9f283768ca418fb7ff86d55b7d73e7a6c3510
BRANCH: feature/public-military-occupational-specialties-directory
MIGRATION: 011_public_military_occupational_specialties_directory.sql
IMPLEMENTATION_PATHS: 25
PR: #20 OPEN
MERGE: NOT AUTHORIZED
```

## Реализовано

- source-centric схема из 9 таблиц и 26 triggers;
- exact seed: 5 legal sources, 4 snapshots, 3 code segments, 6 context domains, 3 scopes, 2 direct disclosures, 4 organizations, 15 programs;
- read-only repository и bootstrap factory;
- owner-only GET route и плитка справочника;
- статический/интеграционный checker;
- PowerShell 5.1 testing runner;
- отсутствие position/rank/equipment/person relations.

## Migration packaging

Canonical SQL выполняется через compatibility loader из двух упорядоченных gzip/base64 частей.

```text
CANONICAL_SQL_BYTES: 88267
CANONICAL_SQL_SHA256: 26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9
GZIP_ARCHIVE_BYTES: 9472
GZIP_ARCHIVE_SHA256: 1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39
BASE64_PARTS: 2
```

Loader проверяет archive SHA-256, распаковывает canonical SQL и проверяет SQL SHA-256 до передачи installer.

## Local Testing attempt 1

```text
DATE: 2026-08-01
RESULT: PRE-EXECUTION PARSER FAILURE
BACKUP: NOT STARTED
DEPLOY: NOT STARTED
INSTALLER: NOT STARTED
DATABASE CHANGES: NONE
```

Windows PowerShell 5.1 прочитал UTF-8 без BOM в системной ANSI-кодировке. Кириллические строки runner были искажены до разбора и вызвали ParserError.

Исправление:

```text
COMMIT: fb28a8d071fb871c0a0f7bc39042bb7331b4771e
RUNNER ENCODING POLICY: ASCII-ONLY
```

## Automated Testing attempt 2

```text
DATE: 2026-08-01
HEAD: 289b6f1c4e77843e5d650b46c480cd44aa6c8eae
RESULT: PASS
MIGRATION 011: APPLIED
REPEATED INSTALLER: PASS
PHP FILES LINTED: 112
SOURCE/DEPLOY PARITY: PASS
HTTP SMOKE: PASS
WORKING TREE: CLEAN
```

Automated testing подтвердил схему, exact seed, lifecycle, negative tests, permissions, все обязательные regressions и сохранность deploy-конфигурации.

## Manual Desktop Acceptance attempt 1

```text
RESULT: FAIL / DEFECTS CONFIRMED
MOBILE: OUT OF SCOPE / NOT RUN
```

Зафиксированы:

1. неполная русификация пользовательских подписей;
2. отсутствие визуальных интервалов между крупными секциями;
3. непонятный технический текст и SHA-256 в пользовательском интерфейсе;
4. неконсистентный подъём интерактивных и статичных карточек;
5. чрезмерно узкая колонка источника;
6. лишнее пустое пространство внизу блока записей.

## UI remediation

Реализовано без изменения схемы, seed и repository-контракта:

- все видимые source-role, evidence и status labels переведены на русский язык;
- evidence fingerprints сохранены в БД и checker, но удалены из пользовательского представления;
- между крупными секциями добавлен единый вертикальный интервал;
- подъём применяется только к карточкам с внешними ссылками;
- статичные информационные карточки не используют dashboard hover behavior;
- таблица ВУС получила пропорции колонок 26% / 22% / 24% / 28%;
- перенос текста источника выполняется по словам, а не по отдельным буквам;
- нижнее предупреждение стало компактным;
- для каждой из трёх тем зарегистрирован отдельный VUS stylesheet;
- добавлен `check-military-occupational-specialties-ui.php`;
- testing runner расширен до exact scope из 24 путей и 14 runtime parity paths.

## Automated Testing attempt 3

```text
DATE: 2026-08-01
HEAD: 2ec9eb1866ed59cdf3411bbed1e145abc7d12fc2
RESULT: TEST-CHECKER PATH DEFECT
BACKUP: PASS
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS / NO NEW MIGRATIONS
CORE VUS CHECKER: PASS
UI CHECKER: FAIL BEFORE STYLESHEET ASSERTIONS
DATABASE CHANGES: NONE
```

UI checker корректно подтвердил русификацию, отсутствие fingerprint в пользовательском интерфейсе и требуемые классы разметки, но искал тематические CSS только в исходном пути `themes/...`. После локального deploy темы находятся в `public/themes/...`, поэтому проверка остановилась на чтении первого stylesheet.

Исправление:

```text
COMMIT: 09b032ba39c75d17f87aa003d1df13ddedcd5b2d
CHECKER ASSET RESOLUTION: deployed public/themes/ first, source themes/ fallback
```

## Automated Testing attempt 4

```text
DATE: 2026-08-01
HEAD: ed73780fc3f34aa0e19cc7d168d366832d5dae79
RESULT: THEME REGRESSION EXPECTATION DEFECT
BACKUP: PASS
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS / NO NEW MIGRATIONS
CORE VUS CHECKER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS BEFORE THEME CHECK: PASS
THEME MANAGEMENT CHECK: FAIL ON EXACT REQUIRED-ASSET LIST
DATABASE CHANGES: NONE
```

Существующий theme-management regression checker строго сравнивал прежний список ресурсов темы «Евгения Ростова» и не учитывал новый зарегистрированный `css/military-occupational-specialties.css`.

Исправление:

```text
COMMIT: feaae262033468fc64459ae4b64d0f85be7e9040
EXPECTED ASSETS: VUS stylesheet included
EXACT SCOPE: database/check-theme-management.php included
SOURCE/DEPLOY PARITY: theme regression checker included
```

## Automated Testing attempt 5

```text
DATE: 2026-08-01
HEAD: c71a2959f30c7aa570ca5120115aff81f9054625
RESULT: SOURCE/DEPLOY PARITY PATH DEFECT
BACKUP: PASS
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS / NO NEW MIGRATIONS
CORE VUS CHECKER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS: PASS
THEME REGRESSIONS: PASS
ORGANIZATION REGRESSION: PASS / 58 OF 58
SOURCE/DEPLOY PARITY: FAIL ON FIRST THEME ASSET PATH
DATABASE CHANGES: NONE
```

Все функциональные, UI, security, theme и Organization-проверки прошли. Parity успешно сравнил обычные runtime-файлы, после чего искал исходный путь `themes/...` непосредственно в deploy-root. Фактический deploy-контракт размещает темы в `public/themes/...`.

Исправление:

```text
COMMIT: e9d865990756a70fbbf8d85ee5074b8e518a5a24
SOURCE PATH: themes/<slug>/assets/...
DEPLOY PATH: public/themes/<slug>/assets/...
OTHER PATHS: unchanged
```

Runner теперь преобразует только пути с префиксом `themes/` и выводит `deploy_path` в parity-журнале. Runtime page, CSS, migration, seed и база данных тестовым дефектом не затронуты.

## Automated Testing attempt 6

```text
DATE: 2026-08-01
HEAD: e1bf5c85708cfa29d3a0356368938345eb2064e2
RESULT: PASS
IMPLEMENTATION PATHS: 24
BACKUP: PASS
BACKUP FILE: C:\OSPanel\backups\asu-vch\asu_vch-20260801-140604.sql
BACKUP SIZE BYTES: 389877
BACKUP SHA-256: 90C2368DB9C82F83C0A856D3238CD717D3F5591567D2BB967C6646588B534EA2
DEPLOY: PASS
PHP FILES LINTED: 113
INSTALLER TWICE: PASS / NO NEW MIGRATIONS
CORE VUS CHECKER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS: PASS
THEME REGRESSIONS: PASS
ORGANIZATION REGRESSION: PASS / 58 OF 58
SOURCE/DEPLOY PARITY: PASS / 14 PATHS
HTTP SMOKE: PASS
FINAL ORIGIN FEATURE DIVERGENCE: 0 0
FINAL WORKING TREE: CLEAN
```

Финальные markers:

```text
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=NOT_RUN
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=NOT_CREATED
```

## Manual Desktop Acceptance attempt 2

```text
DATE: 2026-08-01
RUNTIME_HEAD: e1bf5c85708cfa29d3a0356368938345eb2064e2
RESULT: PASS
THEMES: 3 OF 3
DESKTOP RESOLUTIONS: 1920x1080 AND 1366x768
CONSOLE ERRORS: 0
HTTP OR ASSET 404: 0
DEFECTS: NONE
MOBILE: OUT OF SCOPE / NOT RUN
```

Evidence: `docs/testing/PUBLIC-MILITARY-OCCUPATIONAL-SPECIALTIES-V1-MANUAL-DESKTOP-ACCEPTANCE-2026-08-01.md`.

## Pull Request

```text
PR: #20
STATE: OPEN
BASE: main
HEAD BEFORE FINAL REVIEW REMEDIATION: 42aa35bae08625595449697bbe684b962f052d4c
MERGE: NOT AUTHORIZED
BRANCH DELETION: NOT AUTHORIZED
```

## Final PR Review attempt 1

```text
DATE: 2026-08-01
RESULT: CHANGES REQUIRED
BLOCKING FINDINGS: 2
```

Зафиксированы:

1. при `record_type=all` и выбранной организации нормативные direct-disclosure записи ошибочно оставались в общем результате;
2. runner ожидал 24 пути, тогда как фактический PR head после evidence-файла содержал 25 путей.

Дополнительно требовалась синхронизация PR body, Specification и Implementation metadata.

## Final PR Review remediation

Реализовано в утверждённом scope:

- `MilitaryOccupationalSpecialtyCatalogRepository::shouldSearchPublicDisclosures()` определяет включение нормативных записей;
- при выбранной организации direct-disclosure записи исключаются;
- `record_type=direct-disclosure + organization` возвращает пустой результат;
- integration checker проверяет пять комбинаций `record_type/organization` и фактическую фильтрацию программ по организации;
- runner ожидает финальный exact scope из 25 путей, включая manual acceptance evidence;
- финальные markers runner отражают открытый PR #20 и необходимость targeted manual recheck;
- Specification, Test Plan, Runbook, Implementation и PR body синхронизируются с фактическим scope.

Migration, seed, schema, permissions и theme assets этим исправлением не изменены.

## Automated Testing attempt 7 — Final PR Review remediation

```text
DATE: 2026-08-01
HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
RESULT: PASS
IMPLEMENTATION PATHS: 25
BACKUP: PASS
BACKUP FILE: C:\OSPanel\backups\asu-vch\asu_vch-20260801-153344.sql
BACKUP SIZE BYTES: 389878
BACKUP SHA-256: 8D757448B22CB66AC77EDF7E1B3A1E6EAFFCB2C41988BB3830967934582B386C
DEPLOY: PASS / 153 FILES
PHP FILES LINTED: 113 / 0 ERRORS
INSTALLER TWICE: PASS / NO NEW MIGRATIONS
CORE VUS CHECKER: PASS
ORGANIZATION FILTER POLICY REGRESSIONS: PASS
ORGANIZATION REPOSITORY FILTER: PASS
UI CHECKER: PASS
DIRECTORY REGRESSIONS: PASS
SECURITY REGRESSIONS: PASS
THEME REGRESSIONS: PASS
ORGANIZATION REGRESSION: PASS / 58 OF 58
SOURCE/DEPLOY PARITY: PASS / 14 PATHS
HTTP SMOKE: PASS
FINAL ORIGIN FEATURE DIVERGENCE: 0 0
FINAL WORKING TREE: CLEAN
```

Финальные markers:

```text
AUTOMATED_TESTING_STATUS=PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS=TARGETED_RECHECK_REQUIRED
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=OPEN_20_NOT_MERGED
```

Runtime remediation и test-infrastructure remediation подтверждены полным локальным прогоном. Evidence-only commit настоящего раздела не требует повторного deploy или Automated Testing.

## Следующий gate

Targeted Manual Desktop Recheck только для фильтра организации:

- `record_type=all + организация` — только программы выбранной организации;
- `record_type=direct-disclosure + организация` — пустое состояние;
- сброс фильтров — полный набор из 17 записей.

После фиксации PASS выполняется повторный Final PR Review. Merge и удаление ветки остаются запрещены.
