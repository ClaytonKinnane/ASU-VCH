# Specification: Organizational Structure v1 UI Polish

## Статус

```text
BASELINE: 891add3ab379808243b8e5fe670bd9353be4769f
PHASE: SPECIFICATION
IMPLEMENTATION: NOT STARTED
APPROVAL: REQUIRED
```

## 1. Область

Доработка устраняет UI-01—UI-04 из `ORGANIZATIONAL-STRUCTURE-V1-MANUAL-ACCEPTANCE-01.md` и не изменяет бизнес-поведение организационной структуры.

## 2. Функциональные требования

### UI-FR-01. Autofill сохраняет тему

Все text/search/date inputs внутри organizational UI при browser autofill должны сохранять:

- фон `var(--input-background)`;
- цвет текста `var(--text-primary)`;
- цвет caret, совместимый с темой;
- существующую рамку и focus outline.

Белый autofill background в тёмной теме недопустим.

### UI-FR-02. Все элементы раскрытия имеют pointer cursor

Вся область каждого `<summary>` в organizational UI должна:

- иметь `cursor: pointer`;
- иметь hover state;
- иметь keyboard-visible focus;
- не показывать text-selection cursor как основной интерактивный курсор.

Disabled summary не предусматривается.

### UI-FR-03. Нативный marker скрыт

Для всех дорабатываемых `<summary>`:

- стандартный marker/triangle визуально скрыт;
- семантика `<details>/<summary>` сохранена;
- состояние `details[open]` визуально различимо.

### UI-FR-04. Типы disclosure-кнопок

Следующие действия получают типизированные кнопки:

| Экран | Действие | Вариант | Иконка |
|---|---|---|---|
| Карточка | Изменить карточку | edit | pencil |
| Документы | Изменить | edit | pencil |
| Документы | Добавить документ-основание | add | plus |
| Дерево | Изменить | edit | pencil |
| Дерево | Добавить дочерний | add | plus |
| Дерево | Переместить | default | chevron |
| Дерево | Удалить | danger | chevron/danger presentation |

Видимый текст сохраняется во всех случаях.

### UI-FR-05. Theme-aware calendar icon

Следующие date-поля получают собственный calendar icon:

- дата документа при создании;
- дата документа при редактировании;
- дата вступления версии в действие.

Требования:

- стандартный чёрный indicator визуально не отображается;
- icon использует theme variable;
- native date picker продолжает открываться мышью и клавиатурой;
- icon не перехватывает pointer events;
- дата остаётся доступной для ввода вручную там, где это разрешает браузер.

### UI-FR-06. Состояния кнопки

Disclosure-кнопка должна иметь:

- normal;
- hover;
- focus-visible;
- open;
- danger normal/hover/focus.

Open state должен изменять направление chevron или иным очевидным образом показывать раскрытое состояние.

### UI-FR-07. Три темы

Требования выполняются для:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Структура CSS должна быть одинаковой; различия цветов задаются существующими variables.

## 3. HTML-контракт

### 3.1 Disclosure

Рекомендуемая структура:

```html
<summary class="organization-disclosure organization-disclosure--edit">
    <span class="organization-disclosure-icon" aria-hidden="true"></span>
    <span>Изменить карточку</span>
</summary>
```

Для add используется `organization-disclosure--add`, для danger — `organization-disclosure--danger`.

### 3.2 Date

```html
<label class="organization-date-label">
    <span>Дата</span>
    <span class="organization-date-control">
        <input type="date" ...>
        <span class="organization-date-icon" aria-hidden="true"></span>
    </span>
</label>
```

Допускается эквивалентная разметка при сохранении accessibility и native picker.

## 4. CSS-контракт

Обязательные selectors:

- `.organization-disclosure`;
- `.organization-disclosure::marker`;
- `.organization-disclosure::-webkit-details-marker`;
- `.organization-disclosure-icon`;
- `.organization-disclosure--edit`;
- `.organization-disclosure--add`;
- `.organization-disclosure--danger`;
- `details[open] > .organization-disclosure`;
- `.organization-date-control`;
- `.organization-date-icon`;
- organizational input `:-webkit-autofill` states;
- organizational `input[type="date"]::-webkit-calendar-picker-indicator`.

## 5. Нефункциональные требования

### Accessibility

- visible text не заменяется icon-only control;
- focus-visible не хуже текущего;
- Enter/Space раскрывают `<details>`;
- decorative icons имеют `aria-hidden="true"`;
- contrast определяется существующей theme palette.

### Security

- POST endpoints, CSRF fields и hidden revision fields не меняются;
- inline event handlers не добавляются;
- внешние SVG/URL не используются.

### Compatibility

- Windows 10 desktop;
- Chromium-based browser в текущем пользовательском окружении;
- PHP 8.5 / Apache / OSP 6.5.1 без изменений.

## 6. Проверки реализации

### Automated

1. PHP lint всех изменённых PHP-файлов.
2. Static checker подтверждает:
   - наличие вариантов disclosure в ожидаемых views;
   - наличие date wrappers для трёх date-полей;
   - наличие autofill и calendar selectors во всех трёх theme CSS;
   - отсутствие удалённых CSRF/hidden fields.
3. Полный `Test-OrganizationalStructureV1.ps1` — PASS.
4. Existing organization checker — `PASS 58 / FAIL 0` или больше PASS без FAIL.
5. Themes/directories regression — PASS.

### Manual desktop

Во всех трёх темах:

1. открыть и закрыть каждую форму мышью;
2. открыть и закрыть каждую форму Enter/Space;
3. проверить pointer cursor на всём summary;
4. проверить pencil/plus/chevron/danger variants;
5. проверить focus-visible;
6. вызвать browser autofill и убедиться, что фон не становится белым;
7. открыть date picker нажатием на theme-aware calendar icon;
8. проверить отсутствие стандартного чёрного calendar icon;
9. проверить отсутствие asset 404 и console errors.

## 7. Acceptance criteria

Доработка принимается только при одновременном выполнении:

- UI-01—UI-04 устранены;
- все automated проверки PASS;
- повторный desktop visual acceptance подтверждён пользователем для трёх тем;
- mobile PASS не заявляется;
- Test Report обновлён;
- PR создаётся только после отдельного разрешения пользователя.
