# Implementation: Organizational Structure v1 UI Polish 2

## Статус

```text
APPROVED BASELINE: f18308f9f3521d41eacb79c148dcecf1b684019e
PHASE: IMPLEMENTATION COMPLETE
TESTING: REQUIRED
DEPLOY: NOT RUN FOR THIS HEAD
PR: NOT CREATED
```

## Реализовано

### Height normalization

В organization CSS введён `--organization-action-height: 44px`.

Одинаковую высоту получают соседние controls в:

- `.organization-actions`;
- `.organization-version-controls`.

Контракт охватывает disclosure, input/date control, primary и danger buttons. Form/details выравниваются через `align-self: flex-end`.

### Pencil icon

`organization-disclosure--edit` теперь рисует карандаш CSS-псевдоэлементами `::before` и `::after`. Внешние assets и data URL не используются.

### Functional calendar

Три date field получили:

- явный уникальный `id`;
- отдельный label с `for`;
- `button type="button"`;
- `data-date-picker-target`;
- доступный `aria-label`;
- theme-aware decorative icon внутри button.

Добавлен `public/assets/js/organization-ui-controls.js`:

- event delegation;
- focus target input;
- `showPicker()` при поддержке;
- fallback `click()`;
- guard disabled/readonly;
- отсутствие submit behavior.

Script подключён в organization layout с `defer`.

### Themes

Structural CSS идентичен во всех трёх темах. Git blob SHA после изменения одинаков:

```text
e9edaa4d200da273f80a0efd6ca063771becc748
```

### Automated contract

`tools/check-organizational-structure-ui-polish.php` расширен и проверяет:

- три functional calendar button;
- target bindings и уникальные id patterns;
- label/input semantics;
- script inclusion;
- showPicker/fallback/focus/no-submit contract;
- action height contract;
- pencil geometry;
- CSS identity трёх тем;
- прежние CSRF/POST/revision invariants.

## Targeted review

- `node --check public/assets/js/organization-ui-controls.js` — PASS в review environment.
- Compare с approved baseline содержит только organization views/layout, новый organization JS, CSS трёх тем и UI checker.

Это не заменяет authoritative Windows/Open Server full runner.

## Неизменённая область

- schema/migrations;
- domain services/repositories;
- routes/POST payload;
- CSRF;
- RBAC;
- revision/lifecycle;
- tree behavior;
- mobile acceptance scope.

## Следующий gate

Требуется полный Windows fail-fast test с новым backup, deploy, PHP lint, UI checker, migrations, organization/security/theme/directory regressions, HTTP smoke и post-test integrity. После automated PASS требуется desktop visual acceptance во всех трёх темах.
