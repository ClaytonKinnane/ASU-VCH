# Theme Management System & ASU Light Blue Theme v1 — Formal Review

## 1. Объект review

Проверен документ:

```text
docs/design/THEME-ASU-LIGHT-BLUE-V1-DESIGN.md
```

Инкремент:

```text
Theme Management System & ASU Light Blue Theme v1
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

## 2. Причина переработки

Первоначальная спецификация предусматривала:

- вторую CSS-тему;
- runtime registry/resolver;
- активацию через `config/local.php`;
- отсутствие UI и migration.

Заказчик предложил сразу реализовать систему управления темами и разрешил переработать Architecture/Specification/Review.

Review подтверждает, что расширение scope оправдано: без UI-переключателя первая дополнительная тема была бы доступна только разработчику и не завершала бы заявленный модуль «Темы оформления».

Статус: **PASS**.

## 3. Проверенные факты текущей системы

### 3.1 Hardcoded theme URLs

Подтверждено:

- `config/app.php` содержит `theme = asu-blue`;
- PHP-emitter'ы подключают `/themes/asu-blue/...` напрямую;
- operation-result modal также привязан к каталогу `asu-blue`.

Вывод: registry и asset resolver обязательны до добавления второй темы.

Статус: **PASS**.

### 3.2 `system_settings`

Подтверждено, что таблица уже содержит:

```text
id
setting_key
setting_value
created_at
updated_at
```

Она предназначена для глобальных настроек и уже используется для `installation_completed`.

Вывод: хранение `ui.active_theme` в этой таблице архитектурно корректно.

Статус: **PASS**.

### 3.3 Отсутствующий actor audit

В `system_settings` нет `updated_by`.

Вывод: если UI изменяет глобальную тему, migration 006 должна добавить nullable actor FK. Это минимальное и переиспользуемое расширение существующей settings entity.

Статус: **PASS**.

### 3.4 Существующие разрешения

Подтверждены:

```text
system.settings.view
system.settings.update
```

Migration 002 уже назначает оба разрешения роли `administrator`; владелец получает абсолютный доступ через `system.*.*`.

Вывод: новые permission codes не нужны. Искусственное owner-only ограничение противоречило бы существующей RBAC-матрице.

Статус: **PASS**.

### 3.5 Текущая settings boundary

`public/admin/settings.php` использует owner-only guard, хотя permission уже существует.

Вывод: замена boundary на `system.settings.view` является исправлением соответствия реализации утверждённой RBAC-модели, а не выдачей новых прав.

Статус: **PASS WITH REGRESSION CONDITION**.

### 3.6 Auth flow

Подтверждено:

- до установки отображается создание владельца;
- после установки — вход;
- публичная регистрация после установки отключена;
- формы защищены CSRF.

Вывод: tabs из HTML остаются только визуальным референсом.

Статус: **PASS**.

## 4. Architecture review

### 4.1 Разделение обязанностей

Предложены:

```text
ThemeRegistry
ThemeSettingsRepository
ThemeActivationService
```

Разделение корректно:

- registry валидирует доверенные metadata/assets;
- repository отвечает за SQL;
- service координирует транзакционную mutation;
- HTTP boundary отвечает за permission, CSRF и PRG.

Статус: **PASS**.

### 4.2 Статический registry

Плюсы:

- allow-list slug;
- отсутствие runtime directory enumeration;
- отсутствие загрузки стороннего кода;
- возможность автоматического checker'а;
- стабильные metadata для UI;
- безопасная связь slug → каталог.

Условие реализации:

- default slug обязан быть зарегистрирован;
- malformed registry должен блокировать checker;
- runtime не должен принимать metadata из БД.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 4.3 Источник активной темы

После migration 006 source-of-truth:

```text
system_settings.ui.active_theme
```

`config/app.php['theme']` остаётся bootstrap/pre-install fallback.

Это решает конфликт между веб-управлением и локальной конфигурацией: веб-интерфейс не редактирует PHP-файлы.

Условие:

- DB setting не должен переопределяться `config/local.php` после установки;
- при недоступности БД runtime должен безопасно использовать config/default;
- query/cookie override запрещён.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 4.4 Migration 006

Добавление nullable `updated_by` в generic settings table признано приемлемым.

Требования:

- FK `ON DELETE SET NULL`;
- существующие строки сохраняются;
- insert `ui.active_theme` идемпотентен;
- повторный install не меняет уже выбранную тему;
- миграция не добавляет permissions.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 4.5 Транзакционная активация

`ThemeActivationService` обязан повторно проверить slug и availability независимо от UI.

Row locking и `INSERT ... ON DUPLICATE KEY UPDATE` достаточны для глобальной single-setting mutation.

Статус: **PASS**.

### 4.6 Safe fallback

Fallback `asu-blue` является зарегистрированной существующей темой.

Условия:

- неизвестное DB value не используется в URL;
- missing required asset делает тему unavailable;
- fallback не должен скрыто переписывать DB при каждом запросе;
- diagnostic log не содержит PII или DSN;
- checker блокирует релиз, если default theme неполна.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 4.7 Asset path validation

`theme_asset()` должен отклонять:

```text
пустой path
../
..\
NUL
://
leading /
leading //
```

Условие: checker вызывает negative cases напрямую.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 4.8 Shared modal JavaScript

Перенос behavior JS в `public/assets/js` правильный: JavaScript отвечает за компонент, CSS — за тему.

Условия:

1. сначала добавить shared file;
2. обновить bootstrap;
3. выполнить repo-wide search;
4. только затем удалить theme-specific JS;
5. проверить error/success modal в обеих темах.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

## 5. RBAC review

### 5.1 Просмотр

```text
/admin/settings.php
/admin/settings/themes.php
```

требуют `system.settings.view`.

### 5.2 Активация

```text
POST /admin/settings/themes/activate.php
```

требует `system.settings.update`.

### 5.3 Роли

Ожидается:

- system_owner: allow;
- administrator: allow;
- operator: deny;
- viewer: deny.

Это соответствует migration 002.

### 5.4 Permission count

Новые permissions отсутствуют, поэтому системный count остаётся текущим.

Статус RBAC review: **PASS**.

## 6. HTTP/security review

### 6.1 Method safety

Mutation только POST.

GET к activation route не изменяет setting.

Статус: **PASS**.

### 6.2 CSRF

`require_csrf()` выполняется до service mutation.

Invalid token возвращает HTTP 419 и не меняет тему.

Статус: **PASS**.

### 6.3 Server authorization

Permission проверяется на маршруте независимо от видимости кнопки.

Direct POST под viewer/operator должен вернуть themed 403.

Статус: **PASS**.

### 6.4 Stored input safety

Даже если DB value изменено вручную, slug обязан пройти registry lookup до формирования path.

Статус: **PASS**.

### 6.5 Error disclosure

Пользователь видит фиксированное сообщение; exception details не выводятся.

Статус: **PASS**.

### 6.6 External code

Browser upload, remote CSS, remote JS и CDN не входят в v1.

Статус: **PASS**.

## 7. UI/UX review

### 7.1 Theme management page

Карточная модель подходит существующему settings UI.

Обязательные признаки:

- имя;
- описание;
- light/dark label;
- три palette swatch;
- active badge;
- unavailable status;
- activate action только при update permission.

Статус: **PASS**.

### 7.2 Immediate switch

После POST выполняется redirect, и целевая страница разрешает assets заново. Поэтому новая тема применяется без отдельного reload.

Статус: **PASS**.

### 7.3 Themed result

Fixed operation result catalog исключает вставку POST title в JavaScript и сохраняет ранее принятую modal-архитектуру.

Статус: **PASS**.

### 7.4 Соответствие HTML-референсу

Сохраняются:

- `#ffffff`;
- `#086ad5`;
- `#054f9e`;
- тонкие borders;
- radius около 8px;
- мягкие shadows;
- плоские buttons;
- светлые inputs.

Не копируются server-incompatible tabs и inline behavior.

Статус: **PASS**.

### 7.5 Контраст

Исходная input border `rgba(8,106,213,.15)` слишком слабая. Спецификация усиливает её и использует тёмный основной текст.

Условие: desktop acceptance включает contrast/focus sanity check.

Статус: **PASS WITH IMPLEMENTATION CONDITION**.

### 7.6 Semantic states

Обе темы обязаны сохранять:

- success;
- warning;
- error;
- muted;
- archived;
- danger;
- disabled.

Статусы имеют текст/маркер, а не только цвет.

Статус: **PASS**.

## 8. Scope review

### 8.1 В scope

- две registered themes;
- DB-backed global setting;
- migration 006;
- actor audit latest value;
- registry/resolver;
- settings UI;
- activation route;
- existing RBAC permissions;
- shared modal behavior;
- light theme CSS;
- automated checker;
- desktop acceptance обеих тем.

Статус: **PASS**.

### 8.2 Вне scope

- ZIP upload;
- arbitrary theme install;
- delete theme;
- CSS editor;
- custom CSS;
- external resources;
- per-user themes;
- query preview;
- OS auto-mode;
- scheduling;
- full audit history;
- mobile acceptance.

Статус: **PASS**.

## 9. Findings

### F-01 — Config parameter не управляет assets

Severity: **blocking for implementation**.

Disposition: resolver + replacement hardcoded links обязательны.

### F-02 — Config-only activation недостаточна для requested management

Severity: **scope blocker**.

Disposition: закрыто DB-backed UI architecture.

### F-03 — `system_settings` не хранит actor

Severity: **medium audit**.

Disposition: migration 006 добавляет `updated_by`.

### F-04 — Owner-only settings guard не соответствует permissions

Severity: **medium authorization consistency**.

Disposition: использовать `system.settings.view/update`; провести роль-регрессию.

### F-05 — Theme slug может стать path injection

Severity: **high if implemented naively**.

Disposition: static registry, strict slug/path validation, negative checker.

### F-06 — Registered theme может быть неполной

Severity: **medium availability**.

Disposition: required assets health, unavailable state, activation rejection, fallback.

### F-07 — Modal behavior привязан к `asu-blue`

Severity: **medium maintainability**.

Disposition: shared JS, per-theme CSS.

### F-08 — Tabs из референса конфликтуют с auth flow

Severity: **medium if copied literally**.

Disposition: не переносить.

### F-09 — Светлая тема может иметь недостаточный контраст

Severity: **medium visual/accessibility**.

Disposition: адаптированные tokens + desktop contrast check.

### F-10 — Web UI не должен редактировать PHP config

Severity: **high operational/security if violated**.

Disposition: DB setting; `config/local.php` только fallback, не mutation target.

## 10. Риски и меры

### Риск 1. Часть страниц остаётся на `asu-blue`

Мера:

- repo-wide search;
- checker hardcoded paths;
- ручной обход.

Остаточный риск: **низкий после gate**.

### Риск 2. Повреждённое setting value ломает CSS

Мера: registry validation + fallback.

Остаточный риск: **низкий**.

### Риск 3. Missing assets после deploy

Мера: checker + deploy hash/existence check + unavailable state.

Остаточный риск: **низкий**.

### Риск 4. Administrator получает неожиданный settings access

Фактически permission уже назначен. Риск связан не с новой выдачей права, а с исправлением старой boundary.

Мера:

- проверить dashboard visibility;
- убедиться, что доступны только реализованные settings actions;
- остальные modules остаются disabled;
- viewer/operator denied.

Остаточный риск: **средний до manual role acceptance**, затем низкий.

### Риск 5. DB недоступна до установки

Мера: exception-safe fallback без requirement system_settings table.

Остаточный риск: **низкий**.

### Риск 6. Регрессия тёмной темы

Мера: default не меняется при migration, обе темы проходят smoke и ручной sanity.

Остаточный риск: **низкий**.

### Риск 7. Concurrent activation

Мера: transaction + row lock + фактическое значение после redirect.

Остаточный риск: **низкий**.

## 11. Implementation gates

До ручной приёмки обязательны:

```text
1. PHP syntax
2. migration 006
3. second install idempotency
4. theme management checker
5. registry validation
6. default theme health
7. light theme health
8. unknown slug rejection
9. missing asset rejection
10. traversal rejection
11. DB persistence and updated_by
12. existing permission count unchanged
13. administrator allow
14. viewer/operator deny
15. CSRF 419 no mutation
16. GET no mutation
17. no hardcoded /themes/asu-blue/ in executable PHP
18. shared modal JS loaded
19. old modal JS unreferenced
20. deploy preserves config/local.php
21. smoke asu-blue
22. smoke asu-light-blue
23. archive/restore regression
24. clean Git status
```

## 12. Desktop acceptance gates

Обязательные сценарии:

1. owner sees theme management;
2. administrator sees theme management;
3. viewer/operator receive themed 403;
4. `asu-blue` active initially;
5. activate `asu-light-blue`;
6. success modal uses light theme;
7. login/dashboard/settings/users render light;
8. tables/forms/statuses/modal render light;
9. unknown modified POST rejected;
10. invalid CSRF leaves active theme unchanged;
11. activate `asu-blue` back;
12. success modal uses dark theme;
13. core dark pages unchanged;
14. active setting and actor/date are correct.

Мобильная приёмка не выполняется.

## 13. Итог review

```text
Scope expansion justification: PASS
Source adaptation: PASS
Architecture: PASS
Database design: PASS WITH IMPLEMENTATION CONDITIONS
RBAC design: PASS WITH ROLE REGRESSION GATE
Security design: PASS WITH IMPLEMENTATION CONDITIONS
Theme isolation: PASS
Runtime fallback: PASS WITH FAILURE-MODE TESTS
UI/UX design: PASS
Accessibility design: PASS WITH DESKTOP CHECK
Backward compatibility: PASS WITH TWO-THEME REGRESSION
Mobile acceptance: OUT OF SCOPE
Unresolved blocking design findings: 0
```

## 14. Решение

Переработанная Architecture/Specification допускается к отдельному утверждению заказчиком.

Реализацию нельзя начинать до явного разрешения:

```text
Утверждаю Architecture/Specification/Review Theme Management System & ASU Light Blue Theme v1 и разрешаю реализацию.
```