# Review: Organizational Structure v1 UI Polish

## Статус

```text
BASELINE: 891add3ab379808243b8e5fe670bd9353be4769f
PHASE: REVIEW
TECHNICAL VERDICT: READY FOR USER APPROVAL
IMPLEMENTATION: NOT STARTED
```

## Проверенные материалы

- `docs/testing/ORGANIZATIONAL-STRUCTURE-V1-MANUAL-ACCEPTANCE-01.md`;
- `docs/architecture/ORGANIZATIONAL-STRUCTURE-V1-UI-POLISH-ARCHITECTURE.md`;
- `docs/specifications/ORGANIZATIONAL-STRUCTURE-V1-UI-POLISH-SPECIFICATION.md`;
- текущие organization views;
- `organization.css` всех трёх тем.

## Подтверждение причины дефектов

### Autofill

Текущий CSS задаёт theme background обычным input, но не содержит `:-webkit-autofill` overrides. Chromium может применять собственный светлый background поверх theme styles.

### Cursor и disclosure marker

Текущий CSS задаёт `cursor: pointer` только части summary selectors. Summary в `.organization-actions` и `.organization-document-actions` не покрыты единым базовым классом. Нативный marker остаётся browser-dependent.

### Calendar icon

Date fields используют чистый `input[type=date]`, поэтому Chromium отображает собственный indicator, цвет которого не связан с theme variables.

## Review решений

### Сохранение details/summary — принято

Преимущества:

- нативная клавиатурная работа;
- отсутствие дополнительного JavaScript state;
- минимальный regression surface;
- сохранение семантики.

### CSS mask icons — принято

Преимущества:

- theme-aware цвет через variables;
- нет внешних assets;
- нет inline SVG-дублирования в каждом view;
- единый контракт трёх тем.

### Date wrapper с прозрачным native indicator — принято

Преимущества:

- сохраняет нативный picker;
- custom icon не перехватывает click;
- не требует JavaScript.

### Scoped autofill override — принято

Преимущества:

- исправляет наблюдаемый Chromium defect;
- не изменяет глобально login/account/users forms;
- использует существующие theme variables.

## Отклонённые альтернативы

### Замена details/summary на JS accordion

Отклонено: увеличивает сложность, требует управления ARIA/state и создаёт лишний regression risk.

### Icon-only controls без текста

Отклонено: ухудшает понятность и accessibility.

### `display: none` для calendar picker indicator

Отклонено: может убрать кликабельную область нативного picker.

### Отдельные SVG-файлы для каждой темы

Отклонено: создаёт ненужные assets и риск theme drift.

## Scope review

Спецификация не затрагивает:

- БД и migrations;
- domain services;
- routes и POST payload;
- RBAC/CSRF;
- JavaScript дерева;
- mobile testing.

Scope соответствует UI polish и не превращается в новый функциональный инкремент.

## Testing review

Предусмотрены:

- static presentation-contract checks;
- PHP lint;
- полный существующий fail-fast runner;
- повторный manual desktop acceptance для всех трёх тем;
- отдельная проверка autofill, cursor, keyboard и date picker.

Набор достаточен для перехода к реализации после утверждения.

## Вывод

Architecture и Specification согласованы между собой, изменения ограничены presentation layer, риски контролируемы.

**Технический вердикт: READY FOR USER APPROVAL.**

До явного утверждения пользователя запрещены:

- изменение PHP views;
- изменение theme CSS;
- добавление checker-кода реализации;
- deploy и повторное тестирование;
- создание Pull Request.
