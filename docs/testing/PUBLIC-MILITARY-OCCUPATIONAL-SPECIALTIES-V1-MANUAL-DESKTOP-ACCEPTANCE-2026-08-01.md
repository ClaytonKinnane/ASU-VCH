# Manual Desktop Acceptance — Public Military Occupational Specialties v1

## Статус

```text
DATE: 2026-08-01
RESULT: PASS
RUNTIME_HEAD: e1bf5c85708cfa29d3a0356368938345eb2064e2
BRANCH: feature/public-military-occupational-specialties-directory
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
DEFECTS: NONE
```

Ручная desktop-приёмка выполнена владельцем проекта после UI remediation и успешного полного Automated Testing.

## Доступ

```text
OWNER_ACCESS=PASS
ORDINARY_ROLE_403=PASS
```

## Проверка исправленных дефектов

```text
RUSSIAN_LOCALIZATION=PASS
SECTION_SPACING=PASS
TECHNICAL_HASHES_HIDDEN=PASS
CARD_INTERACTION_CONSISTENCY=PASS
TABLE_COLUMN_WIDTHS=PASS
BOTTOM_EMPTY_SPACE=PASS
```

Подтверждено:

- все пользовательские подписи отображаются на русском языке;
- крупные секции имеют достаточные визуальные интервалы;
- SHA-256 и техническое пояснение evidence bundle отсутствуют в пользовательском интерфейсе;
- подъём применяется только к карточкам с внешними ссылками;
- ширины колонок таблицы сбалансированы;
- избыточное пустое пространство внизу блока записей устранено.

## Функциональная проверка

```text
SEARCH_AND_FILTERS=PASS
EMPTY_STATE=PASS
EXTERNAL_LINKS=PASS
NO_POSITION_MATCHING=PASS
NO_PERSONAL_DATA=PASS
NO_COMPLETENESS_CLAIM=PASS
```

## Темы и desktop-разрешения

```text
ASU_BLUE_1920x1080=PASS
ASU_BLUE_1366x768=PASS
ASU_LIGHT_BLUE_1920x1080=PASS
ASU_LIGHT_BLUE_1366x768=PASS
ASU_EVGENIYA_ROSTOVA_1920x1080=PASS
ASU_EVGENIYA_ROSTOVA_1366x768=PASS
```

## Диагностика браузера

```text
CONSOLE_ERRORS=0
HTTP_OR_ASSET_404=0
DEFECTS=NONE
```

## Итоговые markers

```text
MANUAL_DESKTOP_ACCEPTANCE_STATUS=PASS
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=NOT_CREATED
MERGE_STATUS=NOT_AUTHORIZED
```

Evidence-only файл не изменяет runtime, migration, seed, repository-контракт или theme assets. Повторный deploy и повторный Automated Testing после этого commit не требуются.

Следующий gate — отдельное разрешение владельца проекта на создание Pull Request из `feature/public-military-occupational-specialties-directory` в `main`.
