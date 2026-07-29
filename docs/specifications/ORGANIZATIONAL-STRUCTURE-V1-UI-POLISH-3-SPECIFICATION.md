# Specification: Organizational Structure v1 UI Polish 3

## Статус

```text
PHASE: SPECIFICATION
STATUS: APPROVED BY USER
DATE: 2026-07-29
```

## Scope

Доработка ограничена деревом организационной структуры и его presentation-layer.

## 1. Единая строка действий

Для каждого редактируемого узла должна существовать одна стабильная строка действий.

Обязательные controls:

- `Выше`;
- `Ниже`;
- `Переместить` для некорневого узла;
- `Изменить`;
- `Добавить дочерний`;
- `Удалить` для некорневого узла.

Требования:

1. Все controls имеют одинаковую высоту `44px` через общий CSS variable contract.
2. Текст и иконки выровнены по центру.
3. Открытие панели не перемещает triggers на новую строку.
4. Action bar может переноситься только из-за фактической нехватки ширины, но не из-за состояния open/closed.

## 2. Кнопки порядка

`Выше` и `Ниже` получают декоративные иконки:

- `organization-direction-icon--up`;
- `organization-direction-icon--down`.

Требования:

- `aria-hidden="true"`;
- без внешних SVG/PNG/data URL;
- текущие `name="direction"`, values `up/down` и POST endpoint сохраняются.

## 3. Action triggers

`Переместить`, `Изменить`, `Добавить дочерний`, `Удалить` становятся `button type="button"` с:

- `data-node-action-target`;
- `aria-controls`;
- `aria-expanded="false"`.

Каждый target ID должен быть уникальным для узла и действия.

## 4. Action panels

Под action bar располагается `organization-node-action-panels`.

Каждая панель:

- имеет уникальный `id`;
- содержит `data-node-action-panel`;
- изначально имеет `hidden`;
- содержит существующую POST-форму без изменения endpoint, hidden inputs и field names.

Поведение:

1. Нажатие trigger открывает связанную панель.
2. Повторное нажатие закрывает её.
3. Открытие другой панели того же узла закрывает предыдущую.
4. `aria-expanded` синхронизируется.
5. Панели другого узла не изменяются.

## 5. Перемещение

Move panel сохраняет:

- `/admin/organization/nodes/move.php`;
- `csrf_token`;
- `structure_id`;
- `version_id`;
- `node_id`;
- `expected_revision`;
- `parent_node_id`;
- submit button `Переместить`.

## 6. Редактирование и добавление

Edit/add panels сохраняют текущие поля, limits, required attributes, endpoints и revision contracts.

## 7. Удаление

Delete trigger остаётся `Удалить`.

В delete panel:

- отображается пояснение, что действие необратимо для текущего черновика;
- для узла с дочерними элементами сохраняются `confirm_subtree` и `reason`;
- финальная submit button называется `Подтвердить удаление`;
- endpoint `/admin/organization/nodes/delete.php` и все hidden fields сохраняются.

## 8. CSS contract

Обязательные selectors:

- `.organization-node-action-bar`;
- `.organization-node-action-bar > form`;
- `.organization-node-action-trigger`;
- `.organization-node-action-panels`;
- `.organization-node-action-panel`;
- `.organization-direction-icon`;
- `.organization-direction-icon--up`;
- `.organization-direction-icon--down`.

Требования:

- action controls используют `height` и `min-height: var(--organization-action-height)`;
- panel занимает ширину строки и не влияет на положение trigger;
- hidden panel не отображается;
- danger panel сохраняет thematic danger styling;
- CSS идентичен в трёх темах.

## 9. JavaScript contract

`organization-ui-controls.js` расширяется event delegation для `[data-node-action-target]`.

Запрещено:

- inline event handlers;
- form submit из toggle logic;
- внешние библиотеки;
- изменение tree search/collapse behavior.

## 10. Automated contract

UI checker обязан проверить:

- отсутствие старых node-action `details`;
- наличие стабильного action bar и panel container;
- четыре trigger variants;
- уникальные target/panel ID patterns;
- direction icons;
- `Подтвердить удаление`;
- JS toggle/hidden/aria contract;
- единый height contract;
- сохранность CSRF, revision counts и POST endpoints;
- идентичность CSS трёх тем.

## 11. Acceptance

Manual desktop acceptance во всех трёх темах подтверждает:

1. одинаковую высоту controls;
2. стрелки `Выше/Ниже`;
3. неподвижность trigger buttons;
4. panel ниже action bar;
5. понятное подтверждение удаления;
6. клавиатурное управление;
7. отсутствие console errors и asset 404.

Mobile testing не входит в scope.
