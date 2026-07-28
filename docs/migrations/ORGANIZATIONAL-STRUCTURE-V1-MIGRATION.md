# Migration specification 009: organizational structure v1

## Порядок

```text
008_organizational_element_types_directory.sql
009_organizational_structure_v1.sql
```

## Создаваемые таблицы

1. `organizational_structures` — контейнер структуры и archive/restore lifecycle.
2. `organizational_structure_elements` — стабильные идентичности.
3. `organizational_structure_versions` — версии, периоды, revision и conditional guards.
4. `organizational_structure_documents` — реквизиты документов.
5. `organizational_structure_version_documents` — роли документов в версии.
6. `organizational_structure_nodes` — adjacency-list дерево.
7. `organizational_structure_change_events` — append-only история.

## Ключевые ограничения

- PK: `BIGINT UNSIGNED AUTO_INCREMENT`;
- UTF-8: `utf8mb4_unicode_ci`;
- MySQL `ENUM` не используется;
- entity FK: `ON UPDATE RESTRICT ON DELETE RESTRICT`;
- nullable actor FK: `ON DELETE SET NULL`;
- no cascade delete;
- generated `pending_guard`, `active_guard`, `root_guard`, `primary_guard`;
- композитные FK фиксируют принадлежность одной структуре, версии и каталогу, включая ссылки предметной истории на версию и стабильный элемент;
- triggers защищают неизменяемый код и archive/restore lifecycle контейнера, переходы версий, опубликованные узлы и документы, а также append-only историю;
- audit/change-history UPDATE и DELETE запрещены.

## Permissions

Migration создаёт шесть system permissions и не создаёт role-permission связей для `administrator`, `operator`, `viewer`.

Ожидаемое состояние:

```text
migrations: 9
system roles: 4
system permissions: 25
organizational structures after migration: 0
```

## Seed

Фактические и демонстрационные структуры отсутствуют. Checker использует синтетические данные внутри транзакции и всегда выполняет rollback.

## Rollback policy

Production rollback удалением migration 009 не предоставляется: таблицы хранят исторические сведения. При отказе до регистрации migration повторный запуск безопасен благодаря `CREATE TABLE IF NOT EXISTS` и `DROP TRIGGER IF EXISTS` перед созданием triggers.

## Verification

```text
php database/check-organizational-structure.php
```
