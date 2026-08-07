<?php

declare(strict_types=1);

final class StaffingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string,mixed>> */
    public function listRegisters(string $query = '', string $status = '', ?int $structureId = null): array
    {
        $where = ['1=1'];
        $params = [];
        $query = mb_substr(trim($query), 0, 150, 'UTF-8');
        if ($query !== '') {
            $where[] = '(LOCATE(?, r.code) > 0 OR LOCATE(?, r.name) > 0)';
            $params[] = $query;
            $params[] = $query;
        }
        if (in_array($status, ['active', 'archived'], true)) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($structureId !== null) {
            $where[] = 'r.organizational_structure_id = ?';
            $params[] = $structureId;
        }

        $sql = 'SELECT r.id,r.code,r.name,r.note,r.status,r.revision,r.updated_at,'
            . 'o.display_name AS structure_name,'
            . 'av.id AS active_version_id,av.version_number AS active_version_number,av.version_label AS active_version_label,av.effective_from AS active_effective_from,'
            . 'pv.id AS pending_version_id,pv.version_number AS pending_version_number,pv.status AS pending_status '
            . 'FROM staffing_registers r '
            . 'JOIN organizational_structures o ON o.id=r.organizational_structure_id '
            . "LEFT JOIN staffing_versions av ON av.staffing_register_id=r.id AND av.status='active' "
            . "LEFT JOIN staffing_versions pv ON pv.staffing_register_id=r.id AND pv.status IN ('draft','approved') "
            . 'WHERE ' . implode(' AND ', $where) . ' ORDER BY r.status,r.name,r.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function organizationalStructures(): array
    {
        return $this->pdo->query(
            "SELECT id,code,display_name,short_name,status FROM organizational_structures "
            . "WHERE status='active' ORDER BY display_name,id"
        )->fetchAll();
    }

    /** @return array<string,mixed> */
    public function register(int $registerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*,o.code AS structure_code,o.display_name AS structure_name,o.status AS structure_status,'
            . 'av.id AS active_version_id,av.version_number AS active_version_number,av.version_label AS active_version_label,'
            . 'pv.id AS pending_version_id,pv.version_number AS pending_version_number,pv.status AS pending_status '
            . 'FROM staffing_registers r '
            . 'JOIN organizational_structures o ON o.id=r.organizational_structure_id '
            . "LEFT JOIN staffing_versions av ON av.staffing_register_id=r.id AND av.status='active' "
            . "LEFT JOIN staffing_versions pv ON pv.staffing_register_id=r.id AND pv.status IN ('draft','approved') "
            . 'WHERE r.id=:id LIMIT 1'
        );
        $stmt->execute(['id' => $registerId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new OutOfBoundsException('Штатный реестр не найден.');
        }
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function versions(int $registerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*,ov.version_number AS organization_version_number,ov.status AS organization_version_status,'
            . 'pc.code AS position_catalog_code,pc.name AS position_catalog_name,'
            . 'rc.code AS rank_catalog_code,rc.name AS rank_catalog_name,'
            . 'vc.code AS vus_catalog_code,vc.name AS vus_catalog_name,'
            . '(SELECT COUNT(*) FROM staffing_slots s WHERE s.staffing_version_id=v.id) AS slot_count,'
            . '(SELECT COUNT(*) FROM staffing_version_documents d WHERE d.staffing_version_id=v.id) AS document_count '
            . 'FROM staffing_versions v '
            . 'JOIN organizational_structure_versions ov ON ov.id=v.organizational_structure_version_id '
            . 'JOIN military_position_catalog_versions pc ON pc.id=v.position_catalog_version_id '
            . 'JOIN military_rank_catalog_versions rc ON rc.id=v.rank_catalog_version_id '
            . 'JOIN military_occupational_specialty_catalog_versions vc ON vc.id=v.vus_catalog_version_id '
            . 'WHERE v.staffing_register_id=:id ORDER BY v.version_number DESC,v.id DESC'
        );
        $stmt->execute(['id' => $registerId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed> */
    public function version(int $registerId, int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*,ov.version_number AS organization_version_number,ov.status AS organization_version_status,'
            . 'pc.code AS position_catalog_code,pc.name AS position_catalog_name,'
            . 'rc.code AS rank_catalog_code,rc.name AS rank_catalog_name,'
            . 'vc.code AS vus_catalog_code,vc.name AS vus_catalog_name '
            . 'FROM staffing_versions v '
            . 'JOIN organizational_structure_versions ov ON ov.id=v.organizational_structure_version_id '
            . 'JOIN military_position_catalog_versions pc ON pc.id=v.position_catalog_version_id '
            . 'JOIN military_rank_catalog_versions rc ON rc.id=v.rank_catalog_version_id '
            . 'JOIN military_occupational_specialty_catalog_versions vc ON vc.id=v.vus_catalog_version_id '
            . 'WHERE v.id=:version_id AND v.staffing_register_id=:register_id LIMIT 1'
        );
        $stmt->execute(['version_id' => $versionId, 'register_id' => $registerId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new OutOfBoundsException('Версия штатной структуры не найдена.');
        }
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function eligibleOrganizationVersions(int $structureId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id,version_number,status,effective_from,effective_to,revision "
            . "FROM organizational_structure_versions "
            . "WHERE organizational_structure_id=:id AND status IN ('active','superseded') "
            . 'ORDER BY version_number DESC,id DESC'
        );
        $stmt->execute(['id' => $structureId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed> */
    public function currentPositionCatalog(): array
    {
        $rows = $this->pdo->query(
            "SELECT id,code,name,rank_catalog_version_id,organizational_element_catalog_version_id "
            . "FROM military_position_catalog_versions WHERE status='published' "
            . 'ORDER BY valid_from DESC,id DESC LIMIT 2'
        )->fetchAll();
        if (count($rows) !== 1) {
            throw new RuntimeException('Текущая версия справочника воинских должностей не определена однозначно.');
        }
        return $rows[0];
    }

    /** @return array<string,mixed> */
    public function currentVusCatalog(): array
    {
        $rows = $this->pdo->query(
            "SELECT id,code,name FROM military_occupational_specialty_catalog_versions "
            . "WHERE status='published' AND valid_to IS NULL ORDER BY valid_from DESC,id DESC LIMIT 2"
        )->fetchAll();
        if (count($rows) !== 1) {
            throw new RuntimeException('Текущая версия справочника публичных сведений о ВУС не определена однозначно.');
        }
        return $rows[0];
    }

    /** @return list<array<string,mixed>> */
    public function organizationNodes(int $organizationVersionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT n.id,n.organizational_structure_element_id,n.parent_node_id,n.name,n.short_name,n.internal_code,n.sort_order,'
            . 't.name AS type_name,t.code AS type_code '
            . 'FROM organizational_structure_nodes n '
            . 'JOIN organizational_element_types t ON t.id=n.organizational_element_type_id '
            . 'WHERE n.structure_version_id=:id ORDER BY n.parent_node_id,n.sort_order,n.id'
        );
        $stmt->execute(['id' => $organizationVersionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function positionTypes(int $catalogVersionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id,code,name,description,sort_order FROM military_position_types '
            . 'WHERE catalog_version_id=:id ORDER BY sort_order,id'
        );
        $stmt->execute(['id' => $catalogVersionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function positionVariants(int $catalogVersionId, ?int $typeId = null): array
    {
        $sql = 'SELECT id,position_type_id,code,designation,sort_order FROM military_position_variants '
            . 'WHERE catalog_version_id=:version_id';
        $params = ['version_id' => $catalogVersionId];
        if ($typeId !== null) {
            $sql .= ' AND position_type_id=:type_id';
            $params['type_id'] = $typeId;
        }
        $sql .= ' ORDER BY position_type_id,sort_order,id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function ranks(int $catalogVersionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id,code,troop_name,naval_name,sort_order FROM military_rank_levels '
            . 'WHERE catalog_version_id=:id ORDER BY sort_order,id'
        );
        $stmt->execute(['id' => $catalogVersionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function vusDisclosures(int $catalogVersionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id,code,raw_identifier,qualification_name,identifier_kind,evidence_level,sort_order '
            . 'FROM military_occupational_specialty_public_disclosures '
            . 'WHERE catalog_version_id=:id ORDER BY sort_order,id'
        );
        $stmt->execute(['id' => $catalogVersionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function documents(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*,vd.document_role,vd.sort_order '
            . 'FROM staffing_version_documents vd '
            . 'JOIN staffing_documents d ON d.id=vd.document_id '
            . 'WHERE vd.staffing_version_id=:id ORDER BY vd.sort_order,d.id'
        );
        $stmt->execute(['id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed> */
    public function document(int $registerId, int $versionId, int $documentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*,vd.document_role,vd.sort_order '
            . 'FROM staffing_version_documents vd '
            . 'JOIN staffing_documents d ON d.id=vd.document_id '
            . 'WHERE vd.staffing_register_id=:register_id AND vd.staffing_version_id=:version_id '
            . 'AND vd.document_id=:document_id LIMIT 1'
        );
        $stmt->execute([
            'register_id' => $registerId,
            'version_id' => $versionId,
            'document_id' => $documentId,
        ]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new OutOfBoundsException('Документ-основание не найден.');
        }
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function slots(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*,n.name AS organizational_element_name,n.short_name AS organizational_element_short_name,'
            . 'n.parent_node_id,n.sort_order AS organization_sort_order,t.code AS position_type_code,t.name AS position_type_name,'
            . 'pv.designation AS position_variant_name,minr.troop_name AS minimum_rank_name,'
            . 'maxr.troop_name AS maximum_rank_name,prefr.troop_name AS preferred_rank_name,'
            . "GROUP_CONCAT(CONCAT(vr.requirement_role, ':', COALESCE(d.raw_identifier,d.qualification_name,d.code)) "
            . "ORDER BY vr.sort_order SEPARATOR ' · ') AS vus_summary "
            . 'FROM staffing_slots s '
            . 'JOIN organizational_structure_nodes n ON n.structure_version_id=s.organizational_structure_version_id '
            . 'AND n.organizational_structure_element_id=s.organizational_structure_element_id '
            . 'JOIN military_position_types t ON t.id=s.position_type_id '
            . 'LEFT JOIN military_position_variants pv ON pv.id=s.position_variant_id '
            . 'LEFT JOIN military_rank_levels minr ON minr.id=s.minimum_rank_id '
            . 'LEFT JOIN military_rank_levels maxr ON maxr.id=s.maximum_rank_id '
            . 'LEFT JOIN military_rank_levels prefr ON prefr.id=s.preferred_rank_id '
            . 'LEFT JOIN staffing_slot_vus_requirements vr ON vr.staffing_slot_id=s.id '
            . 'LEFT JOIN military_occupational_specialty_public_disclosures d ON d.id=vr.public_disclosure_id '
            . 'WHERE s.staffing_version_id=:id '
            . 'GROUP BY s.id,n.id,t.id,pv.id,minr.id,maxr.id,prefr.id '
            . 'ORDER BY n.parent_node_id,n.sort_order,s.sort_order,s.id'
        );
        $stmt->execute(['id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed> */
    public function slot(int $registerId, int $versionId, int $slotId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM staffing_slots WHERE id=:slot_id AND staffing_register_id=:register_id '
            . 'AND staffing_version_id=:version_id LIMIT 1'
        );
        $stmt->execute([
            'slot_id' => $slotId,
            'register_id' => $registerId,
            'version_id' => $versionId,
        ]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new OutOfBoundsException('Штатная позиция не найдена.');
        }
        $req = $this->pdo->prepare(
            'SELECT public_disclosure_id,requirement_role,sort_order FROM staffing_slot_vus_requirements '
            . 'WHERE staffing_slot_id=:id ORDER BY sort_order'
        );
        $req->execute(['id' => $slotId]);
        $row['vus_requirements'] = $req->fetchAll();
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function history(int $registerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*,u.display_name AS actor_name,v.version_number '
            . 'FROM staffing_change_events e '
            . 'LEFT JOIN users u ON u.id=e.actor_user_id '
            . 'LEFT JOIN staffing_versions v ON v.id=e.staffing_version_id '
            . 'WHERE e.staffing_register_id=:id ORDER BY e.created_at DESC,e.id DESC LIMIT 500'
        );
        $stmt->execute(['id' => $registerId]);
        return $stmt->fetchAll();
    }

    /** @return array{left:array<string,mixed>,right:array<string,mixed>,rows:list<array<string,mixed>>,left_documents:list<array<string,mixed>>,right_documents:list<array<string,mixed>>} */
    public function compare(int $registerId, int $leftVersionId, int $rightVersionId): array
    {
        $left = $this->version($registerId, $leftVersionId);
        $right = $this->version($registerId, $rightVersionId);
        $stmt = $this->pdo->prepare(
            'SELECT i.id AS identity_id,l.id AS left_slot_id,r.id AS right_slot_id,'
            . 'l.display_name AS left_name,r.display_name AS right_name,'
            . 'l.organizational_structure_element_id AS left_element_id,r.organizational_structure_element_id AS right_element_id,'
            . 'l.position_type_id AS left_position_type_id,r.position_type_id AS right_position_type_id,'
            . 'l.position_variant_id AS left_variant_id,r.position_variant_id AS right_variant_id,'
            . 'l.minimum_rank_id AS left_min_rank,r.minimum_rank_id AS right_min_rank,'
            . 'l.maximum_rank_id AS left_max_rank,r.maximum_rank_id AS right_max_rank,'
            . 'l.preferred_rank_id AS left_preferred_rank,r.preferred_rank_id AS right_preferred_rank,'
            . 'l.normative_state AS left_state,r.normative_state AS right_state,'
            . "(SELECT GROUP_CONCAT(CONCAT(vr.requirement_role, ':', vr.public_disclosure_id) ORDER BY vr.sort_order SEPARATOR '|') FROM staffing_slot_vus_requirements vr WHERE vr.staffing_slot_id=l.id) AS left_vus,"
            . "(SELECT GROUP_CONCAT(CONCAT(vr.requirement_role, ':', vr.public_disclosure_id) ORDER BY vr.sort_order SEPARATOR '|') FROM staffing_slot_vus_requirements vr WHERE vr.staffing_slot_id=r.id) AS right_vus "
            . 'FROM staffing_slot_identities i '
            . 'LEFT JOIN staffing_slots l ON l.staffing_slot_identity_id=i.id AND l.staffing_version_id=:left_id '
            . 'LEFT JOIN staffing_slots r ON r.staffing_slot_identity_id=i.id AND r.staffing_version_id=:right_id '
            . 'WHERE i.staffing_register_id=:register_id AND (l.id IS NOT NULL OR r.id IS NOT NULL) '
            . 'ORDER BY i.id'
        );
        $stmt->execute([
            'left_id' => $leftVersionId,
            'right_id' => $rightVersionId,
            'register_id' => $registerId,
        ]);
        return [
            'left' => $left,
            'right' => $right,
            'rows' => $stmt->fetchAll(),
            'left_documents' => $this->documents($leftVersionId),
            'right_documents' => $this->documents($rightVersionId),
        ];
    }
}
