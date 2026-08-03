<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/database/MilitaryRankDirectoryV2MigrationCompatibility.php';

function loader_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo 'OK ' . $message . PHP_EOL;
}

try {
    $triggers = military_rank_v2_trigger_sql();
    $publication = military_rank_v2_publication_sql();
    $recovery = file_get_contents($root . '/database/MilitaryRankDirectoryV2/Recovery.php');
    loader_check(is_string($recovery), 'recovery source readable');

    foreach ([
        'trg_military_rank_catalog_versions_before_update',
        'MILITARY_RANK_V2_PUBLICATION_INCOMPLETE',
        'trg_military_composition_semantics_before_insert',
        'trg_military_composition_sources_before_delete',
    ] as $anchor) {
        loader_check(str_contains($triggers, $anchor), "trigger anchor exists: {$anchor}");
    }

    foreach ([
        'START TRANSACTION;',
        'rf-military-ranks-staffing-scopes-v2',
        'soldiers-and-sailors',
        'sergeants-and-starshinas',
        "lifecycle_status = 'superseded'",
        "lifecycle_status = 'published'",
        'COMMIT;',
    ] as $anchor) {
        loader_check(str_contains($publication, $anchor), "publication anchor exists: {$anchor}");
    }

    loader_check(count(military_rank_v1_expected_compositions()) === 6, 'v1 composition anchors: 6');
    loader_check(count(military_rank_v2_expected_compositions()) === 8, 'v2 composition anchors: 8');
    loader_check(count(military_rank_v2_expected_levels()) === 20, 'v2 rank anchors: 20');
    loader_check(count(military_rank_v2_expected_version_sources()) === 2, 'v2 version source anchors: 2');
    loader_check(count(military_rank_v2_expected_composition_sources()) === 8, 'v2 composition source anchors: 8');

    loader_check(
        military_rank_v2_version_source_matches_expected(
            'federal-law-53-fz-article-46',
            'primary-list',
            1
        ),
        'exact primary version source accepted'
    );
    loader_check(
        military_rank_v2_version_source_matches_expected(
            'presidential-decree-1237-article-20',
            'equivalence-and-order',
            2
        ),
        'exact equivalence version source accepted'
    );
    loader_check(
        !military_rank_v2_version_source_matches_expected(
            'federal-law-53-fz-article-46',
            'equivalence-and-order',
            2
        ),
        'contradictory version source rejected'
    );
    loader_check(
        !military_rank_v2_version_source_matches_expected(
            'presidential-decree-1237-article-20',
            'equivalence-and-order',
            1
        ),
        'wrong version source order rejected'
    );

    $derivedNote = 'Источник перечисляет уровни внутри общего нормативного состава; разделение является прикладной классификацией АСУ-ВЧ.';
    loader_check(
        military_rank_v2_composition_source_matches_expected(
            'soldiers-and-sailors',
            'federal-law-53-fz-article-46',
            'derived-classification-basis',
            1,
            $derivedNote
        ),
        'exact derived composition source accepted'
    );
    loader_check(
        military_rank_v2_composition_source_matches_expected(
            'junior-officers',
            'presidential-decree-1237-article-20',
            'rank-list',
            1,
            null
        ),
        'exact officer composition source accepted'
    );
    loader_check(
        !military_rank_v2_composition_source_matches_expected(
            'soldiers-and-sailors',
            'presidential-decree-1237-article-20',
            'rank-list',
            1,
            null
        ),
        'contradictory composition/source pairing rejected'
    );
    loader_check(
        !military_rank_v2_composition_source_matches_expected(
            'soldiers-and-sailors',
            'federal-law-53-fz-article-46',
            'derived-classification-basis',
            1,
            'Изменённое основание'
        ),
        'contradictory derived note rejected'
    );
    loader_check(
        !military_rank_v2_composition_source_matches_expected(
            'officers',
            'federal-law-53-fz-article-46',
            'rank-list',
            1,
            null
        ),
        'contradictory composition source role rejected'
    );

    loader_check(
        str_contains($recovery, 'military_rank_v2_version_source_matches_expected('),
        'recovery uses exact version source matcher'
    );
    loader_check(
        str_contains($recovery, 'military_rank_v2_composition_source_matches_expected('),
        'recovery uses exact composition source matcher'
    );

    echo "MILITARY RANK V2 LOADER CHECK PASSED\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'MILITARY RANK V2 LOADER CHECK FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
