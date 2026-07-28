<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/database/OrganizationalStructureMigrationCompatibility.php';

$migrationPath = $root . '/database/migrations/009_organizational_structure_v1.sql';
$sql = file_get_contents($migrationPath);
if ($sql === false) {
    fwrite(STDERR, "FAIL: migration 009 не прочитана.\n");
    exit(1);
}

try {
    $prepared = transform_organizational_structure_migration_sql($sql);
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

$checks = [
    'таблиц после подготовки: 7' => preg_match_all('/^\s*CREATE\s+TABLE\b/im', $prepared) === 7,
    'triggers после подготовки: 16' => preg_match_all('/^\s*CREATE\s+TRIGGER\b/im', $prepared) === 16,
    'unsupported auto-increment CHECK удалён' => !str_contains($prepared, 'chk_org_structure_nodes_self_parent'),
    'self-parent защищён двумя trigger-проверками' => substr_count(
        $prepared,
        'ORG_STRUCTURE_NODE_SELF_PARENT_FORBIDDEN'
    ) === 2,
    'DELIMITER отсутствует' => preg_match('/^\s*DELIMITER\b/im', $prepared) !== 1,
];

$failed = 0;
foreach ($checks as $label => $passed) {
    if ($passed) {
        echo "PASS: {$label}\n";
    } else {
        fwrite(STDERR, "FAIL: {$label}\n");
        $failed++;
    }
}

if ($failed !== 0) {
    exit(1);
}

echo "ORGANIZATIONAL STRUCTURE MIGRATION COMPATIBILITY CHECK PASSED\n";
exit(0);
