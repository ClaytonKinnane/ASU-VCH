# Implementation — Составы военнослужащих и воинские звания v2

Статус: **IMPLEMENTED**

Ветка: `feature/military-ranks-directory-v2`

Runtime/manual acceptance head: `b44aed14ee1a54be213cbc939322ba21b02e7a58`

## 1. Application layer

### `app/Directory/MilitaryRankCatalogRepository.php`

Repository расширен поддержкой:

- current published version;
- списка visible published/superseded versions;
- version lookup по code;
- version-scoped sources и compositions;
- поиска по выбранной версии;
- ancestry-aware фильтрации родительских составов;
- integrity errors при некорректном current state.

### `app/Directory/MilitaryRankCompatibilityService.php`

Добавлен read-only compatibility service со статусами:

- `compatible`;
- `incompatible`;
- `invalid-catalog-version`;
- `composition-not-selectable`;
- `record-not-found`;
- `integrity-error`.

Сервис не зависит от Organization и проверяет same-version ancestry.

## 2. Database layer

Добавлены:

- `database/MilitaryRankDirectoryV2MigrationCompatibility.php`;
- `database/MilitaryRankDirectoryV2/Baseline.php`;
- `Definitions.php`;
- `Ddl.php`;
- `PublishedState.php`;
- `Recovery.php`;
- `SqlTemplates.php`;
- `publication.sql`;
- семь trigger template files;
- marker `database/migrations/012_military_ranks_directory_v2.sql`.

`database/OrganizationalStructureMigrationCompatibility.php` маршрутизирует migration 012 через compatibility loader.

Результат публикации:

- v1: superseded, valid_to `2026-08-02`;
- v2: published/current, valid_from `2026-08-03`;
- 8 compositions;
- 8 semantics;
- 20 ranks;
- 2 version sources;
- 8 composition sources;
- 18 lifecycle/integrity/immutability triggers.

## 3. UI

Обновлён owner-only route:

`public/admin/directories/military-ranks.php`

Добавлено:

- version switch;
- current/historical lifecycle metadata;
- current v2 и historical v1;
- derived/staffing badges;
- explicit v1 historical notice;
- source cards;
- version-aware search и filters;
- controlled empty state;
- controlled HTTP 503 state;
- read-only presentation.

После ручного visual review выполнена UI-remediation:

- двухколоночная composition grid заменена одной start-aligned колонкой;
- устранено растягивание карточек;
- child cards получили отступ и connector;
- child labels сокращены до собственных имён;
- полный composition path сохранён в таблице званий.

## 4. Themes

Зарегистрирован и опубликован asset:

`css/military-ranks-v2.css`

для тем:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

`config/themes.php` содержит asset в required list каждой темы.

## 5. Checkers

Добавлены/обновлены:

- `tools/check-military-ranks-directory-v2-source.php`;
- `tools/check-military-rank-v2-loader.php`;
- `tools/check-military-rank-compatibility-service.php`;
- `tools/check-military-ranks-directory-core.php`;
- `tools/check-military-ranks-directory-v2-ui-layout.php`;
- theme regression checker;
- permission baseline regression adapter;
- Organization migration compatibility self-check.

Source checker дополнительно блокирует:

- повреждённый UTF-8;
- управляющие байты;
- `TRIGGGER` и повреждённые SQL tokens;
- неполный набор 18 DROP/CREATE trigger declarations;
- запрещённые Staffing/Organization scope terms.

UI-layout checker блокирует:

- возврат двухколоночной растягивающей grid;
- отсутствие start alignment;
- отсутствие child indentation/connector;
- повторение полного parent path в child card labels.

## 6. Scope boundaries

Не добавлены:

- Staffing schema;
- Organization relations;
- military position definitions;
- personnel assignments;
- реальные данные подразделений/военнослужащих;
- mutation routes;
- permissions;
- increment B.

## 7. Operational note

Migration 012 уже применена в локальной Open Server / MySQL 8.4 среде. Повторный installer сообщает 12 применённых migrations и отсутствие новых migrations.

Документационные commits после runtime head не изменяют application runtime или БД.
