# Security User Archive & Restore v1 — Architecture and Specification

Дата: 2026-07-26
Статус: подготовлено к review и утверждению владельцем проекта
Ветка: `feature/user-archive-restore`
База: `main` (`859a2dc7462a41a4e630b637485bf346437ccdd0`)

## 1. Назначение

Инкремент добавляет контролируемое архивирование и восстановление учетных записей без физического удаления строк из таблицы `users`.

Архивирование должно:

- немедленно закрывать пользователю доступ;
- сохранять идентичность, роли, пароль и workflow-статус;
- фиксировать субъекта, время и основание;
- защищать последнего активного владельца системы;
- переводить карточку в read-only.

Восстановление должно:

- возвращать запись из архива без автоматического доступа;
- сохранять прежний `approval_status`;
- фиксировать субъекта, время и основание;
- оставлять логин, email, роли и пароль неизменными.

## 2. Существующие возможности

В `users` уже существует:

```text
deleted_at DATETIME NULL
```

Текущие сервисы редактирования, ролей и активности уже запрещают изменения при `deleted_at IS NOT NULL`.

Вход и проверка текущей сессии требуют одновременно:

```text
is_active = 1
approval_status = approved
deleted_at IS NULL
```

В RBAC уже существуют и назначены администратору:

```text
security.users.archive
security.users.restore
```

Владелец получает их через `system.*.*`. Новые permission codes не добавляются.

Ожидаемые инварианты после migration 005:

```text
4 системные роли
19 системных разрешений
```

## 3. Модель состояния

Архив является отдельным состоянием поверх workflow подтверждения:

```text
Не в архиве: deleted_at IS NULL
В архиве:    deleted_at IS NOT NULL
```

Архивировать разрешено неархивированную запись в любом workflow-состоянии:

```text
pending
approved + active
approved + blocked
rejected
```

Архивирование всегда устанавливает:

```text
is_active = 0
deleted_at = server time
```

`approval_status` при архивировании и восстановлении не изменяется.

## 4. Безопасное восстановление

Восстановление никогда не активирует пользователя автоматически:

```text
deleted_at = NULL
is_active = 0
```

Матрица результата:

| До архивирования | После восстановления |
|---|---|
| `pending`, inactive | `pending`, inactive |
| `rejected`, inactive | `rejected`, inactive |
| `approved`, active | `approved`, inactive — заблокирован |
| `approved`, inactive | `approved`, inactive — заблокирован |

Подтвержденного пользователя после restore требуется отдельно разблокировать через существующую операцию с permission `security.users.block`.

## 5. Migration 005

Добавляется:

```text
database/migrations/005_security_user_archive_restore.sql
```

Новые поля `users`:

```text
archived_by      BIGINT UNSIGNED NULL
last_archived_at DATETIME NULL
archive_reason   VARCHAR(500) NULL
restored_by      BIGINT UNSIGNED NULL
restored_at      DATETIME NULL
restore_reason   VARCHAR(500) NULL
```

Роли timestamp:

- `deleted_at` — канонический признак и время текущего архивного состояния;
- `last_archived_at` — audit snapshot последнего архивирования, сохраняемый после restore;
- `restored_at` — время последнего восстановления текущего цикла.

`last_archived_at` не используется в guards, фильтрах или определении состояния.

Внешние ключи:

```text
fk_users_archived_by
archived_by -> users.id
ON UPDATE RESTRICT
ON DELETE SET NULL

fk_users_restored_by
restored_by -> users.id
ON UPDATE RESTRICT
ON DELETE SET NULL
```

Индексы:

```text
idx_users_deleted_at
idx_users_archived_by
idx_users_last_archived_at
idx_users_restored_by
idx_users_restored_at
```

Поля nullable для совместимости с историческими строками и ручным `deleted_at`. Миграция не создает фиктивного субъекта, времени или основания.

Migrations 001–004 не изменяются.

## 6. Сохранение идентичности и данных

Архивирование не освобождает уникальные значения:

```text
username_canonical
email_canonical
```

Создание новой записи с логином или email архивированного пользователя остается запрещено существующими UNIQUE-ограничениями.

Archive/restore не изменяют:

```text
username
username_canonical
email
email_canonical
password_hash
display_name
approval_status
approved_by
approved_at
rejected_by
rejected_at
rejection_reason
created_by
creation_reason
is_temporary
must_change_password
last_login_at
roles
```

## 7. Запрет самоархивирования

Пользователь не может архивировать собственную учетную запись:

```text
actorId === userId
```

Сообщение:

```text
Нельзя архивировать собственную учетную запись.
```

Запрет выполняется серверным сервисом внутри транзакции; скрытие кнопки не считается защитой.

## 8. Защита последнего активного владельца

Если цель одновременно:

```text
имеет роль system_owner
is_active = 1
approval_status = approved
deleted_at IS NULL
```

сервис блокирует строки действующих владельцев через `SELECT ... FOR UPDATE` и запрещает архивирование, если их число не превышает одного.

Сообщение:

```text
Нельзя архивировать последнего активного владельца системы.
```

Неактивный, pending или rejected пользователь с ролью владельца не считается действующим владельцем.

## 9. Сервис

Добавляется:

```text
app/Security/UserArchiveRestoreService.php
```

Методы:

```php
archive(int $userId, int $actorId, string $reason): array
restore(int $userId, int $actorId, string $reason): array
```

Оба метода:

1. валидируют основание до открытия транзакции;
2. открывают транзакцию;
3. загружают цель через `SELECT ... FOR UPDATE`;
4. повторно проверяют текущее состояние;
5. выполняют атомарный update;
6. commit или rollback;
7. возвращают логин для PRG-сообщения.

## 10. Операция archive

Разрешена только при `deleted_at IS NULL`.

Последовательность:

1. Проверить основание.
2. Заблокировать целевую строку.
3. Проверить существование.
4. Запретить повторное архивирование.
5. Запретить самоархивирование.
6. Проверить last-owner guard.
7. Записать состояние и аудит.
8. Зафиксировать транзакцию.

Обновление:

```text
is_active       = 0
deleted_at      = now
archived_by     = actorId
last_archived_at= now
archive_reason  = validated reason
restored_by     = NULL
restored_at     = NULL
restore_reason  = NULL
updated_at      = now
```

Повторный archive возвращает:

```text
Учетная запись уже архивирована.
```

## 11. Операция restore

Разрешена только при `deleted_at IS NOT NULL`.

Последовательность:

1. Проверить основание.
2. Заблокировать целевую строку.
3. Проверить существование.
4. Запретить restore неархивированной записи.
5. Очистить текущий archive-state и оставить доступ заблокированным.
6. Записать restore-аудит.
7. Зафиксировать транзакцию.

Обновление:

```text
deleted_at      = NULL
is_active       = 0
restored_by     = actorId
restored_at     = now
restore_reason  = validated reason
updated_at      = now
```

Сохраняются:

```text
archived_by
last_archived_at
archive_reason
approval_status
roles
password_hash
```

Повторный restore возвращает:

```text
Учетная запись не находится в архиве.
```

## 12. Последний цикл аудита

V1 хранит последний цикл непосредственно в `users`.

После archive:

```text
archive-поля заполнены
restore-поля пусты
deleted_at заполнен
```

После restore:

```text
archive-поля сохранены
restore-поля заполнены
deleted_at очищен
```

После повторного archive:

- archive-поля заменяются данными нового архивирования;
- restore-поля очищаются;
- сведения предыдущих циклов не сохраняются.

Полная append-only история будет отдельным инкрементом Security User Change History v1.

## 13. Основания

Основания archive и restore обязательны.

Правила:

- валидный UTF-8;
- после `trim()` от 10 до 500 символов;
- разрешен многострочный текст;
- запрещены управляющие символы, кроме перевода строки и табуляции;
- HTML не интерпретируется;
- вывод только через экранирование.

Ошибки:

```text
Основание архивирования должно содержать от 10 до 500 символов.
Основание восстановления должно содержать от 10 до 500 символов.
```

## 14. Маршруты

Добавляются:

```text
POST /admin/users/archive.php
POST /admin/users/restore.php
```

Archive route:

- `require_permission('security.users.archive')`;
- POST-only;
- CSRF;
- строгая проверка `user_id`;
- actor из текущей аутентифицированной сессии;
- вызов сервиса;
- redirect-after-POST в карточку.

Restore route аналогично требует `security.users.restore`.

GET не меняет данные и перенаправляется в список пользователей.

Прямой запрос без permission получает HTTP 403 до обработки операции.

## 15. Прекращение доступа

Archive атомарно устанавливает:

```text
is_active = 0
deleted_at IS NOT NULL
```

Это блокирует новый вход существующим login-query.

Уже открытая сессия теряет аутентификацию при следующем HTTP-запросе, потому что `current_user()` загружает только active + approved + nonarchived пользователя.

Отдельная server-side таблица сессий в v1 не требуется.

## 16. Карточка неархивированной записи

Форма archive показывается при:

```text
includeSensitive = true
has_permission('security.users.archive')
deleted_at IS NULL
actorId !== targetId
```

Зона:

```text
Основание архивирования
[textarea]
[Архивировать пользователя]
```

Подтверждение:

```text
Архивировать учетную запись? Доступ пользователя будет немедленно прекращен.
```

На собственной карточке форма отсутствует; допустимо пояснение о запрете самоархивирования.

## 17. Карточка архивированной записи

Статус:

```text
Архивирован
```

Карточка read-only:

- нет редактирования основных данных;
- нет изменения ролей;
- нет approve/reject;
- нет block/unblock;
- нет archive.

Restore-форма показывается при:

```text
includeSensitive = true
has_permission('security.users.restore')
deleted_at IS NOT NULL
```

Зона:

```text
Основание восстановления
[textarea]
[Восстановить пользователя]
```

Подтверждение:

```text
Восстановить учетную запись? После восстановления доступ останется заблокированным.
```

## 18. Отображение аудита

`UserDetailRepository` при sensitive-загрузке получает audit-поля и actor-данные для archive/restore.

В карточку добавляется блок:

```text
Архивирование и восстановление
```

Archive-аудит:

```text
Архивировал
Дата архивирования       <- last_archived_at
Основание архивирования
```

Restore-аудит при наличии:

```text
Восстановил
Дата восстановления      <- restored_at
Основание восстановления
```

Если историческая архивированная строка не имеет actor/reason:

```text
Системная или историческая операция
```

Если actor недоступен:

```text
Субъект недоступен
```

Подробности доступны только при `includeSensitive = true`. Operator и viewer видят общий статус и роли, но не actors, даты, основания и action forms.

## 19. Список и счетчики

Существующий фильтр `Архивированные` используется как единственная штатная точка поиска архивных записей.

Default list меняется:

```text
status не выбран -> deleted_at IS NULL
```

Архивированные записи не появляются в обычном рабочем списке.

Плитка `Всего` переименовывается в:

```text
Не в архиве
```

Счетчики:

```text
Не в архиве:    deleted_at IS NULL
Активные:       active + approved + nonarchived
Ожидают:        pending + nonarchived
Отклоненные:    rejected + nonarchived
Заблокированные:approved + inactive + nonarchived
Архивированные: deleted_at IS NOT NULL
```

Поиск с фильтром `Архивированные` продолжает работать по username, display name и email.

## 20. Конкурентность

Archive и restore используют row lock целевой строки.

Существующие update, role update, status, approve и reject сервисы должны повторно проверять `deleted_at` после получения своего lock. При обнаружении архива операция прекращается.

Гарантии:

- два archive-запроса: первый успешен, второй получает «уже архивирована»;
- два restore-запроса: первый успешен, второй получает «не находится в архиве»;
- archive против update/status/role/approve/reject: второе завершившееся действие повторно проверяет состояние;
- last-owner guard выполняется внутри archive-транзакции;
- повторный POST не перезаписывает аудит без допустимого перехода.

## 21. Сообщения

Успех:

```text
Учетная запись «<логин>» архивирована.
Учетная запись «<логин>» восстановлена и оставлена заблокированной.
```

Ошибки:

```text
Учетная запись не найдена.
Учетная запись уже архивирована.
Учетная запись не находится в архиве.
Нельзя архивировать собственную учетную запись.
Нельзя архивировать последнего активного владельца системы.
Основание архивирования должно содержать от 10 до 500 символов.
Основание восстановления должно содержать от 10 до 500 символов.
Учетная запись не архивирована из-за серверной ошибки.
Учетная запись не восстановлена из-за серверной ошибки.
```

Исключения журналируются без PII, логина, email и текста основания.

## 22. Предполагаемые файлы реализации

Новые:

```text
app/Security/UserArchiveRestoreService.php
database/migrations/005_security_user_archive_restore.sql
database/check-security-user-archive-restore.php
public/admin/users/archive.php
public/admin/users/restore.php
```

Изменяемые:

```text
app/bootstrap.php
app/Security/UserDetailRepository.php
app/Security/UserListRepository.php
public/admin/users.php
public/admin/users/view.php
themes/asu-blue/assets/css/users.css
```

Permission count остается 19; migration 005 не вставляет новые permissions.

## 23. Автоматизированные проверки

Добавляется:

```text
database/check-security-user-archive-restore.php
```

Проверяются:

- migration 005 и шесть новых полей;
- FK `ON DELETE SET NULL` и индексы;
- 19 permissions и существующие archive/restore grants;
- валидация обоих оснований;
- запрет самоархивирования;
- archive approved/pending/rejected fixtures;
- `is_active = 0`, archive audit и очистка restore audit;
- повторный archive;
- невозможность login/current-user для архива;
- запрет update, roles, status, approve и reject для архива;
- restore с `is_active = 0`;
- сохранение identity, password, roles, workflow и прежнего decision audit;
- повторный restore;
- state matrix pending/rejected/approved;
- повторный archive после restore;
- cleanup в `finally`.

Last-active-owner guard тестируется только в изолированной тестовой базе или после проверенной резервной копии. Основной checker не должен временно лишать рабочую базу единственного владельца.

## 24. Регрессия и ручная приемка

Автоматически:

```text
Test-PhpSyntax.ps1
install.php — первый и повторный запуск
check-security-rbac.php
check-security-user-approval.php
check-security-required-password-change.php
check-security-user-rejection.php
check-security-user-archive-restore.php
Test-LocalSmoke.ps1
```

Ручная desktop-проверка:

- archive active/blocked/pending/rejected;
- обязательность и escaping оснований;
- завершение существующей сессии цели на следующем запросе;
- запрет нового входа;
- self-archive guard;
- last-owner guard в безопасной среде;
- read-only archive-card;
- audit и actor links;
- privacy для operator/viewer;
- restore каждого workflow-состояния;
- approved restore как blocked и последующий явный unblock;
- повторные и конкурентные POST;
- CSRF 419;
- direct route без permission — HTTP 403;
- default list без архива;
- archive filter и counter.

Мобильная версия не входит в критерии приемки. Responsive-код не удаляется, но не заявляется как проверенный.

## 25. Вне объема

- физическое удаление;
- освобождение логина или email;
- массовые операции;
- автоматическая активация после restore;
- изменение ролей или пароля во время restore;
- email-уведомления;
- отдельное session storage и принудительное удаление cookies;
- append-only история нескольких циклов;
- общая история изменений пользователя;
- мобильная приемка.

## 26. Gate реализации

Реализация разрешается только после:

1. formal review;
2. устранения блокирующих findings;
3. отдельного явного утверждения владельца проекта;
4. создания approval-документа.

До утверждения запрещено добавлять migration 005, сервисы, маршруты и UI-функциональность.