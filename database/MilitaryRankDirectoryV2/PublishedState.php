<?php

declare(strict_types=1);

function military_rank_v2_assert_published_state(PDO $pdo): void
{
    $versionStmt = $pdo->prepare(
        'SELECT id, lifecycle_status, is_current, valid_from, valid_to, verified_at, published_at, superseded_at '
        . 'FROM military_rank_catalog_versions WHERE code = :code LIMIT 1'
    );
    $versionStmt->execute(['code' => MILITARY_RANK_V2_CODE]);
    $v2 = $versionStmt->fetch();
    if (!is_array($v2)
        || $v2['lifecycle_status'] !== 'published'
        || (int) $v2['is_current'] !== 1
        || $v2['valid_from'] !== '2026-08-03'
        || $v2['verified_at'] !== '2026-08-02'
        || $v2['valid_to'] !== null
        || $v2['published_at'] === null) {
        throw new RuntimeException('Migration 012 recovery: опубликованная версия v2 не совпадает с anchors.');
    }
    $v2Id = (int) $v2['id'];

    $v1Stmt = $pdo->prepare(
        'SELECT lifecycle_status, is_current, valid_to, published_at, superseded_at '
        . 'FROM military_rank_catalog_versions WHERE code = :code LIMIT 1'
    );
    $v1Stmt->execute(['code' => MILITARY_RANK_V1_CODE]);
    $v1 = $v1Stmt->fetch();
    if (!is_array($v1)
        || $v1['lifecycle_status'] !== 'superseded'
        || (int) $v1['is_current'] !== 0
        || $v1['valid_to'] !== '2026-08-02'
        || $v1['published_at'] === null
        || $v1['superseded_at'] === null) {
        throw new RuntimeException('Migration 012 recovery: версия v1 не находится в ожидаемом superseded-состоянии.');
    }

    $counts = [
        'military_personnel_compositions' => 8,
        'military_personnel_composition_semantics' => 8,
        'military_rank_levels' => 20,
        'military_rank_catalog_version_sources' => 2,
        'military_personnel_composition_sources' => 8,
    ];
    foreach ($counts as $table => $expectedCount) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE catalog_version_id = :version_id");
        $stmt->execute(['version_id' => $v2Id]);
        if ((int) $stmt->fetchColumn() !== $expectedCount) {
            throw new RuntimeException("Migration 012 recovery: таблица {$table} не совпадает с anchors.");
        }
    }

    $compositionStmt = $pdo->prepare(
        'SELECT c.id, c.code, c.name, c.sort_order, p.code AS parent_code '
        . 'FROM military_personnel_compositions c '
        . 'LEFT JOIN military_personnel_compositions p '
        . 'ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id '
        . 'WHERE c.catalog_version_id = :version_id ORDER BY c.sort_order, c.id'
    );
    $compositionStmt->execute(['version_id' => $v2Id]);
    $compositionRows = $compositionStmt->fetchAll();
    $expectedCompositions = military_rank_v2_expected_compositions();
    if (count($compositionRows) !== count($expectedCompositions)) {
        throw new RuntimeException('Migration 012 recovery: composition count v2 не совпадает.');
    }
    $compositionIdsByCode = [];
    foreach ($expectedCompositions as $index => $expectedComposition) {
        $actual = $compositionRows[$index] ?? null;
        if (!is_array($actual)
            || (string) $actual['code'] !== $expectedComposition['code']
            || (string) $actual['name'] !== $expectedComposition['name']
            || ($actual['parent_code'] !== null ? (string) $actual['parent_code'] : null) !== $expectedComposition['parent_code']
            || (int) $actual['sort_order'] !== $expectedComposition['sort_order']) {
            throw new RuntimeException('Migration 012 recovery: composition anchors v2 не совпадают.');
        }
        $compositionIdsByCode[$expectedComposition['code']] = (int) $actual['id'];
    }

    $semanticsStmt = $pdo->prepare(
        'SELECT c.code, s.classification_kind, s.is_staffing_selectable, s.derivation_note '
        . 'FROM military_personnel_composition_semantics s '
        . 'JOIN military_personnel_compositions c '
        . 'ON c.id = s.composition_id AND c.catalog_version_id = s.catalog_version_id '
        . 'WHERE s.catalog_version_id = :version_id ORDER BY c.sort_order, c.id'
    );
    $semanticsStmt->execute(['version_id' => $v2Id]);
    $semanticsRows = $semanticsStmt->fetchAll();
    if (count($semanticsRows) !== 8) {
        throw new RuntimeException('Migration 012 recovery: semantics count v2 не совпадает.');
    }
    $selectableCodes = ['soldiers-and-sailors', 'sergeants-and-starshinas', 'warrant-officers', 'officers'];
    $derivedCodes = ['soldiers-and-sailors', 'sergeants-and-starshinas'];
    foreach ($semanticsRows as $row) {
        $code = (string) $row['code'];
        $expectedKind = in_array($code, $derivedCodes, true)
            ? 'derived-staffing-scope'
            : 'normative-composition';
        if (!isset($compositionIdsByCode[$code])
            || (string) $row['classification_kind'] !== $expectedKind
            || (int) $row['is_staffing_selectable'] !== (int) in_array($code, $selectableCodes, true)
            || ($expectedKind === 'derived-staffing-scope'
                && (!is_string($row['derivation_note']) || trim($row['derivation_note']) === ''))) {
            throw new RuntimeException('Migration 012 recovery: semantics anchors v2 не совпадают.');
        }
    }

    $versionSourceStmt = $pdo->prepare(
        'SELECT s.code, vs.source_role, vs.sort_order '
        . 'FROM military_rank_catalog_version_sources vs '
        . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
        . 'WHERE vs.catalog_version_id = :version_id ORDER BY vs.sort_order'
    );
    $versionSourceStmt->execute(['version_id' => $v2Id]);
    $versionSourceRows = $versionSourceStmt->fetchAll();
    $expectedVersionSources = [
        ['code' => 'federal-law-53-fz-article-46', 'source_role' => 'primary-list', 'sort_order' => 1],
        ['code' => 'presidential-decree-1237-article-20', 'source_role' => 'equivalence-and-order', 'sort_order' => 2],
    ];
    if (count($versionSourceRows) !== count($expectedVersionSources)) {
        throw new RuntimeException('Migration 012 recovery: version source count v2 не совпадает.');
    }
    foreach ($expectedVersionSources as $index => $expectedSource) {
        $actual = $versionSourceRows[$index] ?? null;
        if (!is_array($actual)
            || (string) $actual['code'] !== $expectedSource['code']
            || (string) $actual['source_role'] !== $expectedSource['source_role']
            || (int) $actual['sort_order'] !== $expectedSource['sort_order']) {
            throw new RuntimeException('Migration 012 recovery: version source anchors v2 не совпадают.');
        }
    }

    $compositionSourceStmt = $pdo->prepare(
        'SELECT c.code AS composition_code, s.code AS source_code, cs.source_role, cs.sort_order, cs.note '
        . 'FROM military_personnel_composition_sources cs '
        . 'JOIN military_personnel_compositions c '
        . 'ON c.id = cs.composition_id AND c.catalog_version_id = cs.catalog_version_id '
        . 'JOIN legal_sources s ON s.id = cs.legal_source_id '
        . 'WHERE cs.catalog_version_id = :version_id ORDER BY c.sort_order, c.id'
    );
    $compositionSourceStmt->execute(['version_id' => $v2Id]);
    $compositionSourceRows = $compositionSourceStmt->fetchAll();
    if (count($compositionSourceRows) !== 8) {
        throw new RuntimeException('Migration 012 recovery: composition source count v2 не совпадает.');
    }
    foreach ($compositionSourceRows as $row) {
        $code = (string) $row['composition_code'];
        $isDerived = in_array($code, $derivedCodes, true);
        $isOfficerChild = in_array($code, ['junior-officers', 'senior-officers', 'higher-officers'], true);
        $expectedSourceCode = $isOfficerChild
            ? 'presidential-decree-1237-article-20'
            : 'federal-law-53-fz-article-46';
        $expectedRole = $isDerived
            ? 'derived-classification-basis'
            : ($isOfficerChild ? 'rank-list' : 'normative-definition');
        if (!isset($compositionIdsByCode[$code])
            || (string) $row['source_code'] !== $expectedSourceCode
            || (string) $row['source_role'] !== $expectedRole
            || (int) $row['sort_order'] !== 1
            || ($isDerived && (!is_string($row['note']) || trim($row['note']) === ''))) {
            throw new RuntimeException('Migration 012 recovery: composition source anchors v2 не совпадают.');
        }
    }

    $sourceVerificationStmt = $pdo->query(
        "SELECT COUNT(*) FROM legal_sources WHERE code IN ("
        . "'federal-law-53-fz-article-46', 'presidential-decree-1237-article-20'"
        . ") AND verified_at = '2026-08-02'"
    );
    if ((int) $sourceVerificationStmt->fetchColumn() !== 2) {
        throw new RuntimeException('Migration 012 recovery: даты проверки официальных источников не совпадают.');
    }

    $selectableStmt = $pdo->prepare(
        'SELECT c.code FROM military_personnel_composition_semantics s '
        . 'JOIN military_personnel_compositions c '
        . 'ON c.id = s.composition_id AND c.catalog_version_id = s.catalog_version_id '
        . 'WHERE s.catalog_version_id = :version_id AND s.is_staffing_selectable = 1 ORDER BY c.code'
    );
    $selectableStmt->execute(['version_id' => $v2Id]);
    $selectable = $selectableStmt->fetchAll(PDO::FETCH_COLUMN);
    $expectedSelectable = ['officers', 'sergeants-and-starshinas', 'soldiers-and-sailors', 'warrant-officers'];
    if ($selectable !== $expectedSelectable) {
        throw new RuntimeException('Migration 012 recovery: selectable compositions v2 не совпадают.');
    }

    $rankStmt = $pdo->prepare(
        'SELECT r.code, r.troop_name, r.naval_name, r.sort_order, c.code AS composition_code '
        . 'FROM military_rank_levels r JOIN military_personnel_compositions c '
        . 'ON c.id = r.composition_id AND c.catalog_version_id = r.catalog_version_id '
        . 'WHERE r.catalog_version_id = :version_id ORDER BY r.sort_order, r.id'
    );
    $rankStmt->execute(['version_id' => $v2Id]);
    $rankRows = $rankStmt->fetchAll();
    $expectedRanks = military_rank_v2_expected_levels();
    if (count($rankRows) !== count($expectedRanks)) {
        throw new RuntimeException('Migration 012 recovery: rank level count v2 не совпадает.');
    }
    foreach ($expectedRanks as $index => $expectedRank) {
        $actual = $rankRows[$index] ?? null;
        if (!is_array($actual)
            || (string) $actual['code'] !== $expectedRank['code']
            || (string) $actual['troop_name'] !== $expectedRank['troop_name']
            || ($actual['naval_name'] !== null ? (string) $actual['naval_name'] : null) !== $expectedRank['naval_name']
            || (int) $actual['sort_order'] !== $expectedRank['sort_order']
            || (string) $actual['composition_code'] !== $expectedRank['composition_code']) {
            throw new RuntimeException('Migration 012 recovery: rank anchors v2 не совпадают.');
        }
    }
}
