<?php

declare(strict_types=1);

function load_military_occupational_specialty_migration_sql(string $migrationDirectory): string
{
    $encoded = '';
    for ($index = 0; $index <= 1; $index++) {
        $part = sprintf(
            '%s/011_public_military_occupational_specialties_directory.sql.gz.b64.part%02d',
            rtrim($migrationDirectory, '/\\'),
            $index
        );
        if (!is_file($part)) {
            throw new RuntimeException("Не найдена часть архива migration 011: {$part}");
        }
        $contents = file_get_contents($part);
        if (!is_string($contents)) {
            throw new RuntimeException("Не удалось прочитать часть архива migration 011: {$part}");
        }
        $encoded .= trim($contents);
    }

    $compressed = base64_decode($encoded, true);
    if (!is_string($compressed)) {
        throw new RuntimeException('Не удалось декодировать архив migration 011.');
    }
    $expectedArchiveHash = '1c1af1e07e040452499e5882ce181b088c4017c936b0892d2552e8447996bc39';
    $actualArchiveHash = hash('sha256', $compressed);
    if (!hash_equals($expectedArchiveHash, $actualArchiveHash)) {
        throw new RuntimeException("SHA-256 архива migration 011 не совпадает: {$actualArchiveHash}");
    }

    $sql = gzdecode($compressed);
    if (!is_string($sql)) {
        throw new RuntimeException('Не удалось распаковать canonical SQL migration 011.');
    }
    $expectedSqlHash = '26039aedc4c700a883203eeaefd09194cc6a9a304b3c2db94a7479f8710b8fd9';
    $actualSqlHash = hash('sha256', $sql);
    if (!hash_equals($expectedSqlHash, $actualSqlHash)) {
        throw new RuntimeException("SHA-256 migration 011 не совпадает: {$actualSqlHash}");
    }

    return $sql;
}
