<?php

declare(strict_types=1);

final class PersonnelRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{rows:list<array<string,mixed>>,total:int,page:int,per_page:int,pages:int} */
    public function listPersons(string $query, string $status, ?string $birthDate, int $page, int $perPage): array
    {
        $where = [];
        $params = [];
        if ($status === 'active' || $status === 'archived') {
            $where[] = 'p.record_status = :status';
            $params['status'] = $status;
        }
        if ($birthDate !== null) {
            $where[] = 'p.birth_date = :birth_date';
            $params['birth_date'] = $birthDate;
        }
        if ($query !== '') {
            $where[] = "(CONCAT_WS(' ', p.last_name, p.first_name, p.middle_name) LIKE :q OR EXISTS ("
                . 'SELECT 1 FROM personnel_identifiers qi WHERE qi.personnel_id = p.id AND qi.value LIKE :q_identifier'
                . '))';
            $like = '%' . $query . '%';
            $params['q'] = $like;
            $params['q_identifier'] = $like;
        }
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $count = $this->pdo->prepare('SELECT COUNT(*) FROM personnel_records p' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*,
            (SELECT i.value FROM personnel_identifiers i JOIN personnel_identifier_types t ON t.id=i.identifier_type_id WHERE i.personnel_id=p.id AND i.valid_to IS NULL AND t.code='personal_number' LIMIT 1) AS personal_number,
            (SELECT i.value FROM personnel_identifiers i JOIN personnel_identifier_types t ON t.id=i.identifier_type_id WHERE i.personnel_id=p.id AND i.valid_to IS NULL AND t.code='service_dog_tag' LIMIT 1) AS service_dog_tag,
            (SELECT i.value FROM personnel_identifiers i JOIN personnel_identifier_types t ON t.id=i.identifier_type_id WHERE i.personnel_id=p.id AND i.valid_to IS NULL AND t.code='table_number' LIMIT 1) AS table_number,
            (SELECT i.value FROM personnel_identifiers i JOIN personnel_identifier_types t ON t.id=i.identifier_type_id WHERE i.personnel_id=p.id AND i.valid_to IS NULL AND t.code='call_sign' LIMIT 1) AS call_sign
            FROM personnel_records p" . $whereSql
            . " ORDER BY CASE WHEN p.record_status='active' THEN 0 ELSE 1 END, p.last_name, p.first_name, p.middle_name, p.id LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }

    /** @return array<string,mixed>|null */
    public function person(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM personnel_records WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function identifierTypes(): array
    {
        return $this->pdo->query(
            'SELECT id, code, name, description, enforce_global_unique, sort_order FROM personnel_identifier_types ORDER BY sort_order, id'
        )->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function identifiers(int $personnelId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, t.code AS type_code, t.name AS type_name, t.description AS type_description '
            . 'FROM personnel_identifiers i JOIN personnel_identifier_types t ON t.id=i.identifier_type_id '
            . 'WHERE i.personnel_id=:personnel_id '
            . 'ORDER BY CASE WHEN i.valid_to IS NULL THEN 0 ELSE 1 END, t.sort_order, COALESCE(i.valid_from, DATE(i.created_at)) DESC, i.id DESC'
        );
        $stmt->execute(['personnel_id' => $personnelId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> keyed by identifier type id */
    public function activeIdentifiersByType(int $personnelId): array
    {
        $result = [];
        foreach ($this->identifiers($personnelId) as $row) {
            if ($row['valid_to'] === null) {
                $result[(int) $row['identifier_type_id']] = $row;
            }
        }
        return $result;
    }

    /** @return list<array<string,mixed>> */
    public function history(int $personnelId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, u.display_name AS actor_display_name FROM personnel_change_events e '
            . 'LEFT JOIN users u ON u.id=e.actor_user_id WHERE e.personnel_id=:personnel_id '
            . 'ORDER BY e.occurred_at DESC, e.id DESC'
        );
        $stmt->execute(['personnel_id' => $personnelId]);
        return $stmt->fetchAll();
    }
}
