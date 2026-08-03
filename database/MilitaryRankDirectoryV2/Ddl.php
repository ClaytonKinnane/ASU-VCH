<?php

declare(strict_types=1);

function military_rank_v2_ddl(PDO $pdo, string $schemaName): array
{
    $ddl = [];

    if (!military_rank_v2_column_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'lifecycle_status')) {
        $ddl[] = "ALTER TABLE military_rank_catalog_versions ADD COLUMN lifecycle_status VARCHAR(20) NOT NULL DEFAULT 'published' AFTER is_current";
    }
    if (!military_rank_v2_column_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'published_at')) {
        $ddl[] = 'ALTER TABLE military_rank_catalog_versions ADD COLUMN published_at DATETIME NULL AFTER verified_at';
    }
    if (!military_rank_v2_column_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'superseded_at')) {
        $ddl[] = 'ALTER TABLE military_rank_catalog_versions ADD COLUMN superseded_at DATETIME NULL AFTER published_at';
    }
    if (!military_rank_v2_column_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'current_guard')) {
        $ddl[] = "ALTER TABLE military_rank_catalog_versions ADD COLUMN current_guard TINYINT "
            . "GENERATED ALWAYS AS (CASE WHEN lifecycle_status = 'published' AND is_current = 1 THEN 1 ELSE NULL END) STORED";
    }
    if (!military_rank_v2_column_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'building_guard')) {
        $ddl[] = "ALTER TABLE military_rank_catalog_versions ADD COLUMN building_guard TINYINT "
            . "GENERATED ALWAYS AS (CASE WHEN lifecycle_status = 'building' THEN 1 ELSE NULL END) STORED";
    }
    if (!military_rank_v2_index_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'uq_military_rank_catalog_current_guard')) {
        $ddl[] = 'ALTER TABLE military_rank_catalog_versions ADD UNIQUE KEY uq_military_rank_catalog_current_guard (current_guard)';
    }
    if (!military_rank_v2_index_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'uq_military_rank_catalog_building_guard')) {
        $ddl[] = 'ALTER TABLE military_rank_catalog_versions ADD UNIQUE KEY uq_military_rank_catalog_building_guard (building_guard)';
    }
    if (!military_rank_v2_constraint_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'chk_military_rank_catalog_lifecycle_status')) {
        $ddl[] = "ALTER TABLE military_rank_catalog_versions ADD CONSTRAINT chk_military_rank_catalog_lifecycle_status "
            . "CHECK (lifecycle_status IN ('building', 'published', 'superseded'))";
    }
    if (!military_rank_v2_constraint_exists($pdo, $schemaName, 'military_rank_catalog_versions', 'chk_military_rank_catalog_lifecycle_state')) {
        $ddl[] = "ALTER TABLE military_rank_catalog_versions ADD CONSTRAINT chk_military_rank_catalog_lifecycle_state CHECK ("
            . "(lifecycle_status = 'building' AND is_current = 0 AND valid_to IS NULL AND superseded_at IS NULL) OR "
            . "(lifecycle_status = 'published' AND is_current = 1 AND valid_to IS NULL AND superseded_at IS NULL) OR "
            . "(lifecycle_status = 'superseded' AND is_current = 0 AND valid_to IS NOT NULL AND superseded_at IS NOT NULL))";
    }

    if (!military_rank_v2_table_exists($pdo, $schemaName, 'military_personnel_composition_semantics')) {
        $ddl[] = <<<'SQL'
CREATE TABLE military_personnel_composition_semantics (
    composition_id BIGINT UNSIGNED NOT NULL,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    classification_kind VARCHAR(40) NOT NULL,
    is_staffing_selectable BOOLEAN NOT NULL DEFAULT FALSE,
    derivation_note VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (composition_id),
    UNIQUE KEY uq_military_composition_semantics_version (composition_id, catalog_version_id),
    KEY idx_military_composition_semantics_staffing (catalog_version_id, is_staffing_selectable, composition_id),
    CONSTRAINT fk_military_composition_semantics_composition
        FOREIGN KEY (composition_id, catalog_version_id)
        REFERENCES military_personnel_compositions(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_military_composition_semantics_kind
        CHECK (classification_kind IN ('normative-composition', 'derived-staffing-scope')),
    CONSTRAINT chk_military_composition_semantics_derivation
        CHECK (classification_kind <> 'derived-staffing-scope'
            OR (derivation_note IS NOT NULL AND CHAR_LENGTH(TRIM(derivation_note)) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    }

    if (!military_rank_v2_table_exists($pdo, $schemaName, 'military_personnel_composition_sources')) {
        $ddl[] = <<<'SQL'
CREATE TABLE military_personnel_composition_sources (
    composition_id BIGINT UNSIGNED NOT NULL,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    legal_source_id BIGINT UNSIGNED NOT NULL,
    source_role VARCHAR(80) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    note VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (composition_id, legal_source_id),
    UNIQUE KEY uq_military_composition_source_order (composition_id, sort_order),
    KEY idx_military_composition_sources_version (catalog_version_id, composition_id),
    CONSTRAINT fk_military_composition_sources_composition
        FOREIGN KEY (composition_id, catalog_version_id)
        REFERENCES military_personnel_compositions(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_military_composition_sources_legal
        FOREIGN KEY (legal_source_id) REFERENCES legal_sources(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_military_composition_sources_role
        CHECK (source_role IN ('normative-definition', 'rank-list', 'derived-classification-basis')),
    CONSTRAINT chk_military_composition_sources_order CHECK (sort_order > 0),
    CONSTRAINT chk_military_composition_sources_derived_note
        CHECK (source_role <> 'derived-classification-basis'
            OR (note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    }

    if (!military_rank_v2_index_exists($pdo, $schemaName, 'military_rank_levels', 'uq_military_rank_level_id_version')) {
        $ddl[] = 'ALTER TABLE military_rank_levels ADD UNIQUE KEY uq_military_rank_level_id_version (id, catalog_version_id)';
    }

    return $ddl;
}
