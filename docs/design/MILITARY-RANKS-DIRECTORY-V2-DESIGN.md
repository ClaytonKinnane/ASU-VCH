# Составы военнослужащих и воинские звания v2 — Architecture & Specification

Статус: **APPROVED**  
Дата утверждения: **2026-08-03**  
Базовая ветка: `main`  
Базовый commit: `5f97ed4237cca6fed314952e0c19716d98e7f459`  
Рабочая ветка: `feature/military-ranks-directory-v2`

## 1. Цель

Расширить нормативный системный справочник «Составы военнослужащих и воинские звания» версионируемой моделью прикладных категорий для последующего использования в штатных должностях, сохранив нормативные составы, историческую версию v1 и неизменный перечень из 20 воинских званий.

## 2. Границы инкремента

В область входят:

- новая версия каталога `rf-military-ranks-staffing-scopes-v2`;
- lifecycle `building → published → superseded`;
- исторический просмотр v1;
- version-scoped semantics составов;
- read-only compatibility service;
- owner-only UI с выбором версии, поиском и фильтрами;
- три desktop-темы;
- migration 012, recovery и integrity guards.

В область не входят:

- Staffing tables, штатные слоты и назначения;
- связи с Organization;
- фактические данные воинских частей, должностей или военнослужащих;
- импорт Excel;
- mutation UI и новые permissions;
- инкремент B;
- обязательная mobile-приёмка.

## 3. Версии каталога

### v1

- code: `rf-military-ranks-2026-07-27`;
- lifecycle после публикации v2: `superseded`;
- `valid_to`: `2026-08-02`;
- 6 составов;
- 20 званий;
- Staffing eligibility отсутствует и не выводится задним числом.

### v2

- code: `rf-military-ranks-staffing-scopes-v2`;
- source verification date: `2026-08-02`;
- business effective date: `2026-08-03`;
- lifecycle: `published`;
- current version: единственная;
- 8 составов/категорий;
- 8 version-scoped semantic records;
- 20 неизменных кодов, наименований и порядковых позиций званий;
- 2 version-source links;
- 8 composition-source links.

## 4. Иерархия v2

```text
enlisted
├── soldiers-and-sailors
└── sergeants-and-starshinas
warrant-officers
officers
├── junior-officers
├── senior-officers
└── higher-officers
```

Нормативные корневые составы сохраняются. `soldiers-and-sailors` и `sergeants-and-starshinas` являются прикладными derived-категориями АСУ-ВЧ и не объявляются самостоятельными нормативными составами.

Staffing-selectable категории v2:

- `soldiers-and-sailors`;
- `sergeants-and-starshinas`;
- `warrant-officers`;
- `officers`.

## 5. Данные и ограничения

Добавляются:

- lifecycle-поля версии;
- generated guards для единственной current/building версии;
- `military_personnel_composition_semantics`;
- `military_personnel_composition_sources`;
- version-scoped unique/check constraints;
- 18 lifecycle, integrity и immutability triggers.

Published и superseded child data неизменяемы. Очистка допускается только для корректной building-версии.

Publication guard проверяет точные anchors, состав данных и evidence links, а не только количество строк.

## 6. Migration и recovery

Migration 012 выполняется compatibility loader-ом. SQL marker должен fail closed при обходе loader-а.

Поддерживаемые состояния:

1. чистая v1;
2. частично применённый DDL без опубликованной v2;
3. корректная building v2 — очистка и повторное создание;
4. противоречивая building v2 — отказ без автоматического исправления;
5. опубликованная точная v2 без записи migration registry — проверка и восстановление регистрации.

Повторный installer обязан быть идемпотентным.

## 7. Application contracts

### Repository

`MilitaryRankCatalogRepository` предоставляет:

- единственную текущую опубликованную версию;
- список видимых published/superseded версий;
- version-scoped sources и compositions;
- поиск и фильтрацию по выбранной версии;
- ancestry-aware фильтр родительского состава.

### Compatibility service

Read-only Reference-owned service возвращает один из статусов:

- `compatible`;
- `incompatible`;
- `invalid-catalog-version`;
- `composition-not-selectable`;
- `record-not-found`;
- `integrity-error`.

Сервис использует только same-version ancestry и не зависит от Organization.

## 8. UI

Route: `/admin/directories/military-ranks.php`.

Требования:

- owner-only, `GET`, read-only;
- current/historical version switch;
- явные lifecycle и date metadata;
- два официальных источника;
- 8 карточек v2 и 6 карточек v1;
- derived и staffing badges только для v2;
- явная historical notice для v1;
- 20 строк и version-aware filters;
- controlled empty state;
- HTTP 503 при отсутствии current version или нарушении integrity;
- HTTP 403 для пользователя без owner wildcard;
- одинаковая функциональность в `asu-blue`, `asu-light-blue`, `asu-evgeniya-rostova`.

Иерархия составов выводится одной start-aligned колонкой. Дочерние категории имеют короткие названия, отступ и connector; двухколоночное растягивание карточек запрещено отдельным checker-ом.

## 9. Security и privacy

- используется существующий wildcard `system.*.*`;
- новые permissions отсутствуют;
- mutation routes и forms отсутствуют;
- запросы выполняются prepared statements;
- в GitHub, migration, fixtures, screenshots и public reports не включаются данные реальных подразделений или военнослужащих.

## 10. Acceptance criteria

- migration 012 и повторный installer: PASS;
- exact v1/v2 anchors и counts: PASS;
- compatibility service statuses: PASS;
- full static/integration/regression checks: PASS;
- source/deploy parity: PASS;
- HTTP smoke: PASS;
- desktop manual acceptance 1920×1080 и 1366×768 во всех трёх темах: PASS;
- current v2 и historical v1: PASS;
- non-owner HTTP 403: PASS;
- mobile: **OUT OF SCOPE / NOT RUN**.

## 11. Merge policy

Этот документ и успешное тестирование не разрешают merge. Merge допускается только после Final PR Review и отдельного явного разрешения владельца. Удаление ветки требует ещё одного отдельного разрешения.
