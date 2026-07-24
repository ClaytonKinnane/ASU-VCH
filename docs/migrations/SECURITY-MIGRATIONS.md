# Спецификация миграций Security

## 1. Статус и назначение

Документ определяет физическую модель миграций домена `Security` и является обязательным контрактом для последующей реализации миграций, сидеров и команды первоначальной установки.

Источники требований:

- `docs/DATABASE.md`;
- `docs/NAMING.md`;
- `docs/domains/SECURITY.md`;
- `docs/domains/SECURITY-REVIEW.md`;
- `docs/erd/ERD-010-security.md`;
- `docs/erd/ERD-010-security-review.md`.

SQL и PHP-код не должны расходиться с этой спецификацией.

## 2. Зависимости

До выполнения миграций Security должны существовать:

- `reference_groups`;
- `reference_values`;
- таблица `soldiers` либо утвержденный план отложенного добавления FK `users.soldier_id`.

Если Organization еще не развернут, колонка `soldier_id` создается nullable без внешнего ключа, а FK добавляется отдельной интеграционной миграцией после создания `soldiers`.

## 3. Общие параметры хранения

- СУБД: MySQL 8.4.
- Engine: InnoDB.
- Character set: `utf8mb4`.
- Collation: единая регистронезависимая collation проекта.
- Время хранится в UTC.
- MySQL `ENUM` не используется.
- Все идентификаторы: `BIGINT UNSIGNED AUTO_INCREMENT`, кроме составной таблицы `role_permissions`.
- Даты: `DATETIME(6)`, если общесистемная спецификация не установит иной единый тип.

## 4. Порядок миграций

```text
001_create_roles_table
002_create_permissions_table
003_create_users_table
004_create_user_roles_table
005_create_role_permissions_table
006_add_security_indexes
007_add_user_roles_active_role_generated_column
008_create_users_status_validation_triggers
009_add_users_soldier_foreign_key
```

Системные данные не смешиваются со структурными миграциями.

После структурных миграций выполняются:

```text
SecurityReferenceSeeder
SecurityRoleSeeder
SecurityPermissionSeeder
SecurityRolePermissionSeeder
```

Первый владелец создается только отдельной командой установки.

## 5. Таблица roles

### 5.1 Колонки

| Поле | Тип | Null | Default |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | нет | — |
| `code` | VARCHAR(128) | нет | — |
| `name` | VARCHAR(255) | нет | — |
| `description` | TEXT | да | NULL |
| `is_system` | BOOLEAN/TINYINT(1) | нет | 0 |
| `created_at` | DATETIME(6) | нет | — |
| `updated_at` | DATETIME(6) | нет | — |
| `deleted_at` | DATETIME(6) | да | NULL |

### 5.2 Ограничения и индексы

```text
pk_roles
uq_roles_code
idx_roles_deleted_at
```

- PK: `id`.
- `code` уникален глобально и не переиспользуется после soft delete.
- `code` хранится в нижнем регистре ASCII.
- Значения `is_system = 1` защищаются доменным сервисом от удаления и изменения критических свойств.

## 6. Таблица permissions

### 6.1 Колонки

| Поле | Тип | Null | Default |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | нет | — |
| `code` | VARCHAR(191) | нет | — |
| `name` | VARCHAR(255) | нет | — |
| `description` | TEXT | да | NULL |
| `module` | VARCHAR(64) | нет | — |
| `is_system` | BOOLEAN/TINYINT(1) | нет | 0 |
| `created_at` | DATETIME(6) | нет | — |
| `updated_at` | DATETIME(6) | нет | — |

### 6.2 Ограничения и индексы

```text
pk_permissions
uq_permissions_code
idx_permissions_module
```

- PK: `id`.
- `code` уникален и соответствует формату `module.resource.action`.
- `code` и `module` хранятся в нижнем регистре ASCII.
- Опубликованные системные разрешения не удаляются физически.

## 7. Таблица users

### 7.1 Колонки

| Поле | Тип | Null | Default |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | нет | — |
| `soldier_id` | BIGINT UNSIGNED | да | NULL |
| `status_id` | BIGINT UNSIGNED | нет | — |
| `username` | VARCHAR(64) | нет | — |
| `email` | VARCHAR(254) | да | NULL |
| `password_hash` | VARCHAR(255) | нет | — |
| `must_change_password` | BOOLEAN/TINYINT(1) | нет | 0 |
| `last_login_at` | DATETIME(6) | да | NULL |
| `password_changed_at` | DATETIME(6) | да | NULL |
| `created_at` | DATETIME(6) | нет | — |
| `updated_at` | DATETIME(6) | нет | — |
| `deleted_at` | DATETIME(6) | да | NULL |

### 7.2 Ограничения и индексы

```text
pk_users
uq_users_username
uq_users_email
uq_users_soldier_id
idx_users_status_id
idx_users_deleted_at
```

- `username` и `email` не переиспользуются после soft delete.
- Несколько `NULL` в `email` допустимы.
- `soldier_id` nullable и уникален при наличии значения.
- `username` и `email` нормализуются прикладным сервисом до записи и хранятся в нижнем регистре.
- Пароль хранится только в `password_hash`.

### 7.3 Внешние ключи

```text
fk_users_status_id_reference_values
fk_users_soldier_id_soldiers
```

Для обоих FK:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

FK на `soldiers` может быть добавлен отложенной интеграционной миграцией.

## 8. Таблица user_roles

### 8.1 Колонки

| Поле | Тип | Null | Default |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | нет | — |
| `user_id` | BIGINT UNSIGNED | нет | — |
| `role_id` | BIGINT UNSIGNED | нет | — |
| `assigned_by` | BIGINT UNSIGNED | да | NULL |
| `assigned_at` | DATETIME(6) | нет | — |
| `revoked_at` | DATETIME(6) | да | NULL |
| `active_role_id` | BIGINT UNSIGNED GENERATED ALWAYS | да | generated |

Generated expression:

```sql
IF(`revoked_at` IS NULL, `role_id`, NULL)
```

Предпочтительный вариант хранения: `STORED`, если это требуется для стабильной индексируемости в принятом migration framework; иначе допускается `VIRTUAL` после отдельной проверки на MySQL 8.4.

### 8.2 Ограничения и индексы

```text
pk_user_roles
uq_user_roles_active_assignment
idx_user_roles_user_revoked
idx_user_roles_role_revoked
idx_user_roles_assigned_by
```

Уникальный индекс:

```text
UNIQUE (user_id, active_role_id)
```

Дополнительное ограничение:

```text
revoked_at IS NULL OR revoked_at >= assigned_at
```

Если framework не поддерживает именованный `CHECK`, он создается отдельным SQL-выражением миграции.

### 8.3 Внешние ключи

```text
fk_user_roles_user_id_users
fk_user_roles_role_id_roles
fk_user_roles_assigned_by_users
```

Все используют:

```text
ON DELETE RESTRICT
ON UPDATE RESTRICT
```

`assigned_by = NULL` допускается только для установки, миграции, системного seeder или идентифицированного системного процесса.

## 9. Таблица role_permissions

### 9.1 Колонки

| Поле | Тип | Null |
|---|---|---:|
| `role_id` | BIGINT UNSIGNED | нет |
| `permission_id` | BIGINT UNSIGNED | нет |

### 9.2 Ограничения и индексы

```text
pk_role_permissions (role_id, permission_id)
idx_role_permissions_permission_role
fk_role_permissions_role_id_roles
fk_role_permissions_permission_id_permissions
```

FK используют `RESTRICT` для удаления и обновления.

## 10. Проверка принадлежности user_status

Создаются два триггера:

```text
trg_users_bi_validate_status_group
trg_users_bu_validate_status_group
```

Они выполняются `BEFORE INSERT` и `BEFORE UPDATE` и проверяют, что `NEW.status_id` указывает на активное значение `reference_values`, принадлежащее активной группе `reference_groups.code = 'user_status'`.

При нарушении генерируется `SIGNAL SQLSTATE '45000'` с безопасным техническим сообщением.

Триггеры:

- не управляют переходами статусов;
- не активируют пользователей;
- не реализуют авторизацию;
- не заменяют доменную валидацию.

## 11. Системные seed-данные

Seeder выполняется идемпотентно по неизменяемым кодам.

### 11.1 Reference

Группа:

```text
user_status
```

Значения:

```text
pending
active
blocked
disabled
```

### 11.2 Роли

```text
system_owner
administrator
operator
viewer
```

`system_owner` обязательно имеет `is_system = true`.

Статус системности остальных начальных ролей фиксируется в каталоге seed-данных до реализации seeder.

### 11.3 Начальные разрешения

Минимальный каталог:

```text
security.users.view
security.users.create
security.users.update
security.users.block
security.users.disable
security.users.delete
security.roles.view
security.roles.create
security.roles.update
security.roles.assign
security.roles.revoke
security.permissions.view
security.permissions.manage
security.owner.transfer
```

Все опубликованные коды являются системным контрактом.

### 11.4 Связи ролей и разрешений

- `system_owner` получает полный доступ через утвержденную policy short-circuit; дублирование всех разрешений в `role_permissions` не является обязательным условием авторизации.
- `administrator`, `operator` и `viewer` получают только явно утвержденные наборы.
- Точная матрица начальных ролей фиксируется до реализации `SecurityRolePermissionSeeder`.

## 12. Первоначальная установка

Первый владелец не создается миграцией или seeder.

Отдельная одноразовая команда установки должна:

1. проверить наличие структурных миграций и системных seed-данных;
2. заблокировать конкурентную установку;
3. убедиться, что действующий `system_owner` отсутствует;
4. принять и валидировать данные первого пользователя;
5. создать пользователя со статусом `active`;
6. безопасно сформировать `password_hash`;
7. установить `must_change_password` согласно режиму выдачи пароля;
8. назначить роль `system_owner` с `assigned_by = NULL`;
9. выполнить создание пользователя и назначения одной транзакцией;
10. отметить установку завершенной;
11. передать событие в Audit с источником `installation`.

Повторное выполнение после успешной установки запрещено.

## 13. Откат

Откат выполняется в обратном порядке:

```text
1. удалить триггеры users
2. удалить FK users → soldiers, если он создан
3. удалить generated unique index user_roles
4. удалить generated column active_role_id
5. удалить role_permissions
6. удалить user_roles
7. удалить users
8. удалить permissions
9. удалить roles
```

Перед откатом production-среды требуется отдельная процедура подтверждения и резервного копирования. Автоматический destructive rollback рабочих данных не считается штатной операцией.

Seeder rollback не должен удалять системные коды, если они уже используются рабочими данными.

## 14. Проверки после миграции

Автоматически проверяются:

- наличие всех пяти таблиц;
- типы и nullable-свойства колонок;
- отсутствие MySQL `ENUM`;
- наличие PK, FK, UQ и индексов с утвержденными именами;
- уникальность `roles.code`, `permissions.code`, `users.username`, `users.email`;
- существование generated column `active_role_id`;
- невозможность создать два действующих назначения одной роли пользователю;
- возможность создать повторное историческое назначение после отзыва;
- невозможность установить `revoked_at < assigned_at`;
- наличие обоих триггеров статуса;
- отклонение `status_id` из другой справочной группы;
- наличие группы `user_status` и четырех статусов;
- наличие четырех начальных ролей;
- наличие каталога разрешений;
- отсутствие первого владельца до выполнения installation command;
- существование ровно одного владельца после установки.

## 15. Требования к тестам миграций

Обязательны:

- миграция чистой БД;
- повторный идемпотентный запуск seeders;
- rollback тестовой БД;
- проверка ограничений конкурентными вставками;
- проверка нормализации на уровне сервиса;
- проверка FK с `RESTRICT`;
- проверка триггеров на INSERT и UPDATE;
- проверка установки первого владельца;
- отказ повторной первоначальной установки.

## 16. Открытое решение перед реализацией

До написания `SecurityRolePermissionSeeder` необходимо утвердить точную начальную матрицу разрешений для ролей:

```text
administrator
operator
viewer
```

Это единственный оставшийся содержательный вопрос данной спецификации. Он не изменяет структуру ERD, но блокирует реализацию seed-связей ролей и разрешений.

## 17. Критерии утверждения

Спецификация готова к реализации после:

- архитектурного ревью типов и ограничений;
- подтверждения стратегии отложенного FK на `soldiers`;
- проверки generated column и уникального индекса на MySQL 8.4;
- проверки синтаксиса триггеров;
- утверждения матрицы ролей и разрешений;
- явного одобрения документа.

После утверждения создаются миграции, seeders и интеграционные тесты. Контроллеры и UI на этом этапе не реализуются.
