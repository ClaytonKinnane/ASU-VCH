<?php

declare(strict_types=1);

final class MilitaryOccupationalSpecialtyCatalogRepository
{
    /** @var array<string,mixed>|null */
    private ?array $currentVersion = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function shouldSearchPublicDisclosures(string $recordType, string $organization): bool
    {
        return $recordType !== 'training-program' && $organization === '';
    }

    /** @return array<string,mixed> */
    public function currentVersion(): array
    {
        if ($this->currentVersion !== null) {
            return $this->currentVersion;
        }

        $rows = $this->pdo->query(
            "SELECT id, code, name, coverage_note, status, valid_from, valid_to, verified_at, created_at "
            . "FROM military_occupational_specialty_catalog_versions "
            . "WHERE status = 'published' AND valid_to IS NULL "
            . "ORDER BY valid_from DESC, id DESC LIMIT 2"
        )->fetchAll();

        if (count($rows) !== 1) {
            throw new RuntimeException(
                'Текущая версия справочника публичных сведений о ВУС не определена однозначно.'
            );
        }

        $this->currentVersion = $rows[0];
        return $this->currentVersion;
    }

    /** @return list<array<string,mixed>> */
    public function versionLegalSources(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.code, s.document_type, s.document_date, s.document_number, '
            . 's.title, s.provision, s.official_url, s.verified_at, '
            . 'x.source_role, x.provision_detail, x.verified_at_snapshot, x.sort_order '
            . 'FROM military_occupational_specialty_catalog_version_legal_sources x '
            . 'JOIN legal_sources s ON s.id = x.legal_source_id '
            . 'WHERE x.catalog_version_id = :version_id '
            . 'ORDER BY x.sort_order, s.id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function officialSourceSnapshots(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, publisher_name, publisher_type, title, source_url, published_on, '
            . 'verified_at, source_status, source_locator, evidence_summary, evidence_fingerprint, sort_order '
            . 'FROM military_occupational_specialty_official_source_snapshots '
            . 'WHERE catalog_version_id = :version_id ORDER BY sort_order, id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function codeSegments(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, position_from, position_to, character_class, description, '
            . 'source_locator, sort_order '
            . 'FROM military_occupational_specialty_code_segments '
            . 'WHERE catalog_version_id = :version_id ORDER BY sort_order, id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function publicContextDomains(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, description, coverage_note, source_locator, sort_order '
            . 'FROM military_occupational_specialty_public_context_domains '
            . 'WHERE catalog_version_id = :version_id ORDER BY sort_order, id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function personnelScopes(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, description, sort_order '
            . 'FROM military_occupational_specialty_personnel_scopes '
            . 'WHERE catalog_version_id = :version_id ORDER BY sort_order, id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function trainingOrganizations(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.code, o.name, o.organization_type, o.official_site, o.sort_order, '
            . 's.code AS source_code, s.source_url '
            . 'FROM military_occupational_specialty_training_organizations o '
            . 'JOIN military_occupational_specialty_official_source_snapshots s '
            . 'ON s.id = o.official_source_snapshot_id AND s.catalog_version_id = o.catalog_version_id '
            . 'WHERE o.catalog_version_id = :version_id ORDER BY o.sort_order, o.id'
        );
        $stmt->execute(['version_id' => $versionId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public function searchPublicDisclosures(
        int $versionId,
        string $query = '',
        string $disclosureType = '',
        string $identifierKind = '',
        string $personnelScope = '',
        string $source = '',
        string $evidenceLevel = '',
        string $currencyStatus = ''
    ): array {
        $where = ['d.catalog_version_id = ?'];
        $params = [$versionId];
        $query = mb_substr(trim($query), 0, 150, 'UTF-8');

        if ($query !== '') {
            $where[] = '(LOCATE(?, COALESCE(d.raw_identifier, \'\')) > 0 '
                . 'OR LOCATE(?, COALESCE(d.qualification_name, \'\')) > 0 '
                . 'OR LOCATE(?, d.evidence_summary) > 0)';
            array_push($params, $query, $query, $query);
        }
        if ($disclosureType !== '') {
            $where[] = 'd.disclosure_type = ?';
            $params[] = $disclosureType;
        }
        if ($identifierKind !== '') {
            $where[] = 'd.identifier_kind = ?';
            $params[] = $identifierKind;
        }
        if ($personnelScope !== '') {
            $where[] = 'ps.code = ?';
            $params[] = $personnelScope;
        }
        if ($source !== '') {
            $where[] = 's.code = ?';
            $params[] = $source;
        }
        if ($evidenceLevel !== '') {
            $where[] = 'd.evidence_level = ?';
            $params[] = $evidenceLevel;
        }
        if ($currencyStatus !== '') {
            $where[] = 'd.currency_status = ?';
            $params[] = $currencyStatus;
        }

        $sql = 'SELECT d.id, d.code, d.disclosure_type, d.identifier_kind, d.raw_identifier, '
            . 'd.specialty_number, d.position_code, d.special_sign, d.qualification_name, '
            . 'd.service_context, d.applicability_note, d.currency_status, d.evidence_level, '
            . 'd.source_locator, d.evidence_summary, d.sort_order, '
            . 'ps.code AS personnel_scope_code, ps.name AS personnel_scope_name, '
            . 's.code AS source_code, s.title AS source_title, s.official_url AS source_url '
            . 'FROM military_occupational_specialty_public_disclosures d '
            . 'LEFT JOIN military_occupational_specialty_personnel_scopes ps '
            . 'ON ps.id = d.personnel_scope_id AND ps.catalog_version_id = d.catalog_version_id '
            . 'JOIN legal_sources s ON s.id = d.legal_source_id '
            . 'WHERE ' . implode(' AND ', $where)
            . ' ORDER BY d.sort_order, d.id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return ['items' => $items, 'total' => count($items)];
    }

    /**
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public function searchTrainingPrograms(
        int $versionId,
        string $query = '',
        string $identifierKind = '',
        string $personnelScope = '',
        string $organization = '',
        string $source = '',
        string $evidenceLevel = '',
        string $programStatus = ''
    ): array {
        $where = ['p.catalog_version_id = ?'];
        $params = [$versionId];
        $query = mb_substr(trim($query), 0, 150, 'UTF-8');

        if ($query !== '') {
            $where[] = '(LOCATE(?, COALESCE(p.raw_identifier, \'\')) > 0 '
                . 'OR LOCATE(?, COALESCE(p.qualification_name, \'\')) > 0 '
                . 'OR LOCATE(?, p.program_name) > 0 '
                . 'OR LOCATE(?, p.source_phrase) > 0)';
            array_push($params, $query, $query, $query, $query);
        }
        if ($identifierKind !== '') {
            $where[] = 'p.identifier_kind = ?';
            $params[] = $identifierKind;
        }
        if ($personnelScope !== '') {
            $where[] = 'ps.code = ?';
            $params[] = $personnelScope;
        }
        if ($organization !== '') {
            $where[] = 'o.code = ?';
            $params[] = $organization;
        }
        if ($source !== '') {
            $where[] = 'ss.code = ?';
            $params[] = $source;
        }
        if ($evidenceLevel !== '') {
            $where[] = 'p.evidence_level = ?';
            $params[] = $evidenceLevel;
        }
        if ($programStatus !== '') {
            $where[] = 'p.program_status = ?';
            $params[] = $programStatus;
        }

        $sql = 'SELECT p.id, p.code, p.raw_identifier, p.identifier_kind, p.specialty_number, '
            . 'p.position_code, p.special_sign, p.qualification_name, p.program_name, p.program_kind, '
            . 'p.personnel_category_raw, p.service_context_raw, p.source_phrase, p.evidence_level, '
            . 'p.evidence_summary, p.program_status, p.published_on, p.valid_from, p.valid_to, '
            . 'p.verified_at, p.coverage_note, p.sort_order, '
            . 'ps.code AS personnel_scope_code, ps.name AS personnel_scope_name, '
            . 'o.code AS organization_code, o.name AS organization_name, o.official_site, '
            . 'ss.code AS source_code, ss.title AS source_title, ss.source_url '
            . 'FROM military_occupational_specialty_training_programs p '
            . 'JOIN military_occupational_specialty_personnel_scopes ps '
            . 'ON ps.id = p.personnel_scope_id AND ps.catalog_version_id = p.catalog_version_id '
            . 'JOIN military_occupational_specialty_training_organizations o '
            . 'ON o.id = p.organization_id AND o.catalog_version_id = p.catalog_version_id '
            . 'JOIN military_occupational_specialty_official_source_snapshots ss '
            . 'ON ss.id = p.official_source_snapshot_id AND ss.catalog_version_id = p.catalog_version_id '
            . 'WHERE ' . implode(' AND ', $where)
            . ' ORDER BY p.sort_order, p.id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return ['items' => $items, 'total' => count($items)];
    }
}
