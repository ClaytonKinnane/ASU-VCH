# ASU Light Blue Theme v1 — Formal Review

## 1. Объект review

Проверен документ:

```text
docs/design/THEME-ASU-LIGHT-BLUE-V1-DESIGN.md
```

Инкремент:

```text
ASU Light Blue Theme v1
```

Ветка:

```text
feature/theme-asu-light-blue
```

База:

```text
main @ 4e1d692807fbac83d86ec1be431df4563bcfacd5
```

Реализация на момент review не начата.

## 2. Проверенные исходные предпосылки

### 2.1 Конфигурация темы

Подтверждено, что `config/app.php` содержит:

```php
'theme' => 'asu-blue',
```

Но текущие HTML-emitter'ы используют жесткие ссылки `/themes/asu-blue/...`.

Вывод: новый runtime resolver необходим; создание только второго CSS-каталога не сделает тему активируемой.

Статус: **PASS**.

### 2.2 Текущий auth flow

Подтверждено:

- после установки показывается вход;
- до установки показывается первичное создание владельца;
- обе формы не должны отображаться одновременно;
- CSRF и реальные маршруты уже реализованы на сервере.

Вывод: вкладки из предоставленного HTML являются только визуальным референсом и не должны менять flow.

Статус: **PASS**.

### 2.3 Текущий class contract

Страницы используют общий набор классов `glass-tile`, `primary-button`, `secondary-button`, `form-input`, dashboard/users/detail classes.

Вывод: новая тема может быть реализована CSS-пакетом без копирования PHP-разметки.

Статус: **PASS**.

### 2.4 Настройки системы

Страница настроек уже показывает `app_config('theme')`, однако модуль выбора темы отмечен `В разработке`.

Вывод: config-based activation соответствует текущему уровню системы; полноценный UI-переключатель должен быть отдельным инкрементом.

Статус: **PASS**.

## 3. Architecture review

### 3.1 Отделение темы от бизнес-логики

Решение сохраняет:

- существующие PHP-маршруты;
- формы;
- RBAC;
- CSRF;
- session guards;
- статусы и аудит;
- archive/restore modal semantics.

Изменяется только разрешение asset URL и CSS.

Статус: **PASS**.

### 3.2 Реестр тем

Статический `config/themes.php` исключает использование произвольного имени каталога.

Преимущества:

- понятный allow-list;
- диагностируемый список доступных тем;
- возможность checker'а;
- отсутствие directory enumeration в runtime;
- отсутствие зависимости от БД.

Статус: **PASS**.

### 3.3 Безопасный fallback

При неизвестном configured slug используется `asu-blue`.

Review condition:

- fallback должен быть константным и присутствовать в registry;
- сообщение в log не должно содержать HTTP-параметры или PII;
- нельзя разрешать query override темы.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 3.4 Asset path validation

`theme_asset()` должен запрещать:

```text
..
\
NUL
://
leading //
```

Допустимые вызовы должны быть только статическими литералами из кода.

Review condition: checker обязан проверить как минимум `../`, `..\`, абсолютный URL и пустой path.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 3.5 Shared modal JavaScript

Перенос behavior JS из конкретной темы в `public/assets/js` уменьшает дублирование и сохраняет theme-specific CSS.

Review condition:

- сначала добавить shared path и обновить bootstrap;
- затем убедиться grep/checker'ом, что старый файл не используется;
- только после этого удалить theme-specific JS;
- regression обоих modal-вариантов обязательна.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

## 4. UI/UX review

### 4.1 Соответствие референсу

Спецификация сохраняет ключевые признаки референса:

- белая основа;
- `#086ad5`;
- `#054f9e` hover;
- тонкие рамки;
- белые карточки;
- radius около 8px;
- мягкие тени;
- минималистичные кнопки и поля.

Статус: **PASS**.

### 4.2 Адаптация, а не буквальное копирование

Правильно исключены:

- `MySite`;
- статический 2024 год;
- одновременные вкладки login/register;
- inline CSS/JS;
- несуществующие поля и маршруты.

Статус: **PASS**.

### 4.3 Контраст

Исходный HTML использует очень слабую рамку inputs `rgba(8,106,213,.15)` и синий body text.

Спецификация корректно усиливает input border и использует темный основной текст для длинных блоков, сохраняя синий для заголовков/actions.

Review condition: desktop-приемка должна включать DevTools contrast check для основного текста, secondary text, кнопок и focus.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 4.4 Семантические состояния

Светлая тема обязана сохранить различия:

- success;
- warning;
- error;
- muted;
- archived;
- danger actions.

Текст и маркер сохраняются, поэтому состояние не зависит только от цвета.

Статус: **PASS**.

### 4.5 Operation modal

Текущий красный/зеленый modal уже принят заказчиком. Новая тема должна стилизовать тот же component contract, не возвращаясь к native alert.

Статус: **PASS**.

## 5. Scope review

### 5.1 В scope

- вторая тема;
- runtime theme registry/resolver;
- config-based activation;
- замена жестких asset links;
- shared modal behavior JS;
- новая светлая CSS-тема;
- checker;
- тестирование обеих тем;
- desktop acceptance.

Статус: **PASS**.

### 5.2 Вне scope

Корректно исключены:

- UI выбора темы;
- DB storage;
- per-user themes;
- preview query;
- custom CSS upload;
- dark/light auto mode;
- мобильная приемка;
- migration;
- изменение бизнес-логики.

Статус: **PASS**.

## 6. Риски и меры

### Риск 1. Неполная замена hardcoded URL

Последствие: часть страниц останется темной либо modal загрузит CSS старой темы.

Мера:

- repo-wide grep до и после;
- checker падает при `/themes/asu-blue/` в executable PHP;
- ручной обход страниц.

Остаточный риск: **низкий**.

### Риск 2. Неизвестная тема ломает интерфейс

Мера: статический registry и fallback `asu-blue`.

Остаточный риск: **низкий**.

### Риск 3. Path traversal в helper

Мера: строгая валидация path и отсутствие HTTP input.

Остаточный риск: **низкий**.

### Риск 4. Дублирование modal JS

Мера: shared asset + удаление старого файла после grep.

Остаточный риск: **низкий**.

### Риск 5. Светлая тема имеет низкий контраст

Мера: адаптированные tokens, focus states, ручная проверка contrast.

Остаточный риск: **средний до desktop acceptance**, после приемки — низкий.

### Риск 6. Регрессия `asu-blue`

Мера:

- тема по умолчанию не меняется;
- CSS `asu-blue` не переписывается без необходимости;
- smoke и ручной sanity check после возврата на `asu-blue`.

Остаточный риск: **низкий**.

## 7. Обязательные implementation gates

Перед передачей на ручную приемку должны пройти:

```text
1. PHP syntax
2. theme assets checker
3. no hardcoded /themes/asu-blue/ in executable PHP
4. deploy
5. smoke asu-blue
6. smoke asu-light-blue
7. archive/restore regression
8. modal shared JS loaded
9. config/local.php preserved
10. clean Git status
```

## 8. Обязательные desktop screens

Рекомендуемые контрольные скриншоты:

1. login / initial setup card;
2. admin dashboard;
3. users list;
4. active user detail;
5. archived user detail;
6. red error modal;
7. green success modal;
8. themed 403;
9. возврат на `asu-blue`.

Мобильные screenshots не требуются.

## 9. Findings

### F-01 — Theme config сейчас не управляет assets

Severity: **blocking for implementation**.

Disposition: решено архитектурой через registry + `theme_asset()`; должно быть реализовано до CSS-приемки.

### F-02 — Tabs из референса конфликтуют с server flow

Severity: **medium if copied literally**.

Disposition: закрыто спецификацией; tabs не переносятся.

### F-03 — Input border исходника недостаточно контрастен

Severity: **medium visual/accessibility**.

Disposition: закрыто design tokens; подтвердить в desktop acceptance.

### F-04 — Modal JS привязан к каталогу `asu-blue`

Severity: **medium maintainability**.

Disposition: закрыто shared JS architecture; подтвердить grep и regression.

### F-05 — UI выбора темы не спроектирован

Severity: **not a defect / out of scope**.

Disposition: config-based activation в v1; UI — отдельный increment.

## 10. Итог review

```text
Source adaptation: PASS
Architecture: PASS
Security design: PASS WITH IMPLEMENTATION CONDITIONS
Theme isolation: PASS
Backward compatibility: PASS WITH REGRESSION GATE
Accessibility design: PASS WITH DESKTOP CHECK
Database changes: NONE
Mobile acceptance: OUT OF SCOPE
Blocking design findings unresolved: 0
```

## 11. Решение

Architecture/Specification допускается к утверждению.

Реализация не должна начинаться до отдельного явного approval заказчика.
