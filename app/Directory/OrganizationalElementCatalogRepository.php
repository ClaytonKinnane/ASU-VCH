<?php

declare(strict_types=1);

final class OrganizationalElementCatalogRepository
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
            . 'FROM organizational_element_catalog_versions '
            . 'WHERE is_current = 1 '
            . 'ORDER BY valid_from DESC, id DESC LIMIT 2'
        )->fetchAll();

        if (count($rows) !== 1) {
            throw new RuntimeException('Текущая версия справочника типов организационных элементов не определена однозначно.');
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
    public function versionSources(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.code, s.document_type, s.document_date, s.document_number, s.title, '
            . 's.provision, s.official_url, s.verified_at, vs.source_role, vs.sort_order '
            . 'FROM organizational_element_catalog_version_sources vs '
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

    /** @return list<array{id:int,code:string,name:string,description:string,sort_order:int}> */
    public function classes(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, description, sort_order '
            . 'FROM organizational_element_classes '
            . 'WHERE catalog_version_id = :catalog_version_id '
            . 'ORDER BY sort_order, id'
        );
        $stmt->execute(['catalog_version_id' => $versionId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'description' => (string) $row['description'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return $result;
    }

    /**
     * @return array{items:list<array{id:int,code:string,name:string,short_name:?string,description:string,applicability_note:string,sort_order:int}>,total:int}
     */
    public function searchTypes(
        int $versionId,
        string $query = '',
        string $classCode = '',
        string $scope = ''
    ): array {
        $query = trim($query);
        $classCode = trim($classCode);
        $scope = trim($scope);

        $where = ['t.catalog_version_id = ?'];
        $params = [$versionId];

        if ($query !== '') {
            $where[] = '('
                . 'LOCATE(?, t.name) > 0 '
                . 'OR LOCATE(?, COALESCE(t.short_name, \'\')) > 0 '
                . 'OR EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_aliases a '
                . 'WHERE a.catalog_version_id = t.catalog_version_id '
                . 'AND a.type_id = t.id AND LOCATE(?, a.alias) > 0'
                . ')'
                . ')';
            $params[] = $query;
            $params[] = $query;
            $params[] = $query;
        }

        if ($classCode !== '') {
            $where[] = 'EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_classes tc '
                . 'JOIN organizational_element_classes c '
                . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
                . 'WHERE tc.catalog_version_id = t.catalog_version_id '
                . 'AND tc.type_id = t.id AND c.code = ?'
                . ')';
            $params[] = $classCode;
        }

        if ($scope === 'non_subdivision_only') {
            $where[] = 'NOT EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_classes tc '
                . 'JOIN organizational_element_classes c '
                . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
                . 'WHERE tc.catalog_version_id = t.catalog_version_id '
                . 'AND tc.type_id = t.id AND c.code = \'subdivision\''
                . ')';
        } elseif ($scope === 'subdivision_only') {
            $where[] = 'EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_classes tc '
                . 'JOIN organizational_element_classes c '
                . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
                . 'WHERE tc.catalog_version_id = t.catalog_version_id '
                . 'AND tc.type_id = t.id AND c.code = \'subdivision\''
                . ')';
            $where[] = 'NOT EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_classes tc '
                . 'JOIN organizational_element_classes c '
                . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
                . 'WHERE tc.catalog_version_id = t.catalog_version_id '
                . 'AND tc.type_id = t.id AND c.code <> \'subdivision\''
                . ')';
        } elseif ($scope === 'mixed') {
            $where[] = 'EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_classes tc '
                . 'JOIN organizational_element_classes c '
                . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
                . 'WHERE tc.catalog_version_id = t.catalog_version_id '
                . 'AND tc.type_id = t.id AND c.code = \'subdivision\''
                . ')';
            $where[] = 'EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_classes tc '
                . 'JOIN organizational_element_classes c '
                . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
                . 'WHERE tc.catalog_version_id = t.catalog_version_id '
                . 'AND tc.type_id = t.id AND c.code <> \'subdivision\''
                . ')';
        }

        $sql = 'SELECT t.id, t.code, t.name, t.short_name, t.description, t.applicability_note, t.sort_order '
            . 'FROM organizational_element_types t '
            . 'WHERE ' . implode(' AND ', $where) . ' '
            . 'ORDER BY t.sort_order, t.id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'short_name' => $row['short_name'] !== null ? (string) $row['short_name'] : null,
                'description' => (string) $row['description'],
                'applicability_note' => (string) $row['applicability_note'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return ['items' => $items, 'total' => count($items)];
    }

    /** @param list<int> $typeIds
     *  @return array<int,list<array{id:int,code:string,name:string,is_primary:int,context_note:?string,sort_order:int}>>
     */
    public function classesForTypes(int $versionId, array $typeIds): array
    {
        if ($typeIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($typeIds), '?'));
        $params = array_merge([$versionId], $typeIds);
        $stmt = $this->pdo->prepare(
            'SELECT tc.type_id, c.id, c.code, c.name, tc.is_primary, tc.context_note, tc.sort_order '
            . 'FROM organizational_element_type_classes tc '
            . 'JOIN organizational_element_classes c '
            . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
            . 'WHERE tc.catalog_version_id = ? AND tc.type_id IN (' . $placeholders . ') '
            . 'ORDER BY tc.type_id, tc.sort_order, c.id'
        );
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $typeId = (int) $row['type_id'];
            $result[$typeId][] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'is_primary' => (int) $row['is_primary'],
                'context_note' => $row['context_note'] !== null ? (string) $row['context_note'] : null,
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return $result;
    }

    /** @param list<int> $typeIds
     *  @return array<int,list<array{id:int,code:string,document_type:string,document_date:string,document_number:string,title:string,provision:string,official_url:string,source_role:string,provision_detail:string,sort_order:int}>>
     */
    public function sourcesForTypes(int $versionId, array $typeIds): array
    {
        if ($typeIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($typeIds), '?'));
        $params = array_merge([$versionId], $typeIds);
        $stmt = $this->pdo->prepare(
            'SELECT ts.type_id, s.id, s.code, s.document_type, s.document_date, s.document_number, '
            . 's.title, s.provision, s.official_url, ts.source_role, ts.provision_detail, ts.sort_order '
            . 'FROM organizational_element_type_sources ts '
            . 'JOIN legal_sources s ON s.id = ts.legal_source_id '
            . 'WHERE ts.catalog_version_id = ? AND ts.type_id IN (' . $placeholders . ') '
            . 'ORDER BY ts.type_id, ts.sort_order, s.id'
        );
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $typeId = (int) $row['type_id'];
            $result[$typeId][] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'document_type' => (string) $row['document_type'],
                'document_date' => (string) $row['document_date'],
                'document_number' => (string) $row['document_number'],
                'title' => (string) $row['title'],
                'provision' => (string) $row['provision'],
                'official_url' => (string) $row['official_url'],
                'source_role' => (string) $row['source_role'],
                'provision_detail' => (string) $row['provision_detail'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return $result;
    }

    /** @param list<int> $typeIds
     *  @return array<int,list<array{id:int,alias_type:string,alias:string,sort_order:int}>>
     */
    public function aliasesForTypes(int $versionId, array $typeIds): array
    {
        if ($typeIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($typeIds), '?'));
        $params = array_merge([$versionId], $typeIds);
        $stmt = $this->pdo->prepare(
            'SELECT id, type_id, alias_type, alias, sort_order '
            . 'FROM organizational_element_type_aliases '
            . 'WHERE catalog_version_id = ? AND type_id IN (' . $placeholders . ') '
            . 'ORDER BY type_id, sort_order, id'
        );
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $typeId = (int) $row['type_id'];
            $result[$typeId][] = [
                'id' => (int) $row['id'],
                'alias_type' => (string) $row['alias_type'],
                'alias' => (string) $row['alias'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return $result;
    }
}
