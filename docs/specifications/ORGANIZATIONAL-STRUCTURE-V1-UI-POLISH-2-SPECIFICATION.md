# Specification: Organizational Structure v1 UI Polish 2

## Статус

```text
BASELINE: 8db3b3db4cb0dbd8d02c11b004468f5a8e5d7ec2
PHASE: SPECIFICATION
USER APPROVAL: RECEIVED
IMPLEMENTATION: NOT STARTED
```

## UI2-FR-01. Единая высота action controls

В `.organization-actions` и `.organization-version-controls` следующие controls должны иметь одну высоту `44px`:

- `.organization-disclosure`;
- text/date inputs в action row;
- `.primary-button`;
- `.danger-button`.

Controls должны быть выровнены по нижней границе строки. Label caption может располагаться выше, но сами интерактивные controls не должны быть выше или ниже соседних.

## UI2-FR-02. Pencil icon

Все элементы с `organization-disclosure--edit` должны показывать однозначный карандаш:

- «Изменить карточку»;
- «Изменить» документ;
- «Изменить» узел.

Иконка выполняется CSS-псевдоэлементами, использует theme color и не зависит от внешних assets.

## UI2-FR-03. Functional date picker button

Три date field получают отдельную кнопку календаря:

- создание документа;
- редактирование документа;
- дата вступления версии в действие.

HTML contract:

```html
<div class="organization-date-field">
    <label for="unique-date-id">Дата</label>
    <span class="organization-date-control">
        <input id="unique-date-id" type="date" ...>
        <button type="button"
                class="organization-date-picker-button"
                data-date-picker-target="unique-date-id"
                aria-label="Открыть календарь">
            <span class="organization-date-icon" aria-hidden="true"></span>
        </button>
    </span>
</div>
```

Требования:

- id каждого date input уникален на странице;
- button не отправляет форму;
- click/Enter/Space открывают native picker;
- input получает focus;
- используется `showPicker()` с fallback `click()`;
- disabled/readonly input не активируется;
- стандартный browser indicator визуально скрыт;
- custom icon остаётся theme-aware.

## UI2-FR-04. JavaScript contract

Новый `public/assets/js/organization-ui-controls.js`:

- загружается с `defer` после organization tree script;
- использует event delegation;
- обрабатывает только `[data-date-picker-target]`;
- не содержит inline handlers;
- не изменяет значения полей;
- не отправляет формы.

## UI2-FR-05. Three themes

Structural CSS идентичен для:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

## Invariants

Не меняются:

- POST endpoints;
- names полей;
- CSRF inputs;
- hidden identifiers;
- `expected_revision`;
- permissions/RBAC;
- DB and migrations.

## Automated acceptance

1. PHP lint изменённых PHP/checker файлов.
2. Static checker подтверждает:
   - три уникальных date input id;
   - три calendar buttons и target bindings;
   - script inclusion;
   - `showPicker()` и fallback;
   - одинаковую высоту controls;
   - pencil pseudo-elements;
   - идентичность CSS трёх тем;
   - сохранность POST/CSRF/revision contracts.
3. Полный `Test-OrganizationalStructureV1.ps1` — PASS.

## Manual desktop acceptance

Во всех трёх темах:

1. controls в отмеченных action rows имеют одинаковую высоту;
2. edit actions показывают карандаш;
3. calendar button открывает picker мышью;
4. calendar button открывает picker Enter/Space;
5. date input остаётся доступным напрямую;
6. console errors и asset 404 отсутствуют.

Mobile testing не входит в scope.
