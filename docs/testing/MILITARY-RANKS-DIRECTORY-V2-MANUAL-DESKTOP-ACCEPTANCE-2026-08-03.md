# Manual Desktop Acceptance — Справочник воинских званий v2

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

## Проверенные сценарии

- текущая версия `rf-military-ranks-staffing-scopes-v2`;
- историческая версия `rf-military-ranks-2026-07-27`;
- переключение версий;
- утверждённые количества по всем фильтрам v1/v2;
- поиск, empty state и reset;
- read-only UI без mutation controls;
- два официальных источника и открытие ссылок в новой вкладке;
- HTTP 403 для пользователя без owner-доступа;
- console errors: 0;
- HTTP/asset 404: 0.

## Visual remediation evidence

Первичная проверка выявила растягивание карточек и неясную parent/child иерархию. После исправления подтверждены одна start-aligned колонка, компактные карточки, indentation/connectors и короткие дочерние заголовки во всех трёх темах.

## Scope note

Mobile runtime testing исключено из области и не выполнялось. Mobile PASS не заявляется.

## Решение

```text
DEFECTS=NONE
FINAL_RESULT=PASS
```

Документ не разрешает merge; требуется отдельное явное решение владельца.
