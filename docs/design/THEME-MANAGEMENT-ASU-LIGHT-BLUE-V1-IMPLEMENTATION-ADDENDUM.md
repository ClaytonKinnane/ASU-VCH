# Theme Management System & ASU Light Blue Theme v1 — Implementation Addendum

## Назначение

Документ фиксирует технические уточнения, обнаруженные при реализации утверждённой Architecture / Specification. Уточнения не расширяют пользовательский scope и не меняют бизнес-логику.

## 1. Полный обязательный asset contract

Каждая полноценная тема предоставляет:

```text
css/theme.css
css/auth.css
css/account.css
css/users.css
css/theme-management.css
css/operation-result-modal.css
```

`account.css` обязателен, поскольку действующая страница обязательной смены пароля использует специализированный stylesheet.

`theme-management.css` обязателен, поскольку новый модуль управления темами имеет собственные карточки, palette swatches и audit-блок. Этот файл также задаёт link-state доступной карточки «Темы оформления» на общей странице настроек.

## 2. Shared modal JavaScript

Поведение operation-result modal перенесено в:

```text
public/assets/js/operation-result-modal.js
```

Theme-specific JavaScript удаляется. Каждая тема сохраняет собственный:

```text
css/operation-result-modal.css
```

## 3. Runtime asset root

`ThemeRegistry` поддерживает два физически эквивалентных контекста:

- исходный репозиторий: `themes/{slug}/assets`;
- deploy Open Server Panel: `public/themes/{slug}/assets`.

URL-контракт в обоих случаях остаётся:

```text
/themes/{slug}/assets/{path}
```

## 4. RBAC clarification

Новые permissions не создаются. Используются существующие:

```text
system.settings.view
system.settings.update
```

Роль `administrator` уже имеет оба разрешения, а `system_owner` получает их через `system.*.*`.

## 5. Acceptance impact

Автоматический checker обязан подтверждать наличие всех шести CSS-assets для обеих тем. Ручная desktop-приёмка дополнительно охватывает:

- страницу обязательной смены пароля;
- общую страницу настроек;
- страницу управления темами;
- themed success/error modal в обеих темах.
