# Implementation: Справочник типов воинских должностей ВС РФ v1

## Статус

```text
DATE: 2026-08-01
BRANCH: feature/military-positions-directory
PHASE: AUTOMATED TESTING PASSED / MANUAL DESKTOP ACCEPTANCE REQUIRED
AUTOMATED WINDOWS/OPEN SERVER/MYSQL TESTING: PASS
TESTED HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
AUTOMATED TEST EVIDENCE: docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-AUTOMATED-TESTING-2026-08-01.md
MANUAL DESKTOP ACCEPTANCE: NOT RUN
MOBILE TESTING: OUT OF SCOPE / NOT RUN
PR: NOT CREATED
MERGE: NOT AUTHORIZED
```

## Реализованный scope

Подготовлены:

- migration `010_military_positions_directory.sql`, загружаемая compatibility layer из пяти последовательных base64-частей gzip-архива с обязательной проверкой SHA-256 архива и canonical SQL;
- 14 таблиц и 41 trigger;
- whole-catalog lifecycle `building → published → superseded`;
- блокировка INSERT/UPDATE/DELETE после публикации;
- 4 источника версии;
- 24 source entries;
- 28 source-entry evidence;
- 4 функциональных семейства;
- 34 канонических типа;
- 35 нормативных вариантов;
- 2 composition scope и 3 scope-member связи;
- 34 type-scope relations и 35 evidence rows;
- 29 organizational relations и 29 evidence rows;
- отсутствие rank relation tables и автоматических соответствий званиям;
- `MilitaryPositionCatalogRepository` с bulk loading;
- owner-only read-only route `/admin/directories/military-positions.php`;
- плитка в общем разделе справочников;
- integration checker с rejection tests;
- PowerShell 5.1 runner для backup, deploy, migration, regressions, parity и HTTP smoke;
- локальный runbook без patch workflow.

## Локальный процесс

Implementation-кандидат хранится в GitHub feature-ветке. Локально допускаются только:

1. clean synchronization ветки;
2. запуск `tools\Test-MilitaryPositionsDirectory.ps1`;
3. manual desktop acceptance после automated PASS.

Локальные изменения исходников, commit, push и PR во время Testing не требуются.

## Упаковка migration 010

Из-за ограничения GitHub write-канала крупный canonical SQL хранится в пяти последовательных base64-частях gzip-архива `010_military_positions_directory.sql.gz.b64.part00` … `part04`. Маркерная migration сохраняет утверждённое имя `010_military_positions_directory.sql`. `MilitaryPositionMigrationCompatibility.php` проверяет SHA-256 gzip-архива `af617b754e4a8a5b453d6856f5c20540edb72d839fb162e61f9c160493c6fb82`, распаковывает canonical SQL и допускает выполнение только при SHA-256 `3ebb00dc2d89027eea7f3619deb29adfdcdea7b67b9a221b4ab0cd159d96ac78`. Installer по-прежнему регистрирует единственную migration 010.

## Результат автоматизированного Testing

На локальной Windows/Open Server/MySQL среде подтверждено:

- repository preflight и точный scope: PASS;
- backup БД: PASS;
- deploy с сохранением `config/local.php`: PASS;
- PHP lint: 108 файлов / 0 ошибок;
- migration 010 и repeated installer: PASS;
- military positions integration checker: PASS;
- directory, security, theme и Organization regressions: PASS;
- source/deploy parity: PASS;
- HTTP smoke: PASS;
- post-test Git/config integrity: PASS.

Следующий обязательный gate — ручная desktop-приёмка в темах `asu-blue`, `asu-light-blue` и `asu-evgeniya-rostova`. PR до её завершения и отдельного разрешения не создаётся.
