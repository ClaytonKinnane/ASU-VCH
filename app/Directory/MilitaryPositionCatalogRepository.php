<?php

declare(strict_types=1);

final class MilitaryPositionCatalogRepository
{
    /** @var array<string,mixed>|null */
    private ?array $currentVersion = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string,mixed> */
    public function currentVersion(): array
    {
        if ($this->currentVersion !== null) {
            return $this->currentVersion;
        }
        $rows = $this->pdo->query(
            "SELECT id, code, name, coverage_note, status, valid_from, valid_to, verified_at, "
            . "rank_catalog_version_id, organizational_element_catalog_version_id, created_at "
            . "FROM military_position_catalog_versions WHERE status = 'published' "
            . "ORDER BY valid_from DESC, id DESC LIMIT 2"
        )->fetchAll();
        if (count($rows) !== 1) {
            throw new RuntimeException('Текущая версия справочника воинских должностей не определена однозначно.');
        }
        $this->currentVersion = $rows[0];
        return $this->currentVersion;
    }

    /** @return list<array<string,mixed>> */
    public function versionSources(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.code, s.document_type, s.document_date, s.document_number, s.title, '
            . 's.provision, s.official_url, s.verified_at, vs.source_role, vs.sort_order '
            . 'FROM military_position_catalog_version_sources vs '
            . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
            . 'WHERE vs.catalog_version_id = :version_id ORDER BY vs.sort_order, s.id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function families(int $versionId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, description, sort_order FROM military_position_families WHERE catalog_version_id=:id ORDER BY sort_order,id');
        $stmt->execute(['id'=>$versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function compositionScopes(int $versionId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, description, source_section_label, sort_order FROM military_position_composition_scopes WHERE catalog_version_id=:id ORDER BY sort_order,id');
        $stmt->execute(['id'=>$versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function organizationalElementTypes(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT o.id, o.code, o.name, o.short_name, o.sort_order '
            . 'FROM military_position_type_org_relations r '
            . 'JOIN organizational_element_types o ON o.id=r.organizational_element_type_id '
            . 'AND o.catalog_version_id=r.organizational_element_catalog_version_id '
            . 'WHERE r.catalog_version_id=:id ORDER BY o.sort_order,o.id'
        );
        $stmt->execute(['id'=>$versionId]);
        return $stmt->fetchAll();
    }

    /** @return array{items:list<array<string,mixed>>,total:int} */
    public function searchTypes(int $versionId, string $query='', string $family='', string $compositionScope='', ?int $tariffGrade=null, string $organizationalElement=''): array
    {
        $where=['t.catalog_version_id = ?'];
        $params=[$versionId];
        $query=trim($query);
        if ($query !== '') {
            $where[]='(LOCATE(?,t.name)>0 OR LOCATE(?,t.description)>0 OR EXISTS (SELECT 1 FROM military_position_variants v WHERE v.catalog_version_id=t.catalog_version_id AND v.position_type_id=t.id AND LOCATE(?,v.designation)>0))';
            array_push($params,$query,$query,$query);
        }
        if ($family !== '') {
            $where[]='EXISTS (SELECT 1 FROM military_position_type_families tf JOIN military_position_families f ON f.id=tf.family_id AND f.catalog_version_id=tf.catalog_version_id WHERE tf.catalog_version_id=t.catalog_version_id AND tf.position_type_id=t.id AND f.code=?)';
            $params[]=$family;
        }
        if ($compositionScope !== '') {
            $where[]='EXISTS (SELECT 1 FROM military_position_type_composition_scopes tc JOIN military_position_composition_scopes cs ON cs.id=tc.composition_scope_id AND cs.catalog_version_id=tc.catalog_version_id WHERE tc.catalog_version_id=t.catalog_version_id AND tc.position_type_id=t.id AND cs.code=?)';
            $params[]=$compositionScope;
        }
        if ($tariffGrade !== null) {
            $where[]='EXISTS (SELECT 1 FROM military_position_variants v JOIN military_position_source_entries se ON se.id=v.source_entry_id AND se.catalog_version_id=v.catalog_version_id WHERE v.catalog_version_id=t.catalog_version_id AND v.position_type_id=t.id AND se.tariff_grade=?)';
            $params[]=$tariffGrade;
        }
        if ($organizationalElement !== '') {
            $where[]='EXISTS (SELECT 1 FROM military_position_type_org_relations r JOIN organizational_element_types o ON o.id=r.organizational_element_type_id AND o.catalog_version_id=r.organizational_element_catalog_version_id WHERE r.catalog_version_id=t.catalog_version_id AND r.position_type_id=t.id AND o.code=?)';
            $params[]=$organizationalElement;
        }
        $sql='SELECT t.id,t.code,t.name,t.description,t.applicability_note,t.sort_order FROM military_position_types t WHERE '.implode(' AND ',$where).' ORDER BY t.sort_order,t.id';
        $stmt=$this->pdo->prepare($sql);
        $stmt->execute($params);
        $items=$stmt->fetchAll();
        return ['items'=>$items,'total'=>count($items)];
    }

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    public function variantsForTypes(int $versionId, array $ids): array
    {
        return $this->grouped($versionId,$ids,
            'SELECT v.position_type_id,v.id,v.code,v.designation,v.designation_kind,v.extraction_method,v.normalization_rule,v.normalization_note,v.service_context,v.sort_order,se.entry_code,se.tariff_grade,se.source_locator FROM military_position_variants v JOIN military_position_source_entries se ON se.id=v.source_entry_id AND se.catalog_version_id=v.catalog_version_id WHERE v.catalog_version_id=? AND v.position_type_id IN (%s) ORDER BY v.position_type_id,v.sort_order,v.id',
            'position_type_id');
    }

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    public function familiesForTypes(int $versionId, array $ids): array
    {
        return $this->grouped($versionId,$ids,
            'SELECT tf.position_type_id,f.id,f.code,f.name,tf.is_primary,tf.classification_basis,tf.context_note,tf.sort_order FROM military_position_type_families tf JOIN military_position_families f ON f.id=tf.family_id AND f.catalog_version_id=tf.catalog_version_id WHERE tf.catalog_version_id=? AND tf.position_type_id IN (%s) ORDER BY tf.position_type_id,tf.sort_order,f.id',
            'position_type_id');
    }

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    public function compositionScopesForTypes(int $versionId, array $ids): array
    {
        return $this->grouped($versionId,$ids,
            'SELECT tc.position_type_id,cs.id,cs.code,cs.name,cs.description,cs.source_section_label,tc.sort_order,GROUP_CONCAT(c.name ORDER BY m.sort_order SEPARATOR \' · \') AS member_names FROM military_position_type_composition_scopes tc JOIN military_position_composition_scopes cs ON cs.id=tc.composition_scope_id AND cs.catalog_version_id=tc.catalog_version_id JOIN military_position_composition_scope_members m ON m.composition_scope_id=cs.id AND m.catalog_version_id=cs.catalog_version_id JOIN military_personnel_compositions c ON c.id=m.composition_id AND c.catalog_version_id=m.rank_catalog_version_id WHERE tc.catalog_version_id=? AND tc.position_type_id IN (%s) GROUP BY tc.position_type_id,cs.id,cs.code,cs.name,cs.description,cs.source_section_label,tc.sort_order ORDER BY tc.position_type_id,tc.sort_order,cs.id',
            'position_type_id');
    }

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    public function organizationalContextsForTypes(int $versionId, array $ids): array
    {
        return $this->grouped($versionId,$ids,
            'SELECT r.position_type_id,o.id,o.code,o.name,o.short_name,r.relation_role,r.normalization_note,r.sort_order FROM military_position_type_org_relations r JOIN organizational_element_types o ON o.id=r.organizational_element_type_id AND o.catalog_version_id=r.organizational_element_catalog_version_id WHERE r.catalog_version_id=? AND r.position_type_id IN (%s) ORDER BY r.position_type_id,r.sort_order,o.id',
            'position_type_id');
    }

    /** @param list<int> $ids @return array<int,list<array<string,mixed>>> */
    private function grouped(int $versionId, array $ids, string $sqlTemplate, string $key): array
    {
        if ($ids===[]) return [];
        $ids=array_values(array_unique(array_map('intval',$ids)));
        $placeholders=implode(',',array_fill(0,count($ids),'?'));
        $stmt=$this->pdo->prepare(sprintf($sqlTemplate,$placeholders));
        $stmt->execute(array_merge([$versionId],$ids));
        $result=[];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row[$key]][]=$row;
        }
        return $result;
    }
}
