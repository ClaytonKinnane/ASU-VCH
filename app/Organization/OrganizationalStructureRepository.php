<?php

declare(strict_types=1);

final class OrganizationalStructureRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string,mixed>> */
    public function listStructures(string $query = '', string $status = ''): array
    {
        $where = [];
        $params = [];
        $query = trim($query);
        $status = trim($status);

        if ($query !== '') {
            $where[] = '(LOCATE(:query_name, s.display_name) > 0 OR LOCATE(:query_short, COALESCE(s.short_name, \'\')) > 0 OR LOCATE(:query_code, s.code) > 0)';
            $params['query_name'] = $query;
            $params['query_short'] = $query;
            $params['query_code'] = $query;
        }
        if (in_array($status, ['active', 'archived'], true)) {
            $where[] = 's.status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT s.*, '
            . '(SELECT v.version_number FROM organizational_structure_versions v WHERE v.organizational_structure_id = s.id AND v.status = \'active\' LIMIT 1) AS active_version_number, '
            . '(SELECT v.effective_from FROM organizational_structure_versions v WHERE v.organizational_structure_id = s.id AND v.status = \'active\' LIMIT 1) AS active_effective_from, '
            . '(SELECT v.status FROM organizational_structure_versions v WHERE v.organizational_structure_id = s.id AND v.status IN (\'draft\', \'approved\') LIMIT 1) AS pending_status '
            . 'FROM organizational_structures s ';
        if ($where !== []) {
            $sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
        }
        $sql .= 'ORDER BY s.status, s.display_name, s.id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findStructure(int $structureId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM organizational_structures WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $structureId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return list<array<string,mixed>> */
    public function versionsForStructure(int $structureId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*, cv.code AS catalog_code, cv.name AS catalog_name, '
            . '(SELECT COUNT(*) FROM organizational_structure_nodes n WHERE n.structure_version_id = v.id) AS node_count '
            . 'FROM organizational_structure_versions v '
            . 'JOIN organizational_element_catalog_versions cv ON cv.id = v.catalog_version_id '
            . 'WHERE v.organizational_structure_id = :structure_id '
            . 'ORDER BY v.version_number DESC, v.id DESC'
        );
        $stmt->execute(['structure_id' => $structureId]);
        return $stmt->fetchAll();
    }

    public function findVersion(int $versionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*, s.code AS structure_code, s.display_name AS structure_display_name, s.status AS structure_status, '
            . 'cv.code AS catalog_code, cv.name AS catalog_name '
            . 'FROM organizational_structure_versions v '
            . 'JOIN organizational_structures s ON s.id = v.organizational_structure_id '
            . 'JOIN organizational_element_catalog_versions cv ON cv.id = v.catalog_version_id '
            . 'WHERE v.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $versionId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function pendingVersion(int $structureId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM organizational_structure_versions WHERE organizational_structure_id = :structure_id AND status IN ('draft', 'approved') LIMIT 1"
        );
        $stmt->execute(['structure_id' => $structureId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function activeVersion(int $structureId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM organizational_structure_versions WHERE organizational_structure_id = :structure_id AND status = 'active' LIMIT 1"
        );
        $stmt->execute(['structure_id' => $structureId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return list<array<string,mixed>> */
    public function rootTypesForCatalog(int $catalogVersionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.code, t.name, t.short_name, t.sort_order, MAX(tc.is_primary) AS is_primary '
            . 'FROM organizational_element_types t '
            . 'JOIN organizational_element_type_classes tc ON tc.type_id = t.id AND tc.catalog_version_id = t.catalog_version_id '
            . 'JOIN organizational_element_classes c ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
            . 'WHERE t.catalog_version_id = :catalog_version_id AND c.code = \'military-unit\' '
            . 'GROUP BY t.id, t.code, t.name, t.short_name, t.sort_order '
            . 'ORDER BY is_primary DESC, t.sort_order, t.id'
        );
        $stmt->execute(['catalog_version_id' => $catalogVersionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function typesForCatalog(int $catalogVersionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.code, t.name, t.short_name, t.sort_order, '
            . 'GROUP_CONCAT(c.name ORDER BY tc.sort_order SEPARATOR \' · \') AS class_names '
            . 'FROM organizational_element_types t '
            . 'LEFT JOIN organizational_element_type_classes tc ON tc.type_id = t.id AND tc.catalog_version_id = t.catalog_version_id '
            . 'LEFT JOIN organizational_element_classes c ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
            . 'WHERE t.catalog_version_id = :catalog_version_id '
            . 'GROUP BY t.id, t.code, t.name, t.short_name, t.sort_order '
            . 'ORDER BY t.sort_order, t.id'
        );
        $stmt->execute(['catalog_version_id' => $catalogVersionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function nodesForVersion(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT n.*, t.code AS type_code, t.name AS type_name, t.short_name AS type_short_name, '
            . '(SELECT COUNT(*) FROM organizational_structure_nodes c WHERE c.parent_node_id = n.id) AS child_count '
            . 'FROM organizational_structure_nodes n '
            . 'JOIN organizational_element_types t ON t.id = n.organizational_element_type_id AND t.catalog_version_id = n.catalog_version_id '
            . 'WHERE n.structure_version_id = :version_id '
            . 'ORDER BY n.parent_node_id IS NOT NULL, n.parent_node_id, n.sort_order, n.id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    public function findNode(int $nodeId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT n.*, v.status AS version_status, v.revision AS version_revision '
            . 'FROM organizational_structure_nodes n '
            . 'JOIN organizational_structure_versions v ON v.id = n.structure_version_id '
            . 'WHERE n.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $nodeId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return list<array<string,mixed>> */
    public function documentsForVersion(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*, vd.document_role, vd.sort_order '
            . 'FROM organizational_structure_version_documents vd '
            . 'JOIN organizational_structure_documents d ON d.id = vd.document_id '
            . 'WHERE vd.structure_version_id = :version_id '
            . 'ORDER BY vd.sort_order, d.id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function historyForStructure(int $structureId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT e.*, u.display_name AS actor_name '
            . 'FROM organizational_structure_change_events e '
            . 'LEFT JOIN users u ON u.id = e.actor_user_id '
            . 'WHERE e.organizational_structure_id = :structure_id '
            . 'ORDER BY e.created_at DESC, e.id DESC LIMIT ' . $limit
        );
        $stmt->execute(['structure_id' => $structureId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function diffVersions(int $baseVersionId, int $targetVersionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(b.organizational_structure_element_id, t.organizational_structure_element_id) AS element_id, '
            . 'b.id AS base_node_id, t.id AS target_node_id, '
            . 'b.name AS base_name, t.name AS target_name, b.short_name AS base_short_name, t.short_name AS target_short_name, '
            . 'b.internal_code AS base_internal_code, t.internal_code AS target_internal_code, '
            . 'b.organizational_element_type_id AS base_type_id, t.organizational_element_type_id AS target_type_id, '
            . 'bp.organizational_structure_element_id AS base_parent_element_id, tp.organizational_structure_element_id AS target_parent_element_id, '
            . 'b.sort_order AS base_sort_order, t.sort_order AS target_sort_order, b.note AS base_note, t.note AS target_note '
            . 'FROM organizational_structure_nodes b '
            . 'LEFT JOIN organizational_structure_nodes t ON t.structure_version_id = :target_version_id_1 '
            . 'AND t.organizational_structure_element_id = b.organizational_structure_element_id '
            . 'LEFT JOIN organizational_structure_nodes bp ON bp.id = b.parent_node_id '
            . 'LEFT JOIN organizational_structure_nodes tp ON tp.id = t.parent_node_id '
            . 'WHERE b.structure_version_id = :base_version_id '
            . 'UNION ALL '
            . 'SELECT t.organizational_structure_element_id, NULL, t.id, NULL, t.name, NULL, t.short_name, NULL, t.internal_code, '
            . 'NULL, t.organizational_element_type_id, NULL, tp.organizational_structure_element_id, NULL, t.sort_order, NULL, t.note '
            . 'FROM organizational_structure_nodes t '
            . 'LEFT JOIN organizational_structure_nodes tp ON tp.id = t.parent_node_id '
            . 'LEFT JOIN organizational_structure_nodes b ON b.structure_version_id = :base_version_id_2 '
            . 'AND b.organizational_structure_element_id = t.organizational_structure_element_id '
            . 'WHERE t.structure_version_id = :target_version_id_2 AND b.id IS NULL'
        );
        $stmt->execute([
            'target_version_id_1' => $targetVersionId,
            'base_version_id' => $baseVersionId,
            'base_version_id_2' => $baseVersionId,
            'target_version_id_2' => $targetVersionId,
        ]);
        return $stmt->fetchAll();
    }
}
