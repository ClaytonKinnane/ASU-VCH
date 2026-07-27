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

Дата автоматических проверок: `2026-07-27`.

## 2. Проверенная область

Проверены подготовленные изменения:

```text
public/admin/content.php
public/admin/directories.php
docs/design/DIRECTORIES-LANDING-V1-DESIGN.md
docs/design/DIRECTORIES-LANDING-V1-REVIEW.md
docs/decisions/DIRECTORIES-LANDING-V1-APPROVAL.md
```

## 3. PHP syntax

Команды:

```text
php -l public/admin/content.php
php -l public/admin/directories.php
```

Результат:

```text
No syntax errors detected in public/admin/content.php
No syntax errors detected in public/admin/directories.php
```

Статус: **PASS**.

Примечание: проверка выполнена в доступной среде на PHP `8.4.16`. Целевое локальное развёртывание пользователя использует PHP `8.5.4`, поэтому после deploy требуется повторный lint в целевой среде.

## 4. Структурная проверка

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

## 5. RBAC / security review

Статически подтверждено:

- обе страницы используют существующий server-side owner guard;
- новые permissions отсутствуют;
- migration отсутствуют;
- формы и POST-маршруты отсутствуют;
- динамический `display_name` выводится через существующий `e()`.

Статус: **PASS (STATIC)**.

Полная runtime-проверка ответов 403 требует целевого локального развёртывания и тестовых сессий ролей.

## 6. Визуальная проверка

Автоматически подтверждён class contract:

- активная плитка повторно использует принятый hover-контракт `dashboard-tile`;
- компактный размер сохраняется классом `module-tile`;
- статичные плитки не содержат `dashboard-tile` и остаются неинтерактивными.

Фактическая desktop-визуальная проверка в браузере для тем `АСУ Синяя` и `АСУ Светлая синяя` **не выполнялась в доступной среде** и остаётся обязательным пользовательским этапом после deploy.

Статус: **PENDING USER DESKTOP ACCEPTANCE**.

## 7. Мобильное тестирование

Мобильное тестирование исключено из области работ и не выполнялось. Заявление о проверенной мобильной версии не делается.

## 8. Ограничения среды

Недоступны:

- локальный репозиторий пользователя `C:\Project\ASU-VCH`;
- локальное развёртывание `C:\OSPanel\home\asu-vch.local`;
- домен `https://asu-vch.local`;
- runtime-сессии владельца и остальных ролей;
- браузерная desktop-приёмка целевого развёртывания.

## 9. Итог

- PHP syntax: **PASS**;
- structural assertions: **PASS**;
- static RBAC/security review: **PASS**;
- scope compliance: **PASS**;
- runtime deploy/smoke: **NOT RUN**;
- desktop-визуальная приёмка обеих тем: **PENDING**;
- мобильное тестирование: **OUT OF SCOPE / NOT RUN**.

Инкремент готов к публикации в feature-ветку и последующему deploy пользователем для обязательной runtime и desktop-приёмки. Merge до отдельного разрешения запрещён.
