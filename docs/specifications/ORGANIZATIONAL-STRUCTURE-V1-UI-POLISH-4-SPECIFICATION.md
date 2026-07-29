# Specification: Organizational Structure v1 UI Polish 4

## Статус

```text
DATE: 2026-07-29
PHASE: SPECIFICATION
STATUS: APPROVED
```

## 1. Search width alignment

### Разметка

В `organization-tree-tools` должны существовать:

- один `organization-tree-tool-buttons` с кнопками `data-tree-expand` и `data-tree-collapse`;
- один label `organization-tree-search` с неизменённым input `data-tree-search`.

### CSS contract

- `organization-tree-tools` использует grid с одной content-sized колонкой;
- button group использует две content-sized колонки и существующий gap;
- search label и input занимают `100%` ширины общего tools-контейнера;
- левый край input совпадает с левым краем `Раскрыть всё`;
- правый край input совпадает с правым краем `Свернуть всё`.

Функциональность поиска, placeholder и `maxlength` не меняются.

## 2. Unified edit pencil

### Требования

- node-action `Изменить` сохраняет class `organization-disclosure--edit`;
- icon slot и псевдоэлементы остаются общими с `Изменить карточку`;
- node-specific CSS задаёт `transform: rotate(-45deg)`;
- открытое состояние edit panel не изменяет наклон;
- icon не должен визуально читаться как минус.

Add-action продолжает использовать `transform: none`.

## 3. Visible tree toggle

### Требования

`tree-toggle`:

- сохраняет `type="button"`, `data-tree-toggle`, `aria-label` и disabled contract;
- сохраняет glyph `▾` в исходной разметке;
- существующий JavaScript продолжает переключать `▾` / `▸` и `aria-expanded`;
- использует увеличенный glyph и высокий font-weight;
- использует `var(--focus-color)` и theme-aware background;
- имеет заметные hover и `focus-visible` states;
- disabled state остаётся визуально различимым и не интерактивным.

## 4. Invariants

Не меняются:

- `organization-tree.js`;
- `data-tree-expand`, `data-tree-collapse`, `data-tree-search`;
- node POST endpoints;
- CSRF inputs;
- expected revision inputs;
- RBAC;
- tree search, expand/collapse и action-panel behavior;
- БД и migrations.

## 5. Automated checks

UI checker должен подтвердить:

1. header содержит отдельные button group и search label;
2. search input contract сохранён;
3. CSS обеспечивает общий width contract;
4. node edit icon использует `rotate(-45deg)` и не сбрасывается в `none`;
5. add icon сохраняет `transform: none`;
6. tree toggle использует thematic visibility contract;
7. structural CSS идентичен во всех трёх темах;
8. прежние UI Polish 1–3 contracts продолжают проходить.

## 6. Manual desktop acceptance

Во всех трёх темах проверить:

- search input визуально выровнен по внешним границам двух кнопок;
- node edit pencil совпадает с `Изменить карточку`;
- toggle triangle заметен в expanded/collapsed состояниях;
- поиск, раскрытие и сворачивание дерева работают;
- console errors и asset 404 отсутствуют.

Mobile testing не входит в scope.
