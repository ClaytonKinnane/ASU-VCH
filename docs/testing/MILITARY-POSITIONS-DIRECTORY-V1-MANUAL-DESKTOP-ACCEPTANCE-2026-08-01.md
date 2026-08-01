# Manual Desktop Acceptance Evidence: Справочник типов воинских должностей ВС РФ v1

## Статус

```text
DATE: 2026-08-01
BRANCH: feature/military-positions-directory
TESTED RUNTIME HEAD: 0455f0120c881bb9ba6e9df8f80ea0af89819be9
AUTOMATED_TESTING_STATUS: PASS
MANUAL_DESKTOP_ACCEPTANCE_STATUS: PASS
MOBILE_TESTING_STATUS: OUT OF SCOPE / NOT RUN
DEFECTS_FOUND: NONE
PR: NOT CREATED
MERGE: NOT AUTHORIZED
```

Ручная desktop-приёмка выполнена владельцем системы после полного автоматизированного Testing PASS.

## Доступ и данные

```text
Owner access: PASS
Ordinary role HTTP 403: PASS
Default result count 34: PASS
Department-director has no false “отдел” relation: PASS
Official source links: PASS
```

## Поиск и фильтры

```text
Search: PASS
Family filter: PASS
Composition scope filter: PASS
Tariff grade filter: PASS
Organizational context filter: PASS
Combined filters: PASS
Variant details: PASS
```

## Темы и desktop-размеры

```text
asu-blue: PASS
asu-light-blue: PASS
asu-evgeniya-rostova: PASS
1920×1080: PASS
1366×768: PASS
```

## Browser diagnostics

```text
Console errors: NONE
Asset or HTTP 404: NONE
Defects found: NONE
```

## Итог

```text
MANUAL_DESKTOP_ACCEPTANCE_STATUS=PASS
MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN
```

Mobile runtime testing не выполнялось и Mobile PASS не заявляется.

## Следующий gate

Architecture, Specification, Formal Review, Implementation, automated Testing и manual desktop acceptance завершены. Для создания Pull Request требуется отдельное явное разрешение владельца. Merge и удаление ветки требуют последующих отдельных разрешений.
