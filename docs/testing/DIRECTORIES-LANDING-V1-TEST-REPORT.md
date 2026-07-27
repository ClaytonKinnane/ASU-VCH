# Стартовая страница справочников v1 — Test Report

## 1. Объект проверки

Инкремент:

```text
Стартовая страница справочников v1
```

База:

```text
main @ dce36c371b3eb6ef8a1365b24972660c0e62dd54
feature/directories-landing
```

Дата финальной приёмки: `2026-07-27`.

## 2. Проверенная область

```text
public/admin/content.php
public/admin/directories.php
docs/design/DIRECTORIES-LANDING-V1-DESIGN.md
docs/design/DIRECTORIES-LANDING-V1-REVIEW.md
docs/decisions/DIRECTORIES-LANDING-V1-APPROVAL.md
```

Миграции, таблицы БД, CRUD и новые permissions отсутствуют.

## 3. Локальный checkout

Пользователь получил feature-ветку в локальный репозиторий:

```text
C:\Project\ASU-VCH
```

Первоначально подтверждено:

```text
branch: feature/directories-landing
HEAD: 6929073d5f09c90fc51c2d385f38fddac7185311
tracking: origin/feature/directories-landing
pull --ff-only: Already up to date
working tree: clean
```

Статус: **PASS**.

После первой приёмки ветка была дополнена исправлениями D-01 и D-02 и документационными commits.

## 4. PHP syntax и целевая среда

Целевая CLI-среда:

```text
PHP 8.5.4
Zend Engine v4.5.4
```

До корректировки пользователь выполнил:

```text
php -l public\admin\content.php
php -l public\admin\directories.php
```

Результат:

```text
No syntax errors detected in public\admin\content.php
No syntax errors detected in public\admin\directories.php
```

После корректировки оба обновлённых маршрута успешно исполнялись целевым веб-сервером на PHP `8.5.4`, включая тематический ответ 403 и страницу контента с новым элементом `Открыть →`.

Дополнительно post-fix lint и структурные assertions были выполнены в проверочной среде.

Статус: **PASS**.

## 5. Deploy

Целевое развёртывание:

```text
C:\OSPanel\home\asu-vch.local
```

На первом deploy были скопированы:

```text
public\admin\content.php
public\admin\directories.php
```

Изначально SHA-256 исходных и развёрнутых файлов совпали:

```text
content.php:
5DCD66E64F6AFD5D6DCBD1E4EAB85C88CF6FA556DE67EA3823EBE21938081A51

directories.php:
9B626164062F334D8EBE15310BDC4D0F88AB61AE0833CEA80EB7F22B9D27ABB6
```

После исправлений повторный deploy подтверждён фактическим runtime-поведением:

- `/admin/content.php` содержит новый элемент `Открыть →`;
- `/admin/directories.php` использует новый permission boundary и возвращает тематическую 403;
- обе страницы обслуживаются локальным доменом `https://asu-vch.local`.

Статус: **PASS**.

## 6. Первая desktop-приёмка

Пользователь предоставил desktop-скриншоты страниц в обеих темах:

- `АСУ Синяя`;
- `АСУ Светлая синяя`.

Подтверждено:

- обе страницы открываются владельцу;
- навигация `Контент → Справочники` работает;
- кнопка `К контенту` работает;
- на странице справочников ровно две плитки;
- названия: `Подразделения` и `Воинские звания`;
- обе плитки имеют статус `В разработке`;
- сетка, границы, размеры и расположение корректны на desktop;
- статичные плитки не получают интерактивное свечение.

Статус базового desktop layout: **PASS**.

## 7. Выявленные и исправленные дефекты

### D-01 — redirect вместо тематической HTTP 403

Фактическое поведение до исправления:

- не-владелец перенаправлялся на `/admin/`;
- тематическая страница 403 отсутствовала.

Причина:

```text
require_system_owner() → redirect('/')
```

Исправление:

```php
$user = require_permission('system.*.*');
```

Обоснование:

- `system.*.*` уже является wildcard владельца;
- `require_permission()` использует существующую тематическую страницу 403;
- новые permissions и изменения ролей отсутствуют.

Повторная runtime-проверка:

- тематическая страница `Доступ запрещен` отображается в `АСУ Синяя`;
- тематическая страница `Доступ запрещен` отображается в `АСУ Светлая синяя`;
- redirect на `/admin/` отсутствует;
- Edge DevTools Network показывает `403 Forbidden` для `GET /admin/directories.php`;
- ответ сформирован целевым сервером PHP `8.5.4`.

Статус D-01: **FIXED / PASS**.

### D-02 — отсутствует действие `Открыть →`

Фактическое поведение до исправления:

- плитка `Справочники` была активной и имела hover;
- нижнее действие отсутствовало.

Исправление:

```html
<span class="tile-action">Открыть →</span>
```

Повторная desktop-проверка:

- `Открыть →` отображается в теме `АСУ Синяя`;
- `Открыть →` отображается в теме `АСУ Светлая синяя`;
- положение действия соответствует существующему class contract;
- hover, подъём и периметральное свечение сохранены.

Статус D-02: **FIXED / PASS**.

## 8. RBAC и безопасность

Подтверждено:

- владелец открывает `/admin/directories.php`;
- администратор, оператор и наблюдатель не получают доступ к содержимому справочников;
- не-владелец получает HTTP `403 Forbidden`;
- отображается тематическая страница 403;
- owner wildcard поддерживается существующим `AuthorizationService::hasPermission()`;
- новые permissions отсутствуют;
- permission count остаётся `19`;
- migration отсутствуют;
- формы и POST-маршруты отсутствуют;
- `display_name` экранируется через `e()`.

Статус runtime RBAC/security: **PASS**.

## 9. Финальная desktop-приёмка

### Тема `АСУ Синяя`

- активная плитка `Справочники`: **PASS**;
- `Открыть →`: **PASS**;
- hover и свечение: **PASS**;
- тематическая 403: **PASS**.

### Тема `АСУ Светлая синяя`

- активная плитка `Справочники`: **PASS**;
- `Открыть →`: **PASS**;
- hover и свечение: **PASS**;
- тематическая 403: **PASS**.

Итог desktop-приёмки: **PASS**.

## 10. Мобильное тестирование

Мобильное тестирование исключено из области работ и не выполнялось.

Нельзя заявлять, что мобильная версия проверена.

## 11. Итог

- Architecture / Specification / Review / Approval: **PASS**;
- local checkout: **PASS**;
- PHP syntax и target runtime PHP 8.5.4: **PASS**;
- deploy: **PASS**;
- navigation владельца: **PASS**;
- две статичные справочные плитки: **PASS**;
- D-01 thematic HTTP 403: **FIXED / PASS**;
- D-02 `Открыть →`: **FIXED / PASS**;
- runtime RBAC/security: **PASS**;
- desktop-приёмка обеих тем: **PASS**;
- мобильное тестирование: **OUT OF SCOPE / NOT RUN**.

Инкремент готов к переводу PR в ready for review.

Merge разрешается только после отдельной явной команды заказчика.
