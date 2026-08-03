<?php

declare(strict_types=1);

require_once __DIR__ . '/MilitaryRankCompatibilityService.php';

final class MilitaryRankCatalogRepository
{
    /** @var array{id:int,code:string,name:string,is_current:int,lifecycle_status:string,valid_from:string,valid_to:?string,verified_at:string,published_at:?string,superseded_at:?string,created_at:string}|null */
    private ?array $currentVersion = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{id:int,code:string,name:string,is_current:int,lifecycle_status:string,valid_from:string,valid_to:?string,verified_at:string,published_at:?string,superseded_at:?string,created_at:string} */
    public function currentVersion(): array
    {
        if (is_array($this->currentVersion)) {
            return $this->currentVersion;
        }

        $rows = $this->pdo->query(
            'SELECT id, code, name, is_current, lifecycle_status, valid_from, valid_to, verified_at, '
            . 'published_at, superseded_at, created_at '
            . 'FROM military_rank_catalog_versions '
            . "WHERE lifecycle_status = 'published' AND is_current = 1 "
            . 'ORDER BY valid_from DESC, id DESC LIMIT 2'
        )->fetchAll();

        if (count($rows) !== 1) {
            throw new RuntimeException('Текущая версия справочника воинских званий не определена однозначно.');
        }

        $this->currentVersion = $this->mapVersion($rows[0]);
        return $this->currentVersion;
    }

    /** @return list<array{id:int,code:string,name:string,is_current:int,lifecycle_status:string,valid_from:string,valid_to:?string,verified_at:string,published_at:?string,superseded_at:?string,created_at:string}> */
    public function visibleVersions(): array
    {
        $rows = $this->pdo->query(
            'SELECT id, code, name, is_current, lifecycle_status, valid_from, valid_to, verified_at, '
            . 'published_at, superseded_at, created_at '
            . 'FROM military_rank_catalog_versions '
            . "WHERE lifecycle_status IN ('published', 'superseded') "
            . 'ORDER BY is_current DESC, valid_from DESC, id DESC'
        )->fetchAll();

        return array_map(fn (array $row): array => $this->mapVersion($row), $rows);
    }

    /** @return array{id:int,code:string,name:string,is_current:int,lifecycle_status:string,valid_from:string,valid_to:?string,verified_at:string,published_at:?string,superseded_at:?string,created_at:string} */
    public function version(string $code = ''): array
    {
        $code = trim($code);
        if ($code === '') {
            return $this->currentVersion();
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, is_current, lifecycle_status, valid_from, valid_to, verified_at, '
            . 'published_at, superseded_at, created_at '
            . 'FROM military_rank_catalog_versions '
            . "WHERE code = :code AND lifecycle_status IN ('published', 'superseded') LIMIT 1"
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new OutOfBoundsException('Запрошенная версия справочника не найдена.');
        }

        return $this->mapVersion($row);
    }

    /** @return list<array{id:int,code:string,document_type:string,document_date:string,document_number:string,title:string,provision:string,official_url:string,verified_at:string,source_role:string,sort_order:int}> */
    public function sources(?int $versionId = null): array
    {
        $versionId ??= $this->currentVersion()['id'];
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.code, s.document_type, s.document_date, s.document_number, s.title, '
            . 's.provision, s.official_url, s.verified_at, vs.source_role, vs.sort_order '
            . 'FROM military_rank_catalog_version_sources vs '
            . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
            . 'WHERE vs.catalog_version_id = :catalog_version_id '
            . 'ORDER BY vs.sort_order, s.id'
        );
        $stmt->execute(['catalog_version_id' => $versionId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'document_type' => (string) $row['document_type'],
                'document_date' => (string) $row['document_date'],
                'document_number' => (string) $row['document_number'],
                'title' => (string) $row['title'],
                'provision' => (string) $row['provision'],
                'official_url' => (string) $row['official_url'],
                'verified_at' => (string) $row['verified_at'],
                'source_role' => (string) $row['source_role'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return $result;
    }

    /** @return list<array{id:int,code:string,name:string,parent_id:?int,parent_name:?string,path:string,sort_order:int,classification_kind:?string,is_staffing_selectable:?int,derivation_note:?string}> */
    public function compositions(?int $versionId = null): array
    {
        $versionId ??= $this->currentVersion()['id'];
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.code, c.name, c.parent_id, c.sort_order, p.name AS parent_name, '
            . 's.classification_kind, s.is_staffing_selectable, s.derivation_note '
            . 'FROM military_personnel_compositions c '
            . 'LEFT JOIN military_personnel_compositions p '
            . 'ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id '
            . 'LEFT JOIN military_personnel_composition_semantics s '
            . 'ON s.composition_id = c.id AND s.catalog_version_id = c.catalog_version_id '
            . 'WHERE c.catalog_version_id = :catalog_version_id '
            . 'ORDER BY c.sort_order, c.id'
        );
        $stmt->execute(['catalog_version_id' => $versionId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $parentName = $row['parent_name'] !== null ? (string) $row['parent_name'] : null;
            $name = (string) $row['name'];
            $result[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => $name,
                'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'parent_name' => $parentName,
                'path' => $parentName !== null ? $parentName . ' → ' . $name : $name,
                'sort_order' => (int) $row['sort_order'],
                'classification_kind' => $row['classification_kind'] !== null
                    ? (string) $row['classification_kind']
                    : null,
                'is_staffing_selectable' => $row['is_staffing_selectable'] !== null
                    ? (int) $row['is_staffing_selectable']
                    : null,
                'derivation_note' => $row['derivation_note'] !== null
                    ? (string) $row['derivation_note']
                    : null,
            ];
        }

        return $result;
    }

    /**
     * @return array{items:list<array{id:int,code:string,troop_name:string,naval_name:?string,sort_order:int,composition_code:string,composition_name:string,parent_composition_name:?string,composition_path:string,classification_kind:?string,is_staffing_selectable:?int,derivation_note:?string}>,total:int}
     */
    public function search(
        string $query = '',
        string $compositionCode = '',
        ?int $versionId = null
    ): array {
        $versionId ??= $this->currentVersion()['id'];
        $query = trim($query);
        $compositionCode = trim($compositionCode);

        $params = [];
        $prefix = '';
        $where = ['r.catalog_version_id = ?'];

        if ($compositionCode !== '') {
            $prefix = 'WITH RECURSIVE composition_scope AS ('
                . 'SELECT id FROM military_personnel_compositions '
                . 'WHERE catalog_version_id = ? AND code = ? '
                . 'UNION ALL '
                . 'SELECT child.id FROM military_personnel_compositions child '
                . 'JOIN composition_scope scope ON child.parent_id = scope.id '
                . 'WHERE child.catalog_version_id = ?'
                . ') ';
            $params[] = $versionId;
            $params[] = $compositionCode;
            $params[] = $versionId;
            $where[] = 'r.composition_id IN (SELECT id FROM composition_scope)';
        }

        $params[] = $versionId;

        if ($query !== '') {
            $where[] = '(LOCATE(?, r.troop_name) > 0 OR LOCATE(?, COALESCE(r.naval_name, \'\')) > 0)';
            $params[] = $query;
            $params[] = $query;
        }

        $sql = $prefix
            . 'SELECT r.id, r.code, r.troop_name, r.naval_name, r.sort_order, '
            . 'c.code AS composition_code, c.name AS composition_name, p.name AS parent_composition_name, '
            . 's.classification_kind, s.is_staffing_selectable, s.derivation_note '
            . 'FROM military_rank_levels r '
            . 'JOIN military_personnel_compositions c '
            . 'ON c.id = r.composition_id AND c.catalog_version_id = r.catalog_version_id '
            . 'LEFT JOIN military_personnel_compositions p '
            . 'ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id '
            . 'LEFT JOIN military_personnel_composition_semantics s '
            . 'ON s.composition_id = c.id AND s.catalog_version_id = c.catalog_version_id '
            . 'WHERE ' . implode(' AND ', $where) . ' '
            . 'ORDER BY r.sort_order, r.id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $parentName = $row['parent_composition_name'] !== null
                ? (string) $row['parent_composition_name']
                : null;
            $compositionName = (string) $row['composition_name'];
            $items[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'troop_name' => (string) $row['troop_name'],
                'naval_name' => $row['naval_name'] !== null ? (string) $row['naval_name'] : null,
                'sort_order' => (int) $row['sort_order'],
                'composition_code' => (string) $row['composition_code'],
                'composition_name' => $compositionName,
                'parent_composition_name' => $parentName,
                'composition_path' => $parentName !== null
                    ? $parentName . ' → ' . $compositionName
                    : $compositionName,
                'classification_kind' => $row['classification_kind'] !== null
                    ? (string) $row['classification_kind']
                    : null,
                'is_staffing_selectable' => $row['is_staffing_selectable'] !== null
                    ? (int) $row['is_staffing_selectable']
                    : null,
                'derivation_note' => $row['derivation_note'] !== null
                    ? (string) $row['derivation_note']
                    : null,
            ];
        }

        return ['items' => $items, 'total' => count($items)];
    }

    /** @param array<string,mixed> $row
     * @return array{id:int,code:string,name:string,is_current:int,lifecycle_status:string,valid_from:string,valid_to:?string,verified_at:string,published_at:?string,superseded_at:?string,created_at:string}
     */
    private function mapVersion(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'name' => (string) $row['name'],
            'is_current' => (int) $row['is_current'],
            'lifecycle_status' => (string) $row['lifecycle_status'],
            'valid_from' => (string) $row['valid_from'],
            'valid_to' => $row['valid_to'] !== null ? (string) $row['valid_to'] : null,
            'verified_at' => (string) $row['verified_at'],
            'published_at' => $row['published_at'] !== null ? (string) $row['published_at'] : null,
            'superseded_at' => $row['superseded_at'] !== null ? (string) $row['superseded_at'] : null,
            'created_at' => (string) $row['created_at'],
        ];
    }
}
