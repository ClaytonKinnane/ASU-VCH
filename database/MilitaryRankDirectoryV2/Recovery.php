<?php

declare(strict_types=1);

function military_rank_v2_assert_recoverable_building_state(PDO $pdo, int $versionId): void
{
    $versionStmt = $pdo->prepare(
        'SELECT code, name, lifecycle_status, is_current, valid_from, valid_to, verified_at, published_at, superseded_at '
        . 'FROM military_rank_catalog_versions WHERE id = :version_id LIMIT 1'
    );
    $versionStmt->execute(['version_id' => $versionId]);
    $version = $versionStmt->fetch();
    $expectedName = 'Составы военнослужащих и воинские звания Российской Федерации — версия с категориями для штатных должностей';
    if (!is_array($version)
        || $version['code'] !== MILITARY_RANK_V2_CODE
        || $version['name'] !== $expectedName
        || $version['lifecycle_status'] !== 'building'
        || (int) $version['is_current'] !== 0
        || $version['valid_from'] !== '2026-08-03'
        || $version['verified_at'] !== '2026-08-02'
        || $version['valid_to'] !== null
        || $version['published_at'] !== null
        || $version['superseded_at'] !== null) {
        throw new RuntimeException('Migration 012 recovery: building v2 имеет противоречивые metadata.');
    }

    $expectedCompositions = [];
    foreach (military_rank_v2_expected_compositions() as $row) {
        $expectedCompositions[$row['code']] = $row;
    }
    $compositionStmt = $pdo->prepare(
        'SELECT c.id, c.code, c.name, c.sort_order, p.code AS parent_code '
        . 'FROM military_personnel_compositions c '
        . 'LEFT JOIN military_personnel_compositions p '
        . 'ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id '
        . 'WHERE c.catalog_version_id = :version_id ORDER BY c.sort_order, c.id'
    );
    $compositionStmt->execute(['version_id' => $versionId]);
    $compositionCodesById = [];
    foreach ($compositionStmt->fetchAll() as $row) {
        $code = (string) $row['code'];
        $expected = $expectedCompositions[$code] ?? null;
        if (!is_array($expected)
            || (string) $row['name'] !== $expected['name']
            || ($row['parent_code'] !== null ? (string) $row['parent_code'] : null) !== $expected['parent_code']
            || (int) $row['sort_order'] !== $expected['sort_order']) {
            throw new RuntimeException('Migration 012 recovery: building v2 содержит неожиданный состав.');
        }
        $compositionCodesById[(int) $row['id']] = $code;
    }

    $expectedRanks = [];
    foreach (military_rank_v2_expected_levels() as $row) {
        $expectedRanks[$row['code']] = $row;
    }
    $rankStmt = $pdo->prepare(
        'SELECT code, troop_name, naval_name, sort_order, composition_id '
        . 'FROM military_rank_levels WHERE catalog_version_id = :version_id ORDER BY sort_order, id'
    );
    $rankStmt->execute(['version_id' => $versionId]);
    foreach ($rankStmt->fetchAll() as $row) {
        $code = (string) $row['code'];
        $expected = $expectedRanks[$code] ?? null;
        $compositionCode = $compositionCodesById[(int) $row['composition_id']] ?? null;
        if (!is_array($expected)
            || (string) $row['troop_name'] !== $expected['troop_name']
            || ($row['naval_name'] !== null ? (string) $row['naval_name'] : null) !== $expected['naval_name']
            || (int) $row['sort_order'] !== $expected['sort_order']
            || $compositionCode !== $expected['composition_code']) {
            throw new RuntimeException('Migration 012 recovery: building v2 содержит неожиданный rank level.');
        }
    }

    $semanticsStmt = $pdo->prepare(
        'SELECT composition_id, classification_kind, is_staffing_selectable, derivation_note '
        . 'FROM military_personnel_composition_semantics WHERE catalog_version_id = :version_id'
    );
    $semanticsStmt->execute(['version_id' => $versionId]);
    $selectableCodes = ['soldiers-and-sailors', 'sergeants-and-starshinas', 'warrant-officers', 'officers'];
    $derivedCodes = ['soldiers-and-sailors', 'sergeants-and-starshinas'];
    foreach ($semanticsStmt->fetchAll() as $row) {
        $code = $compositionCodesById[(int) $row['composition_id']] ?? null;
        if ($code === null) {
            throw new RuntimeException('Migration 012 recovery: semantics ссылается на неизвестный состав.');
        }
        $expectedKind = in_array($code, $derivedCodes, true)
            ? 'derived-staffing-scope'
            : 'normative-composition';
        if ((string) $row['classification_kind'] !== $expectedKind
            || (int) $row['is_staffing_selectable'] !== (int) in_array($code, $selectableCodes, true)
            || ($expectedKind === 'derived-staffing-scope'
                && (!is_string($row['derivation_note']) || trim($row['derivation_note']) === ''))) {
            throw new RuntimeException('Migration 012 recovery: building v2 содержит неожиданные semantics.');
        }
    }

    $legalCodes = ['federal-law-53-fz-article-46', 'presidential-decree-1237-article-20'];
    $sourceStmt = $pdo->prepare(
        'SELECT s.code, vs.source_role, vs.sort_order '
        . 'FROM military_rank_catalog_version_sources vs '
        . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
        . 'WHERE vs.catalog_version_id = :version_id'
    );
    $sourceStmt->execute(['version_id' => $versionId]);
    foreach ($sourceStmt->fetchAll() as $row) {
        if (!in_array((string) $row['code'], $legalCodes, true)
            || !in_array((string) $row['source_role'], ['primary-list', 'equivalence-and-order'], true)
            || (int) $row['sort_order'] < 1 || (int) $row['sort_order'] > 2) {
            throw new RuntimeException('Migration 012 recovery: building v2 содержит неожиданный version source.');
        }
    }

    $compositionSourceStmt = $pdo->prepare(
        'SELECT cs.composition_id, s.code, cs.source_role, cs.sort_order, cs.note '
        . 'FROM military_personnel_composition_sources cs '
        . 'JOIN legal_sources s ON s.id = cs.legal_source_id '
        . 'WHERE cs.catalog_version_id = :version_id'
    );
    $compositionSourceStmt->execute(['version_id' => $versionId]);
    foreach ($compositionSourceStmt->fetchAll() as $row) {
        $compositionCode = $compositionCodesById[(int) $row['composition_id']] ?? null;
        if ($compositionCode === null
            || !in_array((string) $row['code'], $legalCodes, true)
            || !in_array((string) $row['source_role'], ['normative-definition', 'rank-list', 'derived-classification-basis'], true)
            || (int) $row['sort_order'] !== 1
            || ((string) $row['source_role'] === 'derived-classification-basis'
                && (!is_string($row['note']) || trim($row['note']) === ''))) {
            throw new RuntimeException('Migration 012 recovery: building v2 содержит неожиданный composition source.');
        }
    }
}

/** @return list<string> */
