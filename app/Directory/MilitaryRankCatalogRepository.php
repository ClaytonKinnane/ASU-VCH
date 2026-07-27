<?php

declare(strict_types=1);

final class MilitaryRankCatalogRepository
{
    /** @var array{id:int,code:string,name:string,is_current:int,valid_from:string,valid_to:?string,verified_at:string,created_at:string}|null */
    private ?array $currentVersion = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{id:int,code:string,name:string,is_current:int,valid_from:string,valid_to:?string,verified_at:string,created_at:string} */
    public function currentVersion(): array
    {
        if (is_array($this->currentVersion)) {
            return $this->currentVersion;
        }

        $rows = $this->pdo->query(
            'SELECT id, code, name, is_current, valid_from, valid_to, verified_at, created_at '
            . 'FROM military_rank_catalog_versions '
            . 'WHERE is_current = 1 '
            . 'ORDER BY valid_from DESC, id DESC LIMIT 2'
        )->fetchAll();

        if (count($rows) !== 1) {
            throw new RuntimeException('Текущая версия справочника воинских званий не определена однозначно.');
        }

        $row = $rows[0];
        $this->currentVersion = [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'name' => (string) $row['name'],
            'is_current' => (int) $row['is_current'],
            'valid_from' => (string) $row['valid_from'],
            'valid_to' => $row['valid_to'] !== null ? (string) $row['valid_to'] : null,
            'verified_at' => (string) $row['verified_at'],
            'created_at' => (string) $row['created_at'],
        ];

        return $this->currentVersion;
    }

    /** @return list<array{id:int,code:string,document_type:string,document_date:string,document_number:string,title:string,provision:string,official_url:string,verified_at:string,source_role:string,sort_order:int}> */
    public function sources(): array
    {
        $version = $this->currentVersion();
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.code, s.document_type, s.document_date, s.document_number, s.title, '
            . 's.provision, s.official_url, s.verified_at, vs.source_role, vs.sort_order '
            . 'FROM military_rank_catalog_version_sources vs '
            . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
            . 'WHERE vs.catalog_version_id = :catalog_version_id '
            . 'ORDER BY vs.sort_order, s.id'
        );
        $stmt->execute(['catalog_version_id' => $version['id']]);

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

    /** @return list<array{id:int,code:string,name:string,parent_id:?int,parent_name:?string,path:string,sort_order:int}> */
    public function compositions(): array
    {
        $version = $this->currentVersion();
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.code, c.name, c.parent_id, c.sort_order, p.name AS parent_name '
            . 'FROM military_personnel_compositions c '
            . 'LEFT JOIN military_personnel_compositions p '
            . 'ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id '
            . 'WHERE c.catalog_version_id = :catalog_version_id '
            . 'ORDER BY c.sort_order, c.id'
        );
        $stmt->execute(['catalog_version_id' => $version['id']]);

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
            ];
        }

        return $result;
    }

    /**
     * @return array{items:list<array{id:int,code:string,troop_name:string,naval_name:?string,sort_order:int,composition_code:string,composition_name:string,parent_composition_name:?string,composition_path:string}>,total:int}
     */
    public function search(string $query = '', string $compositionCode = ''): array
    {
        $version = $this->currentVersion();
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
            $params[] = $version['id'];
            $params[] = $compositionCode;
            $params[] = $version['id'];
            $where[] = 'r.composition_id IN (SELECT id FROM composition_scope)';
        }

        $params[] = $version['id'];

        if ($query !== '') {
            $where[] = '(LOCATE(?, r.troop_name) > 0 OR LOCATE(?, COALESCE(r.naval_name, \'\')) > 0)';
            $params[] = $query;
            $params[] = $query;
        }

        $sql = $prefix
            . 'SELECT r.id, r.code, r.troop_name, r.naval_name, r.sort_order, '
            . 'c.code AS composition_code, c.name AS composition_name, p.name AS parent_composition_name '
            . 'FROM military_rank_levels r '
            . 'JOIN military_personnel_compositions c '
            . 'ON c.id = r.composition_id AND c.catalog_version_id = r.catalog_version_id '
            . 'LEFT JOIN military_personnel_compositions p '
            . 'ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id '
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
            ];
        }

        return ['items' => $items, 'total' => count($items)];
    }
}
