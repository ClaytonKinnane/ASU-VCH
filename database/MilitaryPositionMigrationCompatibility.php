<?php

declare(strict_types=1);

function load_military_position_migration_sql(string $migrationDirectory): string
{
    $encoded = '';
    for ($index = 0; $index <= 4; $index++) {
        $part = sprintf(
            '%s/010_military_positions_directory.sql.gz.b64.part%02d',
            rtrim($migrationDirectory, '/\\'),
            $index
        );
        if (!is_file($part)) {
            throw new RuntimeException("Не найдена часть архива migration 010: {$part}");
        }
        $contents = file_get_contents($part);
        if (!is_string($contents)) {
            throw new RuntimeException("Не удалось прочитать часть архива migration 010: {$part}");
        }
        $encoded .= $contents;
    }

    $compressed = base64_decode($encoded, true);
    if (!is_string($compressed)) {
        throw new RuntimeException('Не удалось декодировать архив migration 010.');
    }
    $expectedArchiveHash = 'af617b754e4a8a5b453d6856f5c20540edb72d839fb162e61f9c160493c6fb82';
    $actualArchiveHash = hash('sha256', $compressed);
    if (!hash_equals($expectedArchiveHash, $actualArchiveHash)) {
        throw new RuntimeException("SHA-256 архива migration 010 не совпадает: {$actualArchiveHash}");
    }

    $sql = gzdecode($compressed);
    if (!is_string($sql)) {
        throw new RuntimeException('Не удалось распаковать canonical SQL migration 010.');
    }
    $expectedSqlHash = '3ebb00dc2d89027eea7f3619deb29adfdcdea7b67b9a221b4ab0cd159d96ac78';
    $actualSqlHash = hash('sha256', $sql);
    if (!hash_equals($expectedSqlHash, $actualSqlHash)) {
        throw new RuntimeException("SHA-256 migration 010 не совпадает: {$actualSqlHash}");
    }

    return $sql;
}
