# Architecture: Organizational Structure v1 UI Polish 2

## Статус

```text
BASELINE: 8db3b3db4cb0dbd8d02c11b004468f5a8e5d7ec2
PHASE: ARCHITECTURE
USER APPROVAL: RECEIVED
IMPLEMENTATION: NOT STARTED
```

## Контекст

После automated PASS для первого UI polish пользователь выполнил desktop visual review и отметил три presentation-дефекта:

1. соседние action controls имеют разную высоту и визуально «прыгают»;
2. edit-icon недостаточно однозначно читается как карандаш;
3. theme-aware calendar icon является декоративной и не гарантирует открытие native date picker при клике.

## Цель

Исправить только presentation/UI layer организационной структуры без изменения БД, маршрутов, POST payload, CSRF, RBAC, revision logic и предметных сервисов.

## Решения

### Единая высота action controls

Вводится единый CSS contract высоты для controls в `.organization-actions` и `.organization-version-controls`:

- disclosure summary;
- input/date control;
- primary/danger action button.

Controls выравниваются по нижней границе строки, имеют одинаковую фиксированную высоту и не меняют вертикальный ритм при переключении аналогичных действий.

### Однозначная pencil icon

Edit-вариант сохраняет существующий класс `organization-disclosure--edit`, но получает новую чистую CSS-геометрию карандаша через псевдоэлементы. Внешние SVG и сетевые assets не добавляются.

### Функциональный calendar trigger

Нативный `input[type=date]` сохраняется. Декоративный overlay заменяется на доступную кнопку `type="button"`, связанную с конкретным date input через `data-date-picker-target`.

Новый небольшой script:

1. получает target input;
2. переводит в него focus;
3. вызывает `showPicker()` при поддержке;
4. использует `click()` как fallback.

Кнопка доступна мышью, Enter и Space. Native picker и ручной ввод даты сохраняются.

## Область файлов

- `public/admin/organization/views/documents.php`;
- `public/admin/organization/views/versions.php`;
- `public/admin/organization/views/layout-end.php`;
- новый `public/assets/js/organization-ui-controls.js`;
- `themes/*/assets/css/organization.css` для трёх зарегистрированных тем;
- UI polish checker и testing documentation.

## Неизменяемая область

- schema/migrations;
- Organization repositories/services/traits;
- HTTP routes;
- field names и hidden inputs;
- CSRF;
- RBAC;
- lifecycle/revision;
- tree behavior;
- mobile acceptance scope.

## Риски и меры

- `showPicker()` поддерживается не везде: предусмотрен focus/click fallback.
- Button внутри label может нарушить semantics: date field получает отдельный label с `for`, button размещается рядом с input.
- Theme drift: structural `organization.css` остаётся идентичным во всех трёх темах.
- JS regression: script ограничен `[data-date-picker-target]` и не вмешивается в submit/forms.
