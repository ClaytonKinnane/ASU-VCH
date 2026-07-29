# Implementation: Organizational Structure v1 UI Polish 4

## Статус

```text
DATE: 2026-07-29
PHASE: IMPLEMENTATION COMPLETE
TESTING: REQUIRED
DEPLOY: NOT RUN FOR THIS HEAD
PR: NOT CREATED
```

## Approved baseline

```text
87c2db591b724290cedf48b46257d5d6f1b73ac3
```

## Реализовано

### Search width alignment

`tree.php` теперь содержит:

- `organization-tree-tool-buttons` с `Раскрыть всё` / `Свернуть всё`;
- `organization-tree-search` с неизменённым `data-tree-search`.

CSS использует один content-sized grid track. Верхний button group определяет внешнюю ширину tools-блока, а search label/input растягивается на эту ширину.

### Unified node edit pencil

Устранён specificity conflict:

```text
organization-node-action-trigger + organization-disclosure--edit
```

теперь явно использует `transform: rotate(-45deg)`. Add-trigger отдельно сохраняет `transform: none`.

Иконка node-action `Изменить` использует ту же геометрию псевдоэлементов, что и `Изменить карточку`.

### Visible tree toggle

`tree-toggle` получил:

- border и color через `var(--focus-color)`;
- theme-aware `color-mix` background;
- `font-size: 18px`;
- `font-weight: 900`;
- явный hover state.

Исходный glyph `▾`, переключение `▾` / `▸`, `aria-expanded`, search и collapse JavaScript не изменялись.

### Themes

Structural CSS идентичен во всех трёх темах. Git blob SHA:

```text
0ac2c4029dec673148b5760c10fb9c2281588d0e
```

### Automated contract

`check-organizational-structure-ui-polish.php` дополнительно проверяет:

- отдельные button group и search label;
- неизменность `data-tree-expand`, `data-tree-collapse`, `data-tree-search`, maxlength и placeholder;
- native tree-toggle markup;
- сохранение glyph/aria/search contract в `organization-tree.js`;
- search width CSS contract;
- точный наклон node edit pencil и отсутствие прежнего объединённого reset-rule;
- theme-aware toggle visibility;
- прежние UI Polish 1–3, CSRF, revision и POST contracts.

Ожидаемое число static checks после изменения:

```text
PASS: 64
FAIL: 0
```

## Code review

Diff относительно approved baseline ограничен:

- утверждёнными Architecture / Specification / Review;
- одной строкой heading markup в `tree.php`;
- structural `organization.css` трёх тем;
- UI checker;
- данным implementation record.

Не изменялись:

- `organization-tree.js`;
- `organization-ui-controls.js`;
- БД и migrations;
- domain services;
- routes и forms;
- CSRF/revision/RBAC;
- mobile acceptance scope.

## Следующий gate

Требуется полный Windows/Open Server fail-fast runner с новым backup и deploy. После automated PASS требуется manual desktop visual acceptance во всех трёх темах.
