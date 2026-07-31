# Implementation: Справочник типов воинских должностей ВС РФ v1

## Статус

```text
DATE: 2026-08-01
BRANCH: feature/military-positions-directory
PHASE: IMPLEMENTATION CANDIDATE PUBLISHED FOR LOCAL TESTING
AUTOMATED WINDOWS/OPEN SERVER/MYSQL TESTING: NOT RUN
MANUAL DESKTOP ACCEPTANCE: NOT RUN
MOBILE TESTING: OUT OF SCOPE / NOT RUN
PR: NOT CREATED
MERGE: NOT AUTHORIZED
```

## Реализованный scope

Подготовлены:

- migration `010_military_positions_directory.sql`, собираемая compatibility layer из 13 текстовых частей с обязательной SHA-256 проверкой;
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

Из-за ограничения GitHub write-канала крупный canonical SQL хранится в 13 последовательных UTF-8 частях `010_military_positions_directory.sql.part01` … `part13`. Маркерная migration сохраняет утверждённое имя `010_military_positions_directory.sql`. `MilitaryPositionMigrationCompatibility.php` собирает части в исходном порядке и допускает выполнение только при SHA-256 `3ebb00dc2d89027eea7f3619deb29adfdcdea7b67b9a221b4ab0cd159d96ac78`. Installer по-прежнему регистрирует единственную migration 010.
