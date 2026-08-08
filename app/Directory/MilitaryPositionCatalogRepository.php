<?php

declare(strict_types=1);

final class MilitaryPositionCatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string,mixed>> */
    public function versions(): array
    {
        $rows = $this->pdo->query(
            'SELECT v.*,COUNT(t.id) AS entry_count,'
            . "SUM(CASE WHEN t.status='active' THEN 1 ELSE 0 END) AS active_entry_count "
            . 'FROM military_position_catalog_versions v '
            . 'LEFT JOIN military_position_types t ON t.catalog_version_id=v.id '
            . 'GROUP BY v.id ORDER BY '
            . "CASE v.status WHEN 'draft' THEN 0 WHEN 'published' THEN 1 WHEN 'superseded' THEN 2 ELSE 3 END,"
            . 'v.version_number DESC,v.id DESC'
        )->fetchAll();
        return array_map([$this, 'mapVersion'], $rows);
    }

    /** @return array<string,mixed> */
    public function defaultVersion(): array
    {
        $row = $this->pdo->query(
            'SELECT v.*,COUNT(t.id) AS entry_count,'
            . "SUM(CASE WHEN t.status='active' THEN 1 ELSE 0 END) AS active_entry_count "
            . 'FROM military_position_catalog_versions v '
            . 'LEFT JOIN military_position_types t ON t.catalog_version_id=v.id '
            . "WHERE v.status IN ('draft','published') GROUP BY v.id "
            . "ORDER BY CASE v.status WHEN 'draft' THEN 0 ELSE 1 END,v.id DESC LIMIT 1"
        )->fetch();
        if (!is_array($row)) {
            throw new RuntimeException('Версия справочника воинских должностей не найдена.');
        }
        return $this->mapVersion($row);
    }

    /** @return array<string,mixed> */
    public function currentVersion(): array
    {
        $rows = $this->pdo->query(
            'SELECT v.*,COUNT(t.id) AS entry_count,'
            . "SUM(CASE WHEN t.status='active' THEN 1 ELSE 0 END) AS active_entry_count "
            . 'FROM military_position_catalog_versions v '
            . 'LEFT JOIN military_position_types t ON t.catalog_version_id=v.id '
            . "WHERE v.status='published' GROUP BY v.id ORDER BY v.valid_from DESC,v.id DESC LIMIT 2"
        )->fetchAll();
        if (count($rows) !== 1) {
            throw new RuntimeException('Текущая версия справочника воинских должностей не определена однозначно.');
        }
        return $this->mapVersion($rows[0]);
    }

    /** @return array<string,mixed> */
    public function version(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*,COUNT(t.id) AS entry_count,'
            . "SUM(CASE WHEN t.status='active' THEN 1 ELSE 0 END) AS active_entry_count "
            . 'FROM military_position_catalog_versions v '
            . 'LEFT JOIN military_position_types t ON t.catalog_version_id=v.id '
            . 'WHERE v.id=:id GROUP BY v.id LIMIT 1'
        );
        $stmt->execute(['id' => $versionId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new OutOfBoundsException('Версия справочника воинских должностей не найдена.');
        }
        return $this->mapVersion($row);
    }

    public function hasDraft(): bool
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM military_position_catalog_versions WHERE status='draft'"
        )->fetchColumn() > 0;
    }

    /** @return list<array<string,mixed>> */
    public function versionSources(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.document_type,s.document_date,s.document_number,s.title,s.provision,'
            . 's.official_url,s.verified_at,vs.source_role,vs.sort_order '
            . 'FROM military_position_catalog_version_sources vs '
            . 'JOIN legal_sources s ON s.id=vs.legal_source_id '
            . 'WHERE vs.catalog_version_id=:id ORDER BY vs.sort_order,s.id'
        );
        $stmt->execute(['id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return array{items:list<array<string,mixed>>,total:int} */
    public function entries(
        int $versionId,
        string $query = '',
        string $status = '',
        string $combined = '',
        string $sourceType = ''
    ): array {
        $where = ['t.catalog_version_id=:version_id'];
        $params = ['version_id' => $versionId];
        $query = trim($query);
        if ($query !== '') {
            $where[] = '(LOCATE(:query_name,t.name)>0 OR LOCATE(:query_full,COALESCE(t.full_name,\'\'))>0 '
                . 'OR LOCATE(:query_short,COALESCE(t.short_name,\'\'))>0 '
                . 'OR LOCATE(:query_source,COALESCE(t.source_reference,\'\'))>0)';
            $params['query_name'] = $query;
            $params['query_full'] = $query;
            $params['query_short'] = $query;
            $params['query_source'] = $query;
        }
        if (in_array($status, ['active', 'archived'], true)) {
            $where[] = 't.status=:status';
            $params['status'] = $status;
        }
        if (in_array($combined, ['0', '1'], true)) {
            $where[] = 't.is_combined=:combined';
            $params['combined'] = (int) $combined;
        }
        if (in_array($sourceType, ['official', 'local', 'imported'], true)) {
            $where[] = 't.source_type=:source_type';
            $params['source_type'] = $sourceType;
        }
        $sql = 'SELECT t.*,(SELECT COUNT(*) FROM staffing_slots s WHERE s.position_type_id=t.id) AS usage_count '
            . 'FROM military_position_types t WHERE ' . implode(' AND ', $where)
            . ' ORDER BY t.sort_order,t.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = array_map([$this, 'mapEntry'], $stmt->fetchAll());
        return ['items' => $items, 'total' => count($items)];
    }

    /** @return array<string,mixed> */
    public function entry(int $versionId, int $entryId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*,(SELECT COUNT(*) FROM staffing_slots s WHERE s.position_type_id=t.id) AS usage_count '
            . 'FROM military_position_types t WHERE t.id=:entry_id AND t.catalog_version_id=:version_id LIMIT 1'
        );
        $stmt->execute(['entry_id' => $entryId, 'version_id' => $versionId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new OutOfBoundsException('Воинская должность не найдена в выбранной версии.');
        }
        return $this->mapEntry($row);
    }

    /** @return list<array<string,mixed>> */
    public function history(?int $versionId = null): array
    {
        $sql = 'SELECT e.*,u.display_name AS actor_name,v.version_number,v.version_label '
            . 'FROM military_position_change_events e '
            . 'LEFT JOIN users u ON u.id=e.actor_user_id '
            . 'JOIN military_position_catalog_versions v ON v.id=e.catalog_version_id';
        $params = [];
        if ($versionId !== null) {
            $sql .= ' WHERE e.catalog_version_id=:version_id';
            $params['version_id'] = $versionId;
        }
        $sql .= ' ORDER BY e.created_at DESC,e.id DESC LIMIT 500';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function mapVersion(array $row): array
    {
        foreach (['id','version_number','revision','rank_catalog_version_id','organizational_element_catalog_version_id','entry_count','active_entry_count'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        return $row;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function mapEntry(array $row): array
    {
        foreach (['id','catalog_version_id','is_combined','sort_order','revision','usage_count'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        return $row;
    }
}
