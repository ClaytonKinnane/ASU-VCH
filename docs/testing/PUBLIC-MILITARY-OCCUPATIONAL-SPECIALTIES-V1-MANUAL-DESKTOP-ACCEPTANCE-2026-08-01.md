# Manual Desktop Acceptance — Public Military Occupational Specialties v1

## Статус

```text
DATE: 2026-08-01
RESULT: PASS
INITIAL_ACCEPTANCE_RUNTIME_HEAD: e1bf5c85708cfa29d3a0356368938345eb2064e2
FINAL_PR_REVIEW_REMEDIATION_RUNTIME_HEAD: 9db06c4a26066ca25dc36c627c1236089a3c1238
BRANCH: feature/public-military-occupational-specialties-directory
PR: #20 OPEN / NOT MERGED
MOBILE_TESTING_STATUS: OUT_OF_SCOPE_NOT_RUN
DEFECTS: NONE
```

Ручная desktop-приёмка выполнена владельцем проекта после UI remediation и успешного полного Automated Testing. После remediation замечаний Final PR Review отдельно выполнена целевая перепроверка фильтра организации.

## Доступ

```text
OWNER_ACCESS=PASS
ORDINARY_ROLE_403=PASS
```

## Проверка исправленных UI-дефектов

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

## Targeted Manual Desktop Recheck — Final PR Review remediation

```text
DATE: 2026-08-01
TARGETED_MANUAL_DESKTOP_RECHECK_STATUS=PASS
INITIAL_TOTAL_17=PASS
ALL_PLUS_FINANCIAL_UNIVERSITY_8=PASS
ALL_PLUS_MIIGAIK_4=PASS
ALL_PLUS_CHGU_2=PASS
ALL_PLUS_OSU_1=PASS
ONLY_SELECTED_ORGANIZATION_ROWS=PASS
NORMATIVE_EXAMPLES_EXCLUDED_WITH_ORGANIZATION=PASS
DIRECT_DISCLOSURE_PLUS_ORGANIZATION_EMPTY=PASS
TRAINING_PROGRAM_PLUS_ORGANIZATION=PASS
RESET_RETURNS_17=PASS
CONSOLE_ERRORS=0
HTTP_OR_ASSET_404=0
DEFECTS=NONE
```

Подтверждено:

- без фильтров отображаются 17 записей;
- при `record_type=all` и выбранной организации отображаются только программы этой организации;
- нормативные примеры исключаются при выбранной организации;
- сочетание `record_type=direct-disclosure + organization` возвращает пустое состояние;
- сочетание `record_type=training-program + organization` возвращает программы выбранной организации;
- сброс возвращает полный набор из 17 записей;
- ошибок Console и HTTP/asset 404 нет.

## Итоговые markers

```text
MANUAL_DESKTOP_ACCEPTANCE_STATUS=PASS
TARGETED_MANUAL_DESKTOP_RECHECK_STATUS=PASS
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
PR_STATUS=OPEN_20_NOT_MERGED
MERGE_STATUS=NOT_AUTHORIZED
BRANCH_DELETION_STATUS=NOT_AUTHORIZED
```

Evidence-only файл не изменяет runtime, migration, seed, repository-контракт или theme assets. Повторный deploy и повторный Automated Testing после этого commit не требуются.

Следующий gate — повторный Final PR Review PR #20. Merge и удаление ветки требуют отдельных явных разрешений владельца проекта.
