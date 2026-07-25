# Security User Archive & Restore v1 — Architecture and Specification

Дата: 2026-07-26
Статус: подготовлено к review и утверждению владельцем проекта
Ветка: `feature/user-archive-restore`
База: `main` (`859a2dc7462a41a4e630b637485bf346437ccdd0`)

## 1. Назначение

Инкремент добавляет контролируемое архивирование и восстановление учетных записей пользователей без физического удаления строк из таблицы `users`.

Архивирование должно:

- немедленно закрывать доступ к системе;
- сохранять идентичность, роли, пароль и предыдущий workflow-статус;
- фиксировать субъекта и основание операции;
- защищать последнего активного владельца системы;
- делать архивированную карточку недоступной для обычного редактирования.

Восстановление должно:

- возвращать запись из архива без автоматического предоставления доступа;
- сохранять прежний `approval_status`;
- фиксировать субъекта, дату и основание восстановления;
- оставлять логин, email, роли и пароль неизменными.

Физическое удаление пользователей не реализуется.

## 2. Текущая модель

В таблице `users` уже существует:

```text
deleted_at DATETIME NULL
```

Текущие сервисы редактирования, ролей и активности уже запрещают изменения при `deleted_at IS NOT NULL`. Вход и проверка активной сессии также требуют:

```text
is_active = 1
approval_status = approved
deleted_at IS NULL
```

В RBAC уже существуют разрешения:

```text
security.users.archive
security.users.restore
```

Они уже назначены роли `administrator`; `system_owner` получает их через `system.*.*`.

Следовательно, v1 не вводит новые коды разрешений и не увеличивает число системных permissions.

Ожидаемое количество после migration 005:

```text
4 системные роли
19 системных разрешений
```

## 3. Модель состояний

Архив является ортогональным состоянием поверх workflow подтверждения.

Основные признаки:

```text
Не в архиве: deleted_at IS NULL
В архиве:    deleted_at IS NOT NULL
```

Архивироваться могут неархивированные записи со статусами:

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

`approval_status` при архивировании не изменяется.

## 4. Безопасное состояние после восстановления

Восстановление никогда не возвращает активный доступ автоматически.

Матрица результата:

| Состояние до архивирования | Состояние после восстановления |
|---|---|
| `pending`, inactive | `pending`, inactive |
| `rejected`, inactive | `rejected`, inactive |
| `approved`, active | `approved`, inactive — заблокирован |
| `approved`, inactive | `approved`, inactive — заблокирован |

Операция восстановления выполняет:

```text
deleted_at = NULL
is_active = 0
```

Для возвращения подтвержденного пользователя к работе после восстановления требуется отдельная явная операция «Разблокировать» с permission `security.users.block`.

## 5. Migration 005

Добавляется файл:

```text
database/migrations/005_security_user_archive_restore.sql
```

В таблицу `users` добавляются:

```text
archived_by    BIGINT UNSIGNED NULL
archive_reason VARCHAR(500) NULL
restored_by    BIGINT UNSIGNED NULL
restored_at    DATETIME NULL
restore_reason VARCHAR(500) NULL
```

Отдельное поле `archived_at` не добавляется.

Каноническим временем архивирования является существующее поле:

```text
deleted_at
```

Это исключает риск расхождения двух timestamp одной операции.

Добавляются внешние ключи:

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

Добавляются индексы:

```text
idx_users_deleted_at
idx_users_archived_by
idx_users_restored_by
idx_users_restored_at
```

Новые audit-поля nullable:

- для совместимости с уже существующими строками, где `deleted_at` мог быть заполнен вручную;
- чтобы migration не выдумывала исторического субъекта или основание;
- штатные сервисы обязаны заполнять соответствующие поля для новых операций.

Существующие migrations 001–004 не редактируются.

## 6. Идентичность архивированной записи

Архивирование не освобождает:

```text
username_canonical
email_canonical
```

Архивированная запись продолжает участвовать в существующих UNIQUE-ограничениях.

Это запрещает создание нового пользователя с логином или email архивированной учетной записи и предотвращает подмену личности.

Не изменяются:

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

## 7. Разрешенные и запрещенные цели архивирования

Архивировать можно любую существующую неархивированную запись, кроме следующих случаев.

### 7.1 Самоархивирование

Пользователь не может архивировать собственную учетную запись:

```text
actorId === userId -> запрет
```

Причина: операция немедленно прекращает доступ и может оборвать административную сессию до завершения контролируемого процесса.

Сообщение:

```text
Нельзя архивировать собственную учетную запись.
```

### 7.2 Последний активный владелец

Если целевой пользователь:

```text
имеет роль system_owner
AND is_active = 1
AND approval_status = approved
AND deleted_at IS NULL
```

сервис блокирует всех активных владельцев через `SELECT ... FOR UPDATE` и запрещает операцию, если действующий владелец только один.

Сообщение:

```text
Нельзя архивировать последнего активного владельца системы.
```

Неактивный, pending или rejected пользователь с ролью `system_owner` не считается действующим владельцем, но его архивирование все равно разрешено только другому субъекту с соответствующим permission.

### 7.3 Повторное архивирование

Если `deleted_at IS NOT NULL`, операция запрещается:

```text
Учетная запись уже архивирована.
```

## 8. Сервис архивирования и восстановления

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

1. проверяют UTF-8 и основание;
2. открывают транзакцию;
3. загружают цель через `SELECT ... FOR UPDATE`;
4. проверяют допустимость состояния;
5. выполняют атомарное обновление;
6. фиксируют транзакцию;
7. возвращают логин для PRG-сообщения.

При исключении транзакция откатывается.

## 9. Операция archive

Последовательность:

1. Проверить основание.
2. Открыть транзакцию.
3. Загрузить пользователя с row lock.
4. Проверить существование.
5. Проверить `deleted_at IS NULL`.
6. Запретить `actorId === userId`.
7. Если цель — действующий владелец, проверить число активных владельцев под блокировкой.
8. Записать архивное состояние и аудит.
9. Зафиксировать транзакцию.

Обновление:

```text
is_active      = 0
deleted_at     = server time
archived_by    = actorId
archive_reason = validated reason
restored_by    = NULL
restored_at    = NULL
restore_reason = NULL
updated_at     = server time
```

Очистка restore-полей означает начало нового текущего цикла архивирования.

## 10. Операция restore

Последовательность:

1. Проверить основание.
2. Открыть транзакцию.
3. Загрузить пользователя с row lock.
4. Проверить существование.
5. Разрешить операцию только при `deleted_at IS NOT NULL`.
6. Снять признак архива и оставить доступ заблокированным.
7. Записать аудит восстановления.
8. Зафиксировать транзакцию.

Обновление:

```text
deleted_at     = NULL
is_active      = 0
restored_by    = actorId
restored_at    = server time
restore_reason = validated reason
updated_at     = server time
```

Не изменяются:

```text
archived_by
archive_reason
approval_status
roles
password_hash
```

Повторное восстановление неархивированной записи запрещается:

```text
Учетная запись не находится в архиве.
```

## 11. Аудит и повторные циклы

Модель v1 хранит последний текущий цикл архивирования и восстановления непосредственно в `users`.

После первого archive:

```text
archived_by заполнен
deleted_at заполнен
archive_reason заполнен
restore-поля пусты
```

После restore:

```text
archived_by сохранен
archive_reason сохранен
deleted_at очищен
restored_by заполнен
restored_at заполнен
restore_reason заполнен
```

После повторного archive:

- `archived_by`, `deleted_at`, `archive_reason` заменяются данными нового архивирования;
- `restored_by`, `restored_at`, `restore_reason` очищаются.

Полная история нескольких циклов не входит в v1. Она должна быть реализована будущим append-only журналом Security User Change History v1.

## 12. Основания операций

Оба основания обязательны.

Правила для archive и restore:

- после `trim()` — от 10 до 500 символов;
- строка должна быть валидным UTF-8;
- разрешен многострочный текст;
- запрещены управляющие символы, кроме перевода строки и табуляции;
- HTML не интерпретируется;
- вывод выполняется только через экранирование.

Ошибки:

```text
Основание архивирования должно содержать от 10 до 500 символов.
Основание восстановления должно содержать от 10 до 500 символов.
```

## 13. Маршруты

Добавляются:

```text
POST /admin/users/archive.php
POST /admin/users/restore.php
```

`archive.php`:

- требует `security.users.archive`;
- принимает изменение только через POST;
- проверяет CSRF;
- валидирует `user_id`;
- передает основание и текущего actor в сервис;
- использует redirect-after-POST;
- возвращает пользователя в карточку.

`restore.php`:

- требует `security.users.restore`;
- принимает изменение только через POST;
- проверяет CSRF;
- валидирует `user_id`;
- передает основание и текущего actor в сервис;
- использует redirect-after-POST;
- возвращает пользователя в карточку.

GET-запросы не меняют данные и перенаправляются к списку пользователей.

Permission проверяется сервером до обработки входных данных.

## 14. Прекращение доступа и сессии

Архивирование устанавливает одновременно:

```text
is_active = 0
deleted_at IS NOT NULL
```

Новый вход становится невозможным существующим login-query.

Уже открытая сессия прекращает считаться аутентифицированной при следующем запросе, потому что `current_user()` каждый запрос загружает только пользователя с:

```text
is_active = 1
approval_status = approved
deleted_at IS NULL
```

Отдельная таблица сессий и массовое удаление session cookies в v1 не требуются.

## 15. Карточка неархивированного пользователя

При выполнении условий:

```text
includeSensitive = true
has_permission('security.users.archive')
deleted_at IS NULL
actorId !== targetId
```

добавляется опасная зона:

```text
Основание архивирования
[textarea]

[Архивировать пользователя]
```

Подтверждение браузера:

```text
Архивировать учетную запись? Доступ пользователя будет немедленно прекращен.
```

Для собственной карточки форма архивирования не показывается. Допустима поясняющая строка:

```text
Собственную учетную запись архивировать нельзя.
```

## 16. Карточка архивированного пользователя

Основной статус:

```text
Архивирован
```

Архивированная карточка является read-only:

- не показывается редактирование основных данных;
- не показывается изменение ролей;
- не показываются approve/reject;
- не показываются block/unblock;
- не показывается archive.

При выполнении условий:

```text
includeSensitive = true
has_permission('security.users.restore')
deleted_at IS NOT NULL
```

показывается зона восстановления:

```text
Основание восстановления
[textarea]

[Восстановить пользователя]
```

Подтверждение браузера:

```text
Восстановить учетную запись? После восстановления доступ останется заблокированным.
```

Для pending и rejected карточек уточнение в UI должно отражать, что сохраняется прежний workflow-статус.

## 17. Отображение аудита

Подробный аудит доступен только при `includeSensitive = true`, то есть владельцу и администратору в текущей модели.

В карточку добавляется раздел:

```text
Архивирование и восстановление
```

Для архивированной записи:

```text
Архивировал
Дата архивирования       <- deleted_at
Основание архивирования
```

Для ранее восстановленной записи:

```text
Архивировал
Дата архивирования       <- предыдущий archive timestamp недоступен после очистки deleted_at
Основание архивирования
Восстановил
Дата восстановления      <- restored_at
Основание восстановления
```

Так как `deleted_at` очищается при restore, v1 не может показывать точное время предыдущего архивирования после восстановления без отдельного `archived_at`. Чтобы сохранить принцип единственного канонического archive timestamp и одновременно обеспечить достоверный аудит, принимается следующее уточнение:

```text
при restore значение deleted_at перед очисткой копируется в новое поле last_archived_at
```

Поэтому окончательная migration 005 дополнительно добавляет:

```text
last_archived_at DATETIME NULL
```

Правила:

- при archive: `deleted_at = now`, `last_archived_at = now`;
- при restore: `deleted_at = NULL`, `last_archived_at` сохраняется;
- после повторного archive оба timestamp получают время нового archive.

`deleted_at` остается каноническим признаком и временем текущего архива; `last_archived_at` является только audit snapshot последней операции и не участвует в определении состояния.

Для исторической архивированной записи без субъекта выводится:

```text
Системная или историческая операция
```

Если actor был удален или недоступен:

```text
Субъект недоступен
```

Оператор и viewer видят общий статус и роли, но не видят:

- archive/restore actor;
- даты операций;
- основания;
- формы операций.

## 18. Уточненная схема migration 005

После анализа требования отображать дату последнего архивирования и после восстановления окончательный набор полей:

```text
archived_by      BIGINT UNSIGNED NULL
last_archived_at DATETIME NULL
archive_reason   VARCHAR(500) NULL
restored_by      BIGINT UNSIGNED NULL
restored_at      DATETIME NULL
restore_reason   VARCHAR(500) NULL
```

Индексы:

```text
idx_users_deleted_at
idx_users_archived_by
idx_users_last_archived_at
idx_users_restored_by
idx_users_restored_at
```

Это не дублирует состояние:

- `deleted_at` определяет текущий archive-state;
- `last_archived_at` хранит audit snapshot последнего archive и не используется в guards или фильтрах.

## 19. Список пользователей

Существующий фильтр `Архивированные` сохраняется.

Меняется default-поведение списка:

```text
без выбранного status -> deleted_at IS NULL
```

Архивированные записи не отображаются в обычном рабочем списке и доступны только через явный фильтр:

```text
Архивированные
```

Сводная плитка `Всего` переименовывается в:

```text
Не в архиве
```

Ее значение:

```sql
COUNT(*) WHERE deleted_at IS NULL
```

Плитка `Архивированные` продолжает считать:

```sql
COUNT(*) WHERE deleted_at IS NOT NULL
```

Остальные счетчики исключают архивированные записи.

Поиск внутри фильтра `Архивированные` работает по существующим полям username, display name и email.

## 20. Конкурентные действия

Archive, restore, approve, reject, block/unblock, update и role update должны конкурировать через row lock целевой строки.

Гарантии:

- одновременно отправленные archive и restore: успешно только действие, соответствующее состоянию строки после получения lock;
- одновременно отправленные archive и block/update/role update: archive либо другое изменение завершается первым, второе повторно проверяет `deleted_at`;
- archive и self/last-owner guard выполняются внутри одной транзакции;
- повторный POST не перезаписывает успешный аудит без нового допустимого перехода.

Если существующий сервис после ожидания lock не повторно проверяет `deleted_at`, он должен быть скорректирован в рамках реализации и покрыт регрессией.

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
Недостаточно прав для архивирования пользователя.
Недостаточно прав для восстановления пользователя.
Учетная запись не архивирована из-за серверной ошибки.
Учетная запись не восстановлена из-за серверной ошибки.
```

Серверные исключения журналируются без персональных данных, логина, email и текста основания.

## 22. Bootstrap и репозитории

В `app/bootstrap.php` регистрируется:

```php
user_archive_restore_service(): UserArchiveRestoreService
```

`UserDetailRepository` при sensitive-загрузке получает:

```text
archived_by
last_archived_at
archive_reason
restored_by
restored_at
restore_reason
archive actor
restore actor
```

`UserListRepository` меняет default-condition и summary согласно разделу 19.

## 23. Автоматизированная проверка

Добавляется:

```text
database/check-security-user-archive-restore.php
```

CLI-проверка подтверждает:

- migration 005 зарегистрирована;
- шесть audit-полей существуют;
- два внешних ключа используют `ON DELETE SET NULL`;
- необходимые индексы существуют;
- системных разрешений осталось 19;
- administrator имеет archive и restore permissions;
- короткое и невалидное основание archive отклоняется;
- короткое и невалидное основание restore отклоняется;
- самоархивирование запрещено;
- active approved пользователь архивируется;
- после archive `is_active = 0` и `deleted_at` заполнен;
- archive audit заполнен, restore audit очищен;
- повторный archive запрещен;
- архивированный пользователь не проходит login/current-user условия;
- update, role update, block/unblock, approve и reject не изменяют архивированную запись;
- restore очищает `deleted_at`, но оставляет `is_active = 0`;
- restore audit заполнен;
- роли, пароль, identity и approval audit сохраняются;
- повторный restore запрещен;
- pending после archive/restore остается pending inactive;
- rejected после archive/restore остается rejected inactive;
- повторный archive после restore создает новый последний цикл и очищает restore audit;
- тестовые строки удаляются в `finally`.

Проверка запрета последнего активного владельца выполняется только в изолированной тестовой базе или после создания проверенной резервной копии. CLI-check основной локальной базы не должен временно лишать систему единственного владельца.

## 24. Регрессионные проверки

Обязательны:

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

Ручная desktop-проверка включает:

- archive active/blocked/pending/rejected пользователя;
- обязательность и escaping основания;
- немедленное прекращение существующей сессии цели;
- невозможность нового входа;
- self-archive guard;
- last-owner guard в безопасной тестовой среде;
- read-only карточку архива;
- audit и actor links;
- privacy для operator/viewer;
- restore каждого workflow-статуса;
- approved restore как blocked;
- явное unblock после restore;
- повторные и конкурентные POST;
- CSRF 419;
- прямой маршрут без permission — HTTP 403;
- default list без архивированных записей;
- фильтр и счетчик архива.

Мобильная версия не входит в обязательные критерии приемки. Существующие responsive-стили не удаляются, но их работа в этом инкременте не заявляется как проверенная.

## 25. Файлы предполагаемой реализации

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
database/check-security-rbac.php — только если требуется уточнение инвариантов, count остается 19
```

Документация после реализации:

```text
docs/decisions/SECURITY-USER-ARCHIVE-RESTORE-V1-APPROVAL.md
docs/testing/SECURITY-USER-ARCHIVE-RESTORE-V1-TEST-REPORT.md
docs/design/SECURITY-USER-ARCHIVE-RESTORE-V1-PR-FINAL-REVIEW.md
```

## 26. Вне объема

- физическое удаление пользователей;
- освобождение логина или email;
- массовое архивирование или восстановление;
- автоматическая активация после restore;
- изменение ролей во время restore;
- сброс или изменение пароля;
- email-уведомления;
- произвольное завершение всех server-side сессий через отдельное session storage;
- полный append-only журнал нескольких archive/restore циклов;
- общая история изменений пользователя;
- мобильная приемка.

## 27. Критерии готовности к реализации

Реализация допускается только после:

1. review этого документа;
2. устранения блокирующих findings;
3. отдельного явного утверждения владельца проекта;
4. фиксации approval-документа.

До утверждения запрещено добавлять migration 005, сервисы, маршруты и UI-функциональность.