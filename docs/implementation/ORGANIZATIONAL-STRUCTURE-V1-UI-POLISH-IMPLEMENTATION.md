# Implementation: Organizational Structure v1 UI Polish

## Статус

```text
APPROVED BASELINE: aa73038bdd2037d236a4eca7f3eac7386aeae295
PHASE: IMPLEMENTATION COMPLETE
TESTING: REQUIRED
DEPLOY: NOT RUN
PR: NOT CREATED
```

## Реализованный scope

### Views

Изменены только presentation-файлы:

- `public/admin/organization/views/summary-navigation.php`;
- `public/admin/organization/views/tree.php`;
- `public/admin/organization/views/documents.php`;
- `public/admin/organization/views/versions.php`.

Добавлены семь типизированных disclosure-кнопок:

- edit: карточка структуры, документ, узел;
- add: документ-основание, дочерний узел;
- default chevron: перемещение узла;
- danger: удаление узла.

Видимый текст действий и нативные `<details>/<summary>` сохранены.

Добавлены три theme-aware date wrapper:

- создание документа;
- редактирование документа;
- дата вступления версии в действие.

POST endpoints, CSRF fields, hidden identifiers и `expected_revision` не изменялись.

### Themes

Одинаковый structural CSS добавлен в:

- `themes/asu-blue/assets/css/organization.css`;
- `themes/asu-light-blue/assets/css/organization.css`;
- `themes/asu-evgeniya-rostova/assets/css/organization.css`.

Реализованы:

- pointer/hover/focus/open состояния disclosure;
- скрытие native disclosure marker;
- theme-aware chevron, plus, pencil и calendar через CSS mask/gradients;
- danger presentation;
- scoped Chromium autofill override;
- прозрачный native calendar indicator с сохранением кликабельности;
- декоративный calendar icon с `pointer-events: none`.

Внешние CSS/SVG dependencies и JavaScript не добавлялись.

### Automated contract

Добавлен:

- `tools/check-organizational-structure-ui-polish.php`.

Checker подтверждает:

- варианты и количество disclosure;
- три date wrapper;
- сохранность CSRF и `expected_revision`;
- сохранность всех POST endpoints;
- отсутствие inline event handlers;
- идентичность organization CSS трёх тем;
- наличие autofill/calendar/mask selectors;
- отсутствие внешних CSS/SVG dependencies;
- подключение checker к полному runner.

`tools/Test-OrganizationalStructureV1.ps1` запускает checker на deploy-копии после полного PHP lint и до migration/regression этапов. UTF-8 BOM сохранён.

## Неизменённая область

Не менялись:

- schema и migrations;
- Organization services/repositories/traits;
- routes и POST payload;
- RBAC и permissions;
- CSRF implementation;
- JavaScript дерева;
- mobile acceptance scope.

## Следующий gate

Требуется полный локальный Testing:

1. PHP lint;
2. UI polish static checker;
3. полный fail-fast runner;
4. повторный manual desktop visual acceptance во всех трёх темах.

До automated PASS и повторного visual confirmation Pull Request не создаётся.
