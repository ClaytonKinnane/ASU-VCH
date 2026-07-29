# Architecture: Organizational Structure v1 UI Polish 4

## Статус

```text
DATE: 2026-07-29
PHASE: ARCHITECTURE
STATUS: APPROVED
IMPLEMENTATION: ALLOWED
```

## Контекст

После автоматического PASS UI Polish 3 ручная desktop-проверка выявила три presentation-недочёта:

1. поле поиска дерева не выровнено по внешним границам кнопок `Раскрыть всё` и `Свернуть всё`;
2. карандаш на node-action `Изменить` визуально читается как горизонтальная черта;
3. индикатор раскрытия уровня недостаточно заметен.

## Scope

Изменяется только presentation-layer организационной структуры:

- `public/admin/organization/views/tree.php`;
- `themes/*/assets/css/organization.css`;
- `tools/check-organizational-structure-ui-polish.php`.

JavaScript дерева, маршруты, POST payload, CSRF, revision, RBAC, domain services, migrations и БД не меняются.

## Search tools layout

Правая часть heading дерева становится единым grid-контейнером:

- верхний ряд — отдельный button group `Раскрыть всё` / `Свернуть всё`;
- нижний ряд — label/input поиска шириной контейнера.

Контейнер получает ширину по содержимому верхнего ряда, а search field растягивается на всю эту ширину. Поэтому внешние границы search input совпадают с границами двух кнопок.

## Edit icon

Сохраняется единый class contract `organization-disclosure--edit`. Специальное node-action правило не должно сбрасывать наклон карандаша.

Node edit-trigger использует ту же геометрию:

```text
transform: rotate(-45deg)
```

что и `Изменить карточку`.

## Tree level toggle

Поведение `data-tree-toggle` не меняется. Символы `▾` / `▸`, которыми управляет существующий `organization-tree.js`, сохраняются.

CSS усиливает визуальную заметность:

- увеличенный размер glyph;
- повышенный font-weight;
- `var(--focus-color)`;
- theme-aware background и border;
- явные hover/focus-visible states.

## Themes

Structural `organization.css` остаётся идентичным для:

- `asu-blue`;
- `asu-light-blue`;
- `asu-evgeniya-rostova`.

Все цвета берутся только из существующих theme variables.

## Ограничения

Запрещено:

- добавлять внешние SVG/PNG/icon libraries;
- менять tree search/collapse semantics;
- менять серверные формы и hidden fields;
- менять мобильный acceptance scope.
