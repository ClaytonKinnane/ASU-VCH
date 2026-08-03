<?php

declare(strict_types=1);

final class MilitaryRankCompatibilityService
{
    public const COMPATIBLE = 'compatible';
    public const INCOMPATIBLE = 'incompatible';
    public const INVALID_CATALOG_VERSION = 'invalid-catalog-version';
    public const COMPOSITION_NOT_SELECTABLE = 'composition-not-selectable';
    public const RECORD_NOT_FOUND = 'record-not-found';
    public const INTEGRITY_ERROR = 'integrity-error';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function check(int $catalogVersionId, int $compositionId, int $rankLevelId): string
    {
        if ($catalogVersionId <= 0 || $compositionId <= 0 || $rankLevelId <= 0) {
            return self::RECORD_NOT_FOUND;
        }

        $versionStmt = $this->pdo->prepare(
            'SELECT lifecycle_status FROM military_rank_catalog_versions '
            . 'WHERE id = :catalog_version_id LIMIT 1'
        );
        $versionStmt->execute(['catalog_version_id' => $catalogVersionId]);
        $lifecycle = $versionStmt->fetchColumn();
        if (!is_string($lifecycle) || !in_array($lifecycle, ['published', 'superseded'], true)) {
            return self::INVALID_CATALOG_VERSION;
        }

        $compositionStmt = $this->pdo->prepare(
            'SELECT c.id, c.parent_id, s.is_staffing_selectable '
            . 'FROM military_personnel_compositions c '
            . 'LEFT JOIN military_personnel_composition_semantics s '
            . 'ON s.composition_id = c.id AND s.catalog_version_id = c.catalog_version_id '
            . 'WHERE c.id = :composition_id AND c.catalog_version_id = :catalog_version_id LIMIT 1'
        );
        $compositionStmt->execute([
            'composition_id' => $compositionId,
            'catalog_version_id' => $catalogVersionId,
        ]);
        $composition = $compositionStmt->fetch();
        if (!is_array($composition)) {
            $otherVersionStmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM military_personnel_compositions WHERE id = :composition_id'
            );
            $otherVersionStmt->execute(['composition_id' => $compositionId]);
            return (int) $otherVersionStmt->fetchColumn() > 0
                ? self::INVALID_CATALOG_VERSION
                : self::RECORD_NOT_FOUND;
        }
        if ($composition['is_staffing_selectable'] === null || (int) $composition['is_staffing_selectable'] !== 1) {
            return self::COMPOSITION_NOT_SELECTABLE;
        }

        $rankStmt = $this->pdo->prepare(
            'SELECT composition_id FROM military_rank_levels '
            . 'WHERE id = :rank_level_id AND catalog_version_id = :catalog_version_id LIMIT 1'
        );
        $rankStmt->execute([
            'rank_level_id' => $rankLevelId,
            'catalog_version_id' => $catalogVersionId,
        ]);
        $rankCompositionId = $rankStmt->fetchColumn();
        if ($rankCompositionId === false) {
            $otherVersionStmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM military_rank_levels WHERE id = :rank_level_id'
            );
            $otherVersionStmt->execute(['rank_level_id' => $rankLevelId]);
            return (int) $otherVersionStmt->fetchColumn() > 0
                ? self::INVALID_CATALOG_VERSION
                : self::RECORD_NOT_FOUND;
        }

        $currentCompositionId = (int) $rankCompositionId;
        $visited = [];
        for ($depth = 0; $depth < 64; $depth++) {
            if ($currentCompositionId === $compositionId) {
                return self::COMPATIBLE;
            }
            if (isset($visited[$currentCompositionId])) {
                return self::INTEGRITY_ERROR;
            }
            $visited[$currentCompositionId] = true;

            $parentStmt = $this->pdo->prepare(
                'SELECT parent_id FROM military_personnel_compositions '
                . 'WHERE id = :composition_id AND catalog_version_id = :catalog_version_id LIMIT 1'
            );
            $parentStmt->execute([
                'composition_id' => $currentCompositionId,
                'catalog_version_id' => $catalogVersionId,
            ]);
            $parent = $parentStmt->fetchColumn();
            if ($parent === false) {
                return self::INTEGRITY_ERROR;
            }
            if ($parent === null) {
                return self::INCOMPATIBLE;
            }
            $currentCompositionId = (int) $parent;
        }

        return self::INTEGRITY_ERROR;
    }
}
