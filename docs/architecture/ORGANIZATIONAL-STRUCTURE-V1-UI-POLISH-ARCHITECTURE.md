# Architecture: Organizational Structure v1 UI Polish

## Статус

```text
BASELINE: 891add3ab379808243b8e5fe670bd9353be4769f
PHASE: ARCHITECTURE
IMPLEMENTATION: NOT STARTED
APPROVAL: REQUIRED
```

## Контекст

Функциональное и RBAC-тестирование фактической организационной структуры v1 пройдено. Ручной desktop acceptance выявил четыре визуальных проблемы:

1. browser autofill перекрашивает поля в белый цвет;
2. часть `<summary>` имеет текстовый курсор;
3. нативные disclosure-треугольники не соответствуют визуальному языку тем;
4. стандартный чёрный значок `input[type=date]` не соответствует теме.

Текущая реализация использует нативные `<details>/<summary>` для раскрытия форм и нативные date-input. Бизнес-логика, маршруты, CSRF, RBAC и серверные сервисы работают корректно и не требуют изменения.

## Цель архитектуры

Выполнить ограниченную theme-aware доработку presentation layer без изменения предметной модели и JavaScript-состояния форм.

## Архитектурные решения

### 1. Сохранение `<details>/<summary>`

Нативная семантика раскрытия сохраняется:

- управление доступно мышью и клавиатурой;
- состояние `open` остаётся браузерным;
- JavaScript для открытия и закрытия форм не добавляется;
- screen reader продолжает получать нативную disclosure-семантику.

Нативный marker скрывается только визуально. Сам `<summary>` оформляется как кнопка.

### 2. Явные варианты disclosure-кнопок

Для `<summary>` вводятся presentation-классы:

- `organization-disclosure` — базовая кнопка раскрытия;
- `organization-disclosure--edit` — карандаш;
- `organization-disclosure--add` — знак `+`;
- `organization-disclosure--danger` — опасное действие;
- базовый вариант без модификатора — chevron для перемещения или общего раскрытия.

Видимый текст действия сохраняется. Иконка декоративная и не заменяет подпись.

### 3. Theme-aware иконки через CSS mask

Chevron, plus, pencil и calendar реализуются как небольшие CSS mask-иконки:

- цвет берётся из theme variables (`--focus-color`, `--text-primary` или danger palette);
- внешние изображения и сетевые зависимости отсутствуют;
- одинаковая HTML-структура работает во всех трёх темах;
- иконки не являются интерактивными отдельно от кнопки.

### 4. Защита theme palette при browser autofill

Для input в организационных формах добавляется ограниченный набор WebKit autofill selectors:

- `:-webkit-autofill`;
- `:-webkit-autofill:hover`;
- `:-webkit-autofill:focus`;
- `:-webkit-autofill:active`.

Фон восстанавливается через inset box-shadow с `var(--input-background)`, текст — через `-webkit-text-fill-color: var(--text-primary)`, caret — через `caret-color`.

Правило ограничивается organizational UI и не затрагивает остальные разделы.

### 5. Theme-aware date control

Нативный date picker сохраняется. Для date-полей вводится wrapper:

```html
<span class="organization-date-control">
    <input type="date" ...>
    <span class="organization-date-icon" aria-hidden="true"></span>
</span>
```

Архитектура:

- нативный `::-webkit-calendar-picker-indicator` остаётся кликабельным, но визуально прозрачен;
- поверх его области отображается theme-aware calendar mask;
- декоративная иконка имеет `pointer-events: none`;
- input получает правый внутренний отступ;
- keyboard и native picker behavior сохраняются.

### 6. Область представлений

Изменения ограничиваются:

- `public/admin/organization/views/summary-navigation.php`;
- `public/admin/organization/views/tree.php`;
- `public/admin/organization/views/documents.php`;
- `public/admin/organization/views/versions.php`;
- `themes/asu-blue/assets/css/organization.css`;
- `themes/asu-light-blue/assets/css/organization.css`;
- `themes/asu-evgeniya-rostova/assets/css/organization.css`;
- статическими/integration checker-файлами, необходимыми для проверки presentation-контракта.

## Неизменяемая область

Не изменяются:

- schema и migrations;
- Organization service/repository/traits;
- HTTP routes и POST contracts;
- permissions и RBAC;
- CSRF;
- lifecycle структуры и версий;
- JavaScript дерева;
- mobile acceptance scope.

## Риски и меры

### Риск: потеря нативной доступности details/summary

Мера: не заменять `<summary>` на произвольный `<div>` или JS-кнопку.

### Риск: date picker перестанет открываться мышью

Мера: native indicator не удаляется через `display: none`; используется прозрачность и overlay с `pointer-events: none`.

### Риск: theme drift

Мера: одинаковый structural CSS во всех трёх `organization.css`, цвета только через variables.

### Риск: autofill fix затронет другие экраны

Мера: selectors начинаются с organization-контейнеров/classes.

## Deployment impact

Новых зависимостей, миграций и конфигурации нет. Используется существующий утверждённый `deploy/Deploy-Local.ps1`.

## Gate

Architecture подготовлена. Переход к Specification разрешён; переход к Implementation запрещён до утверждения Specification и Review пользователем.
