<?php

declare(strict_types=1);

final class UserListRepository
{
    private const PER_PAGE = 25;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{items:list<array<string,mixed>>,total:int,page:int,total_pages:int,per_page:int} */
    public function search(string $query, string $status, int $requestedPage, bool $includeSensitive): array
    {
        $conditions = [];
        $params = [];
        if ($query !== '') {
            $escaped = $this->escapeLike($query);
            $conditions[] = "(u.username LIKE :search_username ESCAPE '\\\\' OR u.display_name LIKE :search_display_name ESCAPE '\\\\' OR u.email LIKE :search_email ESCAPE '\\\\')";
            $params = ['search_username' => "%{$escaped}%", 'search_display_name' => "%{$escaped}%", 'search_email' => "%{$escaped}%"];
        }
        $statusCondition = match ($status) {
            'active' => "u.is_active = 1 AND u.approval_status = 'approved' AND u.deleted_at IS NULL",
            'pending' => "u.approval_status = 'pending' AND u.deleted_at IS NULL",
            'blocked' => "u.is_active = 0 AND u.approval_status = 'approved' AND u.deleted_at IS NULL",
            'archived' => 'u.deleted_at IS NOT NULL',
            'temporary' => 'u.is_temporary = 1 AND u.deleted_at IS NULL',
            'password_change' => 'u.must_change_password = 1 AND u.deleted_at IS NULL',
            default => null,
        };
        if ($statusCondition !== null) {
            $conditions[] = $statusCondition;
        }
        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $countStmt = $this->pdo->prepare('SELECT COUNT(DISTINCT u.id) FROM users u' . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $requestedPage), $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;
        $sensitiveFields = $includeSensitive ? 'u.email, u.last_login_at,' : 'NULL AS email, NULL AS last_login_at,';
        $sql = 'SELECT u.id, u.username, u.display_name, ' . $sensitiveFields
            . ' u.is_active, u.is_temporary, u.must_change_password, u.deleted_at, u.approval_status, u.created_at, '
            . 'creator.display_name AS creator_name, creator.username AS creator_username, '
            . "GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS role_names, "
            . "MAX(CASE WHEN r.code = 'system_owner' THEN 1 ELSE 0 END) AS is_owner "
            . 'FROM users u LEFT JOIN users creator ON creator.id = u.created_by '
            . 'LEFT JOIN user_roles ur ON ur.user_id = u.id LEFT JOIN roles r ON r.id = ur.role_id'
            . $where
            . ' GROUP BY u.id, u.username, u.display_name, u.email, u.last_login_at, u.is_active, u.is_temporary, u.must_change_password, u.deleted_at, u.approval_status, u.created_at, creator.display_name, creator.username '
            . "ORDER BY (u.approval_status = 'pending') DESC, is_owner DESC, (u.is_active = 1 AND u.deleted_at IS NULL) DESC, u.username_canonical ASC, u.id ASC "
            . 'LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'total_pages' => $totalPages, 'per_page' => self::PER_PAGE];
    }

    /** @return array{total:int,active:int,pending:int,blocked:int,archived:int} */
    public function summary(): array
    {
        $row = $this->pdo->query(
            "SELECT COUNT(*) AS total, "
            . "SUM(CASE WHEN is_active = 1 AND approval_status = 'approved' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS active, "
            . "SUM(CASE WHEN approval_status = 'pending' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS pending, "
            . "SUM(CASE WHEN is_active = 0 AND approval_status = 'approved' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS blocked, "
            . 'SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS archived FROM users'
        )->fetch();
        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'blocked' => (int) ($row['blocked'] ?? 0),
            'archived' => (int) ($row['archived'] ?? 0),
        ];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
