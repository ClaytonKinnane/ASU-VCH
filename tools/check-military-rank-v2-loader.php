<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/database/MilitaryRankDirectoryV2MigrationCompatibility.php';

function loader_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $triggers = military_rank_v2_trigger_sql();
    $publication = military_rank_v2_publication_sql();

    foreach ([
        'trg_military_rank_catalog_versions_before_update',
        'MILITARY_RANK_V2_PUBLICATION_INCOMPLETE',
        'trg_military_composition_semantics_before_insert',
        'trg_military_composition_sources_before_delete',
    ] as $anchor) {
        loader_check(str_contains($triggers, $anchor), "Не найден trigger anchor: {$anchor}");
    }

    foreach ([
        'START TRANSACTION;',
        "rf-military-ranks-staffing-scopes-v2",
        'soldiers-and-sailors',
        'sergeants-and-starshinas',
        "lifecycle_status = 'superseded'",
        "lifecycle_status = 'published'",
        'COMMIT;',
    ] as $anchor) {
        loader_check(str_contains($publication, $anchor), "Не найден publication anchor: {$anchor}");
    }

    loader_check(count(military_rank_v1_expected_compositions()) === 6, 'Ожидалось 6 composition anchors v1.');
    loader_check(count(military_rank_v2_expected_compositions()) === 8, 'Ожидалось 8 composition anchors v2.');
    loader_check(count(military_rank_v2_expected_levels()) === 20, 'Ожидалось 20 rank anchors v2.');

    echo "MILITARY RANK V2 LOADER CHECK PASSED\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'MILITARY RANK V2 LOADER CHECK FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
