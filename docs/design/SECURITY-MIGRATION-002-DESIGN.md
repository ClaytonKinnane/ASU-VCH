# Проектирование миграции 002 — Security / Users Management

Статус: **Design for review**  
Связанная спецификация: `docs/specifications/SECURITY-USERS-MANAGEMENT-V1.md`  
Связанное решение: `docs/decisions/SECURITY-USERS-MANAGEMENT-V1-APPROVAL.md`

## 1. Назначение

Документ определяет безопасный порядок эволюции существующей стартовой схемы Security до модели Users Management v1.0.

Миграция получает имя:

```text
002_security_users_management.sql
```

Она применяется как к существующей локальной базе с владельцем системы, так и к новой пустой базе после `001_starter_security.sql`.

## 2. Исходное состояние

Миграция `001_starter_security.sql` уже создает:

- `migrations`;
- `system_settings`;
- `users`;
- `roles`;
- `permissions`;
- `user_roles`;
- `role_permissions`.

Текущий установщик жестко выполняет только `001_starter_security.sql`, а затем отдельно создает роль `system_owner`, разрешение `system.*.*` и их связь.

Следовательно, до добавления `002` установщик необходимо преобразовать в последовательный migration runner.

## 3. Обязательное изменение установщика

### 3.1 Поиск миграций

Установщик должен:

1. найти файлы `database/migrations/*.sql`;
2. отсортировать их по имени по возрастанию;
3. получить перечень уже примененных миграций из таблицы `migrations`;
4. выполнить только отсутствующие файлы;
5. записать имя миграции в `migrations` только после успешного выполнения SQL-файла.

Ожидаемый порядок:

```text
001_starter_security.sql
002_security_users_management.sql
```

### 3.2 Первичный запуск

До чтения перечня примененных миграций установщик должен гарантировать наличие таблицы `migrations` через минимальный `CREATE TABLE IF NOT EXISTS`.

`001_starter_security.sql` может сохранить аналогичное выражение: повторное создание таблицы безопасно.

### 3.3 Повторный запуск

Если записи `001` и `002` уже присутствуют, SQL-файлы повторно не выполняются.

Повторный запуск установщика может актуализировать системные справочники отдельной seed-процедурой, но не должен повторять DDL.

### 3.4 Транзакционность

MySQL выполняет неявный commit для многих DDL-операций. Поэтому нельзя заявлять атомарность всей SQL-миграции через обычную PDO-транзакцию.

Правила:

- файл миграции проектируется как линейная последовательность безопасных операций;
- запись в `migrations` создается только после успешного `PDO::exec`;
- при ошибке установка завершается с ненулевым кодом;
- миграция не должна содержать разрушительных изменений существующих данных.

## 4. Изменения схемы

### 4.1 `user_roles`

Добавить:

```sql
assigned_by BIGINT UNSIGNED NULL AFTER assigned_at
```

Добавить индекс:

```sql
INDEX idx_user_roles_assigned_by (assigned_by)
```

Добавить внешний ключ:

```sql
CONSTRAINT fk_user_roles_assigned_by
    FOREIGN KEY (assigned_by)
    REFERENCES users(id)
    ON UPDATE RESTRICT
    ON DELETE SET NULL
```

Существующие назначения, включая bootstrap-владельца, получают `assigned_by = NULL`.

### 4.2 `role_permissions`

Добавить:

```sql
assigned_by BIGINT UNSIGNED NULL AFTER assigned_at
```

Добавить индекс:

```sql
INDEX idx_role_permissions_assigned_by (assigned_by)
```

Добавить внешний ключ:

```sql
CONSTRAINT fk_role_permissions_assigned_by
    FOREIGN KEY (assigned_by)
    REFERENCES users(id)
    ON UPDATE RESTRICT
    ON DELETE SET NULL
```

Существующие системные назначения получают `assigned_by = NULL`.

## 5. Системные роли

Миграция создает или актуализирует роли:

| Код | Наименование | Системная |
|---|---|---|
| `system_owner` | Владелец системы | Да |
| `administrator` | Администратор | Да |
| `operator` | Оператор | Да |
| `viewer` | Наблюдатель | Да |

Используется `INSERT ... ON DUPLICATE KEY UPDATE`.

Обновление существующей роли допускает изменение:

- `name`;
- `description`;
- `is_system`;
- `updated_at`.

Код роли не изменяется.

## 6. Каталог разрешений

Миграция создает или актуализирует:

```text
system.*.*

security.users.view
security.users.create
security.users.update
security.users.block
security.users.archive
security.users.restore
security.users.reset_password
security.users.assign_roles

security.roles.view
security.roles.create
security.roles.update
security.roles.delete
security.roles.assign_permissions

security.permissions.view

system.settings.view
system.settings.update
system.diagnostics.view
```

Все разрешения версии 1.0 являются системными:

```text
is_system = 1
```

## 7. Начальная матрица ролей

### 7.1 `system_owner`

Назначается только:

```text
system.*.*
```

Абсолютное разрешение обрабатывается авторизационным сервисом как wildcard и не требует явного назначения каждого нового разрешения.

### 7.2 `administrator`

Назначаются:

```text
security.users.view
security.users.create
security.users.update
security.users.block
security.users.archive
security.users.restore
security.users.reset_password
security.users.assign_roles
security.roles.view
security.roles.create
security.roles.update
security.roles.assign_permissions
security.permissions.view
system.settings.view
system.settings.update
system.diagnostics.view
```

Не назначается:

```text
security.roles.delete
```

Это уменьшает риск удаления пользовательских ролей до появления полноценной защиты зависимостей. Разрешение остается в каталоге для будущей отдельной реализации.

### 7.3 `operator`

Назначается:

```text
security.users.view
```

### 7.4 `viewer`

Назначается:

```text
security.users.view
```

Ограничение чувствительных полей реализуется серверной политикой представления: `viewer` не получает email и `last_login_at` других пользователей.

## 8. Заполнение связей

Связи `role_permissions` создаются через выбор ID по стабильным кодам.

Требования:

- используется `INSERT ... SELECT`;
- дубликаты не создаются благодаря составному PK;
- `assigned_at` получает серверное время миграции;
- `assigned_by` остается `NULL` для системного seed;
- существующие связи не удаляются миграцией.

Миграция добавляет требуемые связи, но не выполняет полную синхронизацию посредством удаления неизвестных разрешений. Это защищает локальные или будущие расширения от потери данных.

## 9. Совместимость с существующим владельцем

Миграция не изменяет таблицу `users` и не обновляет:

- `username`;
- `email`;
- `password_hash`;
- `display_name`;
- `last_login_at`;
- флаги состояния;
- `deleted_at`.

Существующая связь пользователя с `system_owner` сохраняется.

После миграции действующий владелец продолжает входить с прежними учетными данными и получает абсолютный доступ через `system.*.*`.

## 10. Seed после миграций

Рекомендуется разделить установщик на два этапа:

```text
1. applyMigrations()
2. seedSystemSecurityCatalog()
```

Seed-процедура выполняется после всех миграций и повторно безопасна. Она нужна для актуализации названий и описаний системных ролей/разрешений без создания новой миграции для текстовых исправлений.

При этом структурные изменения и первоначальный обязательный каталог фиксируются в `002`, чтобы схема и данные были воспроизводимы одним набором миграций.

## 11. Вывод установщика

После выполнения установщик должен показывать:

```text
База данных: asu_vch
Применено миграций сейчас: <N>
Всего миграций: <N>
Последняя миграция: 002_security_users_management.sql
Пользователей: <N>
Первичная регистрация доступна|отключена
```

Сообщение о необходимости зарегистрировать первого владельца выводится только при фактическом `users = 0`.

Это также устраняет текущее противоречивое итоговое сообщение `Initialize-Local.ps1`.

## 12. Проверки до реализации UI

После реализации миграции необходимо подтвердить SQL-запросами:

1. В `migrations` есть `001` и `002`.
2. В `roles` существуют четыре системные роли.
3. В `permissions` существует полный каталог v1.0.
4. `system_owner` связан с `system.*.*`.
5. Матрица `administrator` соответствует разделу 7.2.
6. `operator` и `viewer` имеют `security.users.view`.
7. В обеих таблицах связей есть `assigned_by` и внешние ключи.
8. Существующий пользователь Admin и его password hash не изменились.
9. Повторный запуск установщика не меняет количество ролей, разрешений и связей.
10. Вход владельца и текущий smoke test продолжают работать.

## 13. План реализации

После review этого документа:

1. Реализовать migration runner в `database/install.php`.
2. Создать `database/migrations/002_security_users_management.sql`.
3. Вынести системный seed в отдельную функцию или сервис.
4. Исправить итоговое сообщение `Initialize-Local.ps1`.
5. Добавить CLI-проверку структуры и матрицы RBAC.
6. Выполнить локальную установку поверх существующей базы.
7. Выполнить повторную установку для проверки идемпотентности runner/seed.
8. Проверить вход владельца.
9. Запустить smoke test.
10. Зафиксировать результаты тестирования в документации.

## 14. Критерий Approval дизайна

Дизайн готов к реализации после подтверждения следующих решений:

- установщик становится последовательным migration runner;
- DDL не считается полностью транзакционным;
- `assigned_by` допускает `NULL`;
- миграция не удаляет неизвестные связи ролей и разрешений;
- `administrator` в v1.0 не получает `security.roles.delete`;
- `operator` и `viewer` получают только `security.users.view` в домене Security;
- чувствительные поля скрываются сервером для `viewer`.
