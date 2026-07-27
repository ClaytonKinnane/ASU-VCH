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

Дата проверок и корректировки: `2026-07-27`.

## 2. Проверенная область

Проверяются изменения:

```text
public/admin/content.php
public/admin/directories.php
docs/design/DIRECTORIES-LANDING-V1-DESIGN.md
docs/design/DIRECTORIES-LANDING-V1-REVIEW.md
docs/decisions/DIRECTORIES-LANDING-V1-APPROVAL.md
```

## 3. Локальный checkout до первой приёмки

Пользователь получил удалённую feature-ветку в:

```text
C:\Project\ASU-VCH
```

Подтверждено:

```text
branch: feature/directories-landing
HEAD: 6929073d5f09c90fc51c2d385f38fddac7185311
tracking: origin/feature/directories-landing
pull --ff-only: Already up to date
working tree: clean
```

Статус: **PASS**.

После этой проверки feature-ветка получила исправления и documentation commits. Перед повторным deploy требуется `git pull --ff-only`.

## 4. PHP syntax в целевой среде до корректировки

Версия PHP:

```text
PHP 8.5.4 (cli)
Zend Engine v4.5.4
```

Результат:

```text
No syntax errors detected in public\admin\content.php
No syntax errors detected in public\admin\directories.php
```

Статус: **PASS**.

## 5. Первый deploy

Целевое развёртывание:

```text
C:\OSPanel\home\asu-vch.local
```

До корректировки совпали SHA-256 исходных и развёрнутых файлов:

```text
content.php:
5DCD66E64F6AFD5D6DCBD1E4EAB85C88CF6FA556DE67EA3823EBE21938081A51

directories.php:
9B626164062F334D8EBE15310BDC4D0F88AB61AE0833CEA80EB7F22B9D27ABB6
```

Статус первого deploy: **PASS**.

После исправления обоих PHP-файлов эти hashes больше не являются актуальными. Требуются повторные deploy и hash verification.

## 6. Первая runtime и desktop-приёмка

Пользователь предоставил desktop-скриншоты:

- `/admin/content.php` в теме `АСУ Синяя`;
- `/admin/directories.php` в теме `АСУ Синяя`;
- `/admin/content.php` в теме `АСУ Светлая синяя`;
- `/admin/directories.php` в теме `АСУ Светлая синяя`.

Подтверждено:

- обе страницы открываются владельцу;
- навигация `Контент → Справочники` работает;
- кнопка `К контенту` присутствует;
- на странице справочников отображаются ровно две плитки;
- плитки имеют названия `Подразделения` и `Воинские звания`;
- обе плитки имеют статус `В разработке`;
- обе темы применяются корректно;
- сетка, границы, размеры и расположение плиток визуально корректны на desktop.

Статус базового desktop layout: **PASS**.

Мобильная версия не проверялась и не входит в scope.

## 7. Выявленные дефекты

### D-01 — отсутствует тематический HTTP 403

Фактическое поведение до исправления:

- владелец открывает `/admin/directories.php`;
- администратор, оператор и наблюдатель не получают страницу справочников;
- вместо тематической 403 выполняется redirect на `/admin/`.

Причина:

```text
require_system_owner() → redirect('/')
```

Ожидалось:

- тематическая страница `Доступ запрещен`;
- HTTP status `403`;
- отсутствие redirect на панель.

Статус до исправления: **FAIL**.

Исправление в feature-ветке:

```php
$user = require_permission('system.*.*');
```

Обоснование:

- `system.*.*` уже является wildcard владельца;
- `require_permission()` использует существующую тематическую 403;
- новые permissions и изменения ролей отсутствуют.

Статус после code correction: **PENDING TARGET RUNTIME RETEST**.

### D-02 — отсутствует действие `Открыть →`

Фактическое поведение до исправления:

- активная плитка `Справочники` была ссылкой и имела hover;
- нижняя подпись действия отсутствовала, в отличие от принятой плитки `Темы оформления`.

Статус до исправления: **FAIL**.

Исправление в feature-ветке:

```html
<span class="tile-action">Открыть →</span>
```

Новый CSS не добавлялся; используется существующий class contract.

Статус после code correction: **PENDING TARGET DESKTOP RETEST**.

## 8. Автоматические проверки после исправления

В доступной проверочной среде выполнено:

```text
php -l content.php
php -l directories.php
```

Результат:

```text
No syntax errors detected
```

Структурными assertions подтверждено:

- активная плитка содержит `tile-action` с текстом `Открыть →`;
- `/admin/directories.php` использует `require_permission('system.*.*')`;
- `require_system_owner()` на странице справочников больше не используется;
- определены ровно два утверждённых справочника.

Статус: **PASS**.

Примечание: post-fix lint выполнен на PHP `8.4.16`; повторный lint на целевом PHP `8.5.4` обязателен после синхронизации ветки.

## 9. RBAC / security review после исправления

Статически подтверждено:

- owner wildcard уже поддерживается `AuthorizationService::hasPermission()`;
- тематический 403 формируется существующим `require_permission()`;
- новые permissions отсутствуют;
- permission count остаётся `19`;
- migration отсутствуют;
- формы и POST-маршруты отсутствуют;
- `display_name` экранируется через `e()`.

Статус статической проверки: **PASS**.

Статус runtime RBAC: **PENDING RETEST**.

## 10. Обязательная повторная проверка

После `git pull --ff-only` и повторного deploy необходимо проверить:

1. PHP lint на PHP `8.5.4`.
2. Совпадение SHA-256 двух обновлённых PHP-файлов.
3. Наличие `Открыть →` в плитке `Справочники` в обеих темах.
4. Сохранение hover/свечения активной плитки.
5. Статичность плиток `Подразделения` и `Воинские звания`.
6. Прямое открытие `/admin/directories.php` владельцем.
7. Тематический HTTP 403 под администратором, оператором или наблюдателем.
8. Отсутствие redirect на `/admin/` для аутентифицированного не-владельца.

## 11. Итог

- local checkout до корректировки: **PASS**;
- первый PHP lint на PHP 8.5.4: **PASS**;
- первый deploy integrity: **PASS**;
- базовый desktop layout обеих тем: **PASS**;
- runtime navigation владельца: **PASS**;
- D-01 thematic 403: **FIXED IN BRANCH / RETEST PENDING**;
- D-02 `Открыть →`: **FIXED IN BRANCH / RETEST PENDING**;
- post-fix syntax and structural checks: **PASS**;
- повторный deploy и target runtime: **PENDING**;
- мобильное тестирование: **OUT OF SCOPE / NOT RUN**.

PR остаётся draft. Merge до повторной приёмки и отдельного явного разрешения запрещён.
