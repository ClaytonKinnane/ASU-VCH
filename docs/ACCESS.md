# Управление доступом

## Текущее состояние

```text
system roles: 4
system permissions: 25
owner wildcard: system.*.*
```

Системные роли:

- `system_owner` — владелец установки с абсолютным доступом через wildcard;
- `administrator` — административные операции в пределах назначенных permissions;
- `operator` — ограниченные рабочие операции;
- `viewer` — ограниченный просмотр с privacy-защитой.

## Первый владелец

Первый пользователь пустой установки создаётся через bootstrap-регистрацию и транзакционно получает `system_owner`. После успешного создания владельца публичная регистрация отключается.

Инварианты:

- допускается только один active owner;
- последнего active owner нельзя заблокировать, архивировать или лишить критического доступа;
- обычное управление ролями не назначает `system_owner`;
- последующие пользователи не получают owner автоматически.

## Авторизация

Permission не отменяет:

- authentication и user status checks;
- required password change;
- CSRF для POST;
- server validation;
- transaction/DB invariants;
- optimistic revisions;
- audit и privacy;
- last-owner protection.

Прямой доступ без permission возвращает themed HTTP 403. Anonymous access к admin перенаправляется на login.

## Пользовательский lifecycle

Реализованы:

- `pending` creation с обязательным основанием;
- approval и activation;
- rejection с основанием и audit;
- block/unblock;
- archive/restore с audit;
- required temporary-password change;
- login prohibition для inactive/rejected/archived records;
- privacy restrictions для чувствительного audit.

Restore не активирует пользователя автоматически.

## Organizational Structure permissions

Migration 009 добавляет:

```text
organization.structures.view
organization.structures.create
organization.structures.update
organization.structures.publish
organization.structures.archive
organization.structures.history
```

Они не назначаются автоматически ordinary system roles. `system_owner` получает доступ через `system.*.*`.

## Owner-only directories

Следующие read-only routes защищены существующим wildcard `system.*.*`:

```text
/admin/directories/military-ranks.php
/admin/directories/organizational-elements.php
/admin/directories/military-positions.php
/admin/directories/military-occupational-specialties.php
```

Migrations 010 и 011 не добавляют permissions. Общее количество остаётся 25.

Для этих каталогов:

- owner получает доступ;
- ordinary role без wildcard получает themed HTTP 403;
- пользовательские routes — GET-only;
- mutation controls и mutation endpoints отсутствуют;
- filters/search используют prepared statements;
- output escaped;
- official external links проходят safe URL validation.

Каталог должностей не предоставляет кадровые назначения. Каталог ВУС не предоставляет персональный воинский учёт и не связан с users/personnel.

## Безопасность изменяющих операций

Изменяющие операции других доменов:

- POST-only;
- permission protected;
- CSRF protected;
- identifiers и aggregate ownership validated;
- lifecycle и DB invariants повторно проверяются в service layer;
- expected revision применяется, где предусмотрено;
- transactions + prepared statements;
- change events и safe result messages.

## Последняя проверка

```text
system roles: 4
system permissions: 25
organization permissions: 6
ordinary automatic organization assignments: 0
owner access to PR #19/#20 directories: PASS
ordinary-role HTTP 403: PASS
read-only boundary: PASS
security regressions: PASS
```

Секреты, временные пароли и `config/local.php` не включаются в документацию и logs.
