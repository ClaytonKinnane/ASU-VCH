# Manual Desktop Acceptance — Составы военнослужащих и воинские звания v2

Дата: **2026-08-03**  
Результат: **PASS**  
Runtime head: `b44aed14ee1a54be213cbc939322ba21b02e7a58`

## Итог владельца

```text
MANUAL_DESKTOP_ACCEPTANCE

HEAD=b44aed14ee1a54be213cbc939322ba21b02e7a58

ASU_BLUE_1920=PASS
ASU_BLUE_1366=PASS
ASU_LIGHT_BLUE_1920=PASS
ASU_LIGHT_BLUE_1366=PASS
EVGENIYA_ROSTOVA_1920=PASS
EVGENIYA_ROSTOVA_1366=PASS

CURRENT_V2=PASS
HISTORICAL_V1=PASS
VERSION_SWITCHING=PASS
V2_COMPOSITION_COUNTS=PASS
V1_COMPOSITION_COUNTS=PASS
SEARCH_AND_EMPTY_STATE=PASS
READ_ONLY_UI=PASS
OFFICIAL_SOURCE_LINKS=PASS
NON_OWNER_HTTP_403=PASS

CONSOLE_ERRORS=0
HTTP_OR_ASSET_404=0
DEFECTS=NONE

MOBILE=OUT_OF_SCOPE / NOT_RUN
FINAL_RESULT=PASS
```

## Проверенные темы и размеры

| Тема | 1920×1080 | 1366×768 |
|---|---:|---:|
| АСУ Синяя | PASS | PASS |
| АСУ Светлая синяя | PASS | PASS |
| Евгения Ростова | PASS | PASS |

## Current v2

Проверено:

- code `rf-military-ranks-staffing-scopes-v2`;
- current published state;
- effective date `2026-08-03`;
- verification date `2026-08-02`;
- 8 composition/category cards;
- 20 ranks;
- derived badges только у soldiers/sailors и sergeants/starshinas;
- staffing-selectable badges у четырёх утверждённых categories;
- parent/child hierarchy без растянутых пустых карточек;
- concise child labels;
- no horizontal overflow.

Filter counts:

| Фильтр | Результат |
|---|---:|
| Все составы | 20 |
| Солдаты и матросы | 2 |
| Сержанты и старшины | 4 |
| Прапорщики и мичманы | 2 |
| Офицеры | 12 |
| Младшие офицеры | 4 |
| Старшие офицеры | 3 |
| Высшие офицеры | 5 |

## Historical v1

Проверено:

- code `rf-military-ranks-2026-07-27`;
- previous published state;
- valid_to `2026-08-02`;
- 6 composition cards;
- 20 ranks;
- derived/staffing badges отсутствуют;
- у каждой карточки показано, что пригодность для штатных должностей в исторической версии не определена;
- officer child hierarchy сохранена во всех трёх темах.

Filter counts:

| Фильтр | Результат |
|---|---:|
| Все составы | 20 |
| Солдаты, матросы, сержанты и старшины | 6 |
| Прапорщики и мичманы | 2 |
| Офицеры | 12 |
| Младшие офицеры | 4 |
| Старшие офицеры | 3 |
| Высшие офицеры | 5 |

## Search и empty state

Подтверждены:

- поиск `Маршал`;
- поиск `старшина 1 статьи`;
- заведомо отсутствующий запрос;
- тематический empty state;
- reset к 20 строкам;
- совместная работа версии, поиска и фильтра.

## Read-only и security

Подтверждены:

- badge «Только чтение»;
- отсутствие create/edit/delete controls;
- отсутствие mutation forms;
- два официальных источника;
- ссылки открываются в новой вкладке;
- non-owner получает HTTP 403 и тематическую страницу запрета.

## DevTools

```text
CONSOLE_ERRORS=0
HTTP_OR_ASSET_404=0
```

## Visual remediation evidence

Первый вариант composition grid получил FAIL из-за растягивания карточек и неясной иерархии.

После исправления подтверждено:

- одна start-aligned колонка;
- parent cards не растягиваются;
- child cards следуют под своим parent;
- connectors и indentation видимы;
- child labels не повторяют parent path;
- badges читаются во всех трёх темах.

## Scope note

Mobile runtime testing исключено из области и не выполнялось. Mobile PASS не заявляется.

## Решение

```text
DEFECTS=NONE
FINAL_RESULT=PASS
```

Инкремент допускается к фиксации evidence, Pull Request и Final PR Review. Merge не разрешён данным документом.
