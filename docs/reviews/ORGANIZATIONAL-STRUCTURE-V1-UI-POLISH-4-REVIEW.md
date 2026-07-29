# Review: Organizational Structure v1 UI Polish 4

## Статус

```text
DATE: 2026-07-29
ARCHITECTURE: PASS
SPECIFICATION: PASS
BLOCKING FINDINGS: 0
STATUS: APPROVED FOR IMPLEMENTATION
```

## Проверка scope

Решение ограничено presentation-layer:

- layout header дерева;
- CSS трёх тем;
- static UI checker.

Server-side actions, domain logic, security и schema не затрагиваются.

## Проверка search layout

Grid-контейнер с content-sized button group и full-width search field является детерминированным способом совместить внешние границы controls. Решение не зависит от фиксированного pixel width и сохраняет текст кнопок.

## Проверка edit icon

Причина дефекта установлена: node-specific selector с более высокой специфичностью применяет `transform: none` к edit icon. Разделение правил edit/add устраняет конфликт без дублирования icon markup.

## Проверка tree toggle

Сохранение существующих glyph `▾` / `▸` исключает изменение tree JavaScript. Усиление размера, веса, цвета и background остаётся theme-aware и не меняет accessibility contract кнопки.

## Риски и меры

### R1. Search group переполняет узкий viewport

Desktop scope использует content-sized ширину. Существующий media query на малой ширине переводит tools в `width: 100%`; новый contract должен дополнительно разрешить button group и search field занимать доступную ширину.

### R2. Pencil снова сбрасывается open-state rule

Static checker должен требовать node edit selector с `rotate(-45deg)` и запрещать объединённое edit/add правило с `transform: none`.

### R3. Toggle становится слишком ярким

Используются только `var(--focus-color)` и мягкий `color-mix`, без жёстко заданного цвета.

## Invariants

Review требует сохранить:

- tree JS без изменений;
- один search input;
- прежние node action panel contracts;
- CSRF/revision/POST checks;
- идентичность CSS трёх тем.

## Verdict

```text
READY FOR IMPLEMENTATION
USER APPROVAL RECEIVED: YES
```
