# Implementation: Organizational Structure v1 UI Polish 3

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
a3d1f3a8a6a96dfbfcab4e7de890a8cbde245475
```

## Реализовано

### Стабильная строка действий

В `tree.php` раскрываемые node actions больше не используют `details`.

Каждый узел содержит:

- `organization-node-action-bar` со стабильными buttons;
- `organization-node-action-panels` с формами ниже строки.

Открытие panel не изменяет положение trigger buttons.

### Единая высота

`Выше`, `Ниже`, `Переместить`, `Изменить`, `Добавить дочерний` и `Удалить` используют общий `--organization-action-height: 44px`.

### Стрелки порядка

Кнопки `Выше` и `Ниже` получили декоративные CSS arrows:

- `organization-direction-icon--up`;
- `organization-direction-icon--down`.

POST endpoint, `direction=up/down` и hidden fields не менялись.

### Panels

Для каждого узла создаются уникальные IDs:

- `organization-node-move-{id}`;
- `organization-node-edit-{id}`;
- `organization-node-add-{id}`;
- `organization-node-delete-{id}`.

Triggers используют `data-node-action-target`, `aria-controls` и `aria-expanded`. Panels используют `data-node-action-panel` и `hidden`.

### JavaScript

`organization-ui-controls.js` расширен:

- event delegation для node action triggers;
- открытие/закрытие связанного panel;
- закрытие sibling panels только текущего узла;
- синхронизация `hidden` и `aria-expanded`;
- отсутствие form submit в toggle logic;
- прежний calendar picker contract сохранён.

### Удаление

Кнопка `Удалить` остаётся в action bar и открывает danger confirmation panel.

Финальная destructive button переименована в `Подтвердить удаление`. Существующие `confirm_subtree`, `reason`, CSRF, expected revision и endpoint сохранены.

### Темы

Structural CSS идентичен во всех трёх темах. Git blob SHA:

```text
cc6c4ec0a133aa84a7469baaaeb3e0ea3585d8a3
```

### Automated contract

`check-organizational-structure-ui-polish.php` теперь проверяет:

- отсутствие старых `details` в tree actions;
- action bar/panels structure;
- четыре trigger/panel binding;
- direction icons и сохранность reorder POST contract;
- безопасное подтверждение удаления;
- JS `hidden/aria-expanded` contract;
- стабильный CSS layout без старого `details[open]` flex rule;
- единый height contract;
- прежние date, autofill, pencil, CSRF, revision и POST contracts;
- идентичность CSS трёх тем.

## Code review

Diff относительно approved baseline ограничен:

- тремя утверждёнными документами;
- `tree.php`;
- `organization-ui-controls.js`;
- `organization.css` трёх тем;
- UI checker.

БД, migrations, domain services, RBAC, routes и tree search/collapse JS не изменялись.

## Следующий gate

Требуется полный Windows/Open Server fail-fast runner с новым backup и deploy. После automated PASS требуется manual desktop visual acceptance во всех трёх темах.

Mobile testing не входит в scope.
