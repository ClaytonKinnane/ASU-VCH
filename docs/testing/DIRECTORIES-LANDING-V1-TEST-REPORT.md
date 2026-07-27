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

Дата проверок: `2026-07-27`.

## 2. Проверенная область

Проверены изменения:

```text
public/admin/content.php
public/admin/directories.php
docs/design/DIRECTORIES-LANDING-V1-DESIGN.md
docs/design/DIRECTORIES-LANDING-V1-REVIEW.md
docs/decisions/DIRECTORIES-LANDING-V1-APPROVAL.md
```

## 3. Локальный checkout

Пользователь выполнил получение удалённой feature-ветки в локальный репозиторий:

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

## 4. PHP syntax в целевой среде

Версия PHP:

```text
PHP 8.5.4 (cli)
Zend Engine v4.5.4
```

Команды:

```text
php -l public\admin\content.php
php -l public\admin\directories.php
```

Результат:

```text
No syntax errors detected in public\admin\content.php
No syntax errors detected in public\admin\directories.php
```

Статус: **PASS**.

## 5. Deploy в локальное окружение

Целевое развёртывание:

```text
C:\OSPanel\home\asu-vch.local
```

Скопированы файлы:

```text
public\admin\content.php
public\admin\directories.php
```

### 5.1 Контроль целостности `content.php`

SHA-256 исходного и развёрнутого файлов совпадает:

```text
5DCD66E64F6AFD5D6DCBD1E4EAB85C88CF6FA556DE67EA3823EBE21938081A51
```

### 5.2 Контроль целостности `directories.php`

SHA-256 исходного и развёрнутого файлов совпадает:

```text
9B626164062F334D8EBE15310BDC4D0F88AB61AE0833CEA80EB7F22B9D27ABB6
```

Статус deploy: **PASS**.

## 6. Структурная проверка

Автоматическими assertions подтверждено:

- `content.php` использует `require_system_owner()`;
- `directories.php` использует `require_system_owner()`;
- активная плитка имеет классы `dashboard-tile module-tile glass-tile`;
- активная плитка ведёт на `/admin/directories.php`;
- кнопка возврата ведёт на `/admin/content.php`;
- определены ровно два справочника;
- присутствуют `Подразделения` и `Воинские звания` с утверждёнными описаниями;
- плитки выводятся как `article.module-tile.glass-tile.is-disabled`;
- выводится статус `В разработке`;
- элементы справочников не являются ссылками;
- на странице справочников нет форм и mutations;
- остальные модули страницы контента остаются статичными и disabled.

Статус: **PASS**.

## 7. RBAC / security review

Статически подтверждено:

- обе страницы используют существующий server-side owner guard;
- новые permissions отсутствуют;
- migration отсутствуют;
- формы и POST-маршруты отсутствуют;
- динамический `display_name` выводится через существующий `e()`.

Статус статической проверки: **PASS**.

Runtime-проверка владельца и тематического ответа 403 для остальных ролей остаётся обязательной.

Статус runtime RBAC: **PENDING**.

## 8. Визуальная проверка

Автоматически подтверждён class contract:

- активная плитка повторно использует принятый hover-контракт `dashboard-tile`;
- компактный размер сохраняется классом `module-tile`;
- статичные плитки не содержат `dashboard-tile` и остаются неинтерактивными.

Фактическая desktop-визуальная проверка в браузере для тем `АСУ Синяя` и `АСУ Светлая синяя` остаётся обязательной.

Статус: **PENDING USER DESKTOP ACCEPTANCE**.

## 9. Мобильное тестирование

Мобильное тестирование исключено из области работ и не выполнялось. Заявление о проверенной мобильной версии не делается.

## 10. Оставшиеся проверки

Необходимо проверить на `https://asu-vch.local`:

1. открытие `/admin/content.php` владельцем;
2. активную плитку `Справочники` и переход на `/admin/directories.php`;
3. наличие ровно двух статичных плиток;
4. работу кнопки `К контенту`;
5. прямой доступ владельца;
6. тематическую страницу 403 для администратора, оператора или наблюдателя;
7. desktop-внешний вид и hover-поведение в обеих темах.

## 11. Итог

- local checkout / branch sync: **PASS**;
- PHP syntax на PHP 8.5.4: **PASS**;
- deploy file integrity: **PASS**;
- structural assertions: **PASS**;
- static RBAC/security review: **PASS**;
- runtime navigation smoke: **PENDING**;
- runtime RBAC: **PENDING**;
- desktop-визуальная приёмка обеих тем: **PENDING**;
- мобильное тестирование: **OUT OF SCOPE / NOT RUN**.

Merge до завершения runtime-проверок, пользовательской desktop-приёмки и отдельного явного разрешения запрещён.
