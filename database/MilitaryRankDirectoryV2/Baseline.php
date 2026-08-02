<?php

declare(strict_types=1);

function military_rank_v2_assert_v1_baseline(PDO $pdo): int
{
    $versionStmt = $pdo->prepare(
        'SELECT id, is_current, valid_from, valid_to, verified_at '
        . 'FROM military_rank_catalog_versions WHERE code = :code LIMIT 1'
    );
    $versionStmt->execute(['code' => MILITARY_RANK_V1_CODE]);
    $version = $versionStmt->fetch();
    if (!is_array($version)) {
        throw new RuntimeException('Migration 012 preflight: не найдена ожидаемая версия v1.');
    }

    $versionId = (int) $version['id'];

    $compositionStmt = $pdo->prepare(
        'SELECT c.code, c.name, c.sort_order, p.code AS parent_code '
        . 'FROM military_personnel_compositions c '
        . 'LEFT JOIN military_personnel_compositions p '
        . 'ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id '
        . 'WHERE c.catalog_version_id = :version_id ORDER BY c.sort_order, c.id'
    );
    $compositionStmt->execute(['version_id' => $versionId]);
    $compositions = $compositionStmt->fetchAll();
    $expectedV1Compositions = military_rank_v1_expected_compositions();
    if (count($compositions) !== count($expectedV1Compositions)) {
        throw new RuntimeException('Migration 012 preflight: версия v1 должна содержать 6 составов.');
    }
    foreach ($expectedV1Compositions as $index => $expectedComposition) {
        $actual = $compositions[$index] ?? null;
        if (!is_array($actual)
            || (string) $actual['code'] !== $expectedComposition['code']
            || (string) $actual['name'] !== $expectedComposition['name']
            || ($actual['parent_code'] !== null ? (string) $actual['parent_code'] : null) !== $expectedComposition['parent_code']
            || (int) $actual['sort_order'] !== $expectedComposition['sort_order']) {
            throw new RuntimeException('Migration 012 preflight: anchors составов v1 не совпадают.');
        }
    }

    $rankStmt = $pdo->prepare(
        'SELECT code, troop_name, naval_name, sort_order FROM military_rank_levels '
        . 'WHERE catalog_version_id = :version_id ORDER BY sort_order, id'
    );
    $rankStmt->execute(['version_id' => $versionId]);
    $ranks = $rankStmt->fetchAll();
    $expected = military_rank_v2_expected_levels();
    if (count($ranks) !== count($expected)) {
        throw new RuntimeException('Migration 012 preflight: версия v1 должна содержать 20 уровней званий.');
    }

    foreach ($expected as $index => $expectedRank) {
        $actual = $ranks[$index] ?? null;
        if (!is_array($actual)
            || (string) $actual['code'] !== $expectedRank['code']
            || (string) $actual['troop_name'] !== $expectedRank['troop_name']
            || ($actual['naval_name'] !== null ? (string) $actual['naval_name'] : null) !== $expectedRank['naval_name']
            || (int) $actual['sort_order'] !== $expectedRank['sort_order']) {
            throw new RuntimeException('Migration 012 preflight: anchors уровней званий v1 не совпадают.');
        }
    }

    $sourceStmt = $pdo->prepare(
        'SELECT s.code, vs.source_role, vs.sort_order '
        . 'FROM military_rank_catalog_version_sources vs '
        . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
        . 'WHERE vs.catalog_version_id = :version_id ORDER BY vs.sort_order'
    );
    $sourceStmt->execute(['version_id' => $versionId]);
    $sourceRows = $sourceStmt->fetchAll();
    $expectedSources = [
        ['code' => 'federal-law-53-fz-article-46', 'source_role' => 'primary-list', 'sort_order' => 1],
        ['code' => 'presidential-decree-1237-article-20', 'source_role' => 'equivalence-and-order', 'sort_order' => 2],
    ];
    if (count($sourceRows) !== count($expectedSources)) {
        throw new RuntimeException('Migration 012 preflight: количество нормативных источников v1 не совпадает.');
    }
    foreach ($expectedSources as $index => $expectedSource) {
        $actualSource = $sourceRows[$index] ?? null;
        if (!is_array($actualSource)
            || (string) $actualSource['code'] !== $expectedSource['code']
            || (string) $actualSource['source_role'] !== $expectedSource['source_role']
            || (int) $actualSource['sort_order'] !== $expectedSource['sort_order']) {
            throw new RuntimeException('Migration 012 preflight: нормативные источники v1 не совпадают с anchors.');
        }
    }

    return $versionId;
}
