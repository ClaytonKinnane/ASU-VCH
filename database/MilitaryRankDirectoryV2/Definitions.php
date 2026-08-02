<?php

declare(strict_types=1);

function military_rank_v1_expected_compositions(): array
{
    return [
        ['code' => 'enlisted', 'name' => 'Солдаты, матросы, сержанты, старшины', 'parent_code' => null, 'sort_order' => 10],
        ['code' => 'warrant-officers', 'name' => 'Прапорщики и мичманы', 'parent_code' => null, 'sort_order' => 20],
        ['code' => 'officers', 'name' => 'Офицеры', 'parent_code' => null, 'sort_order' => 30],
        ['code' => 'junior-officers', 'name' => 'Младшие офицеры', 'parent_code' => 'officers', 'sort_order' => 31],
        ['code' => 'senior-officers', 'name' => 'Старшие офицеры', 'parent_code' => 'officers', 'sort_order' => 32],
        ['code' => 'higher-officers', 'name' => 'Высшие офицеры', 'parent_code' => 'officers', 'sort_order' => 33],
    ];
}

/** @return list<array{code:string,name:string,parent_code:?string,sort_order:int}> */

function military_rank_v2_expected_compositions(): array
{
    return [
        ['code' => 'enlisted', 'name' => 'Солдаты, матросы, сержанты и старшины', 'parent_code' => null, 'sort_order' => 10],
        ['code' => 'soldiers-and-sailors', 'name' => 'Солдаты и матросы', 'parent_code' => 'enlisted', 'sort_order' => 11],
        ['code' => 'sergeants-and-starshinas', 'name' => 'Сержанты и старшины', 'parent_code' => 'enlisted', 'sort_order' => 12],
        ['code' => 'warrant-officers', 'name' => 'Прапорщики и мичманы', 'parent_code' => null, 'sort_order' => 20],
        ['code' => 'officers', 'name' => 'Офицеры', 'parent_code' => null, 'sort_order' => 30],
        ['code' => 'junior-officers', 'name' => 'Младшие офицеры', 'parent_code' => 'officers', 'sort_order' => 31],
        ['code' => 'senior-officers', 'name' => 'Старшие офицеры', 'parent_code' => 'officers', 'sort_order' => 32],
        ['code' => 'higher-officers', 'name' => 'Высшие офицеры', 'parent_code' => 'officers', 'sort_order' => 33],
    ];
}

/** @return list<array{code:string,troop_name:string,naval_name:?string,sort_order:int,composition_code:string}> */

function military_rank_v2_expected_levels(): array
{
    return [
        ['code' => 'private', 'troop_name' => 'рядовой', 'naval_name' => 'матрос', 'sort_order' => 1, 'composition_code' => 'soldiers-and-sailors'],
        ['code' => 'corporal', 'troop_name' => 'ефрейтор', 'naval_name' => 'старший матрос', 'sort_order' => 2, 'composition_code' => 'soldiers-and-sailors'],
        ['code' => 'junior-sergeant', 'troop_name' => 'младший сержант', 'naval_name' => 'старшина 2 статьи', 'sort_order' => 3, 'composition_code' => 'sergeants-and-starshinas'],
        ['code' => 'sergeant', 'troop_name' => 'сержант', 'naval_name' => 'старшина 1 статьи', 'sort_order' => 4, 'composition_code' => 'sergeants-and-starshinas'],
        ['code' => 'senior-sergeant', 'troop_name' => 'старший сержант', 'naval_name' => 'главный старшина', 'sort_order' => 5, 'composition_code' => 'sergeants-and-starshinas'],
        ['code' => 'starshina', 'troop_name' => 'старшина', 'naval_name' => 'главный корабельный старшина', 'sort_order' => 6, 'composition_code' => 'sergeants-and-starshinas'],
        ['code' => 'warrant-officer', 'troop_name' => 'прапорщик', 'naval_name' => 'мичман', 'sort_order' => 7, 'composition_code' => 'warrant-officers'],
        ['code' => 'senior-warrant-officer', 'troop_name' => 'старший прапорщик', 'naval_name' => 'старший мичман', 'sort_order' => 8, 'composition_code' => 'warrant-officers'],
        ['code' => 'junior-lieutenant', 'troop_name' => 'младший лейтенант', 'naval_name' => 'младший лейтенант', 'sort_order' => 9, 'composition_code' => 'junior-officers'],
        ['code' => 'lieutenant', 'troop_name' => 'лейтенант', 'naval_name' => 'лейтенант', 'sort_order' => 10, 'composition_code' => 'junior-officers'],
        ['code' => 'senior-lieutenant', 'troop_name' => 'старший лейтенант', 'naval_name' => 'старший лейтенант', 'sort_order' => 11, 'composition_code' => 'junior-officers'],
        ['code' => 'captain', 'troop_name' => 'капитан', 'naval_name' => 'капитан-лейтенант', 'sort_order' => 12, 'composition_code' => 'junior-officers'],
        ['code' => 'major', 'troop_name' => 'майор', 'naval_name' => 'капитан 3 ранга', 'sort_order' => 13, 'composition_code' => 'senior-officers'],
        ['code' => 'lieutenant-colonel', 'troop_name' => 'подполковник', 'naval_name' => 'капитан 2 ранга', 'sort_order' => 14, 'composition_code' => 'senior-officers'],
        ['code' => 'colonel', 'troop_name' => 'полковник', 'naval_name' => 'капитан 1 ранга', 'sort_order' => 15, 'composition_code' => 'senior-officers'],
        ['code' => 'major-general', 'troop_name' => 'генерал-майор', 'naval_name' => 'контр-адмирал', 'sort_order' => 16, 'composition_code' => 'higher-officers'],
        ['code' => 'lieutenant-general', 'troop_name' => 'генерал-лейтенант', 'naval_name' => 'вице-адмирал', 'sort_order' => 17, 'composition_code' => 'higher-officers'],
        ['code' => 'colonel-general', 'troop_name' => 'генерал-полковник', 'naval_name' => 'адмирал', 'sort_order' => 18, 'composition_code' => 'higher-officers'],
        ['code' => 'army-general', 'troop_name' => 'генерал армии', 'naval_name' => 'адмирал флота', 'sort_order' => 19, 'composition_code' => 'higher-officers'],
        ['code' => 'marshal-russian-federation', 'troop_name' => 'Маршал Российской Федерации', 'naval_name' => null, 'sort_order' => 20, 'composition_code' => 'higher-officers'],
    ];
}

function military_rank_v2_table_exists(PDO $pdo, string $schemaName, string $tableName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name'
    );
    $stmt->execute(['schema_name' => $schemaName, 'table_name' => $tableName]);
    return (int) $stmt->fetchColumn() === 1;
}

function military_rank_v2_column_exists(
    PDO $pdo,
    string $schemaName,
    string $tableName,
    string $columnName
): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name AND column_name = :column_name'
    );
    $stmt->execute([
        'schema_name' => $schemaName,
        'table_name' => $tableName,
        'column_name' => $columnName,
    ]);
    return (int) $stmt->fetchColumn() === 1;
}

function military_rank_v2_index_exists(
    PDO $pdo,
    string $schemaName,
    string $tableName,
    string $indexName
): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name AND index_name = :index_name'
    );
    $stmt->execute([
        'schema_name' => $schemaName,
        'table_name' => $tableName,
        'index_name' => $indexName,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function military_rank_v2_constraint_exists(
    PDO $pdo,
    string $schemaName,
    string $tableName,
    string $constraintName
): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.table_constraints '
        . 'WHERE constraint_schema = :schema_name AND table_name = :table_name '
        . 'AND constraint_name = :constraint_name'
    );
    $stmt->execute([
        'schema_name' => $schemaName,
        'table_name' => $tableName,
        'constraint_name' => $constraintName,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

/** @return array<string,mixed>|null */

function military_rank_v2_version_by_code(PDO $pdo, string $code, bool $withLifecycle): ?array
{
    $columns = $withLifecycle
        ? 'id, code, name, is_current, lifecycle_status, valid_from, valid_to, verified_at, published_at, superseded_at, created_at'
        : 'id, code, name, is_current, valid_from, valid_to, verified_at, created_at';
    $stmt = $pdo->prepare(
        "SELECT {$columns} FROM military_rank_catalog_versions WHERE code = :code LIMIT 1"
    );
    $stmt->execute(['code' => $code]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}
