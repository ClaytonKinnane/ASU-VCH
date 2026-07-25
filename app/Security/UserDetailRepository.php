<?php

declare(strict_types=1);

final class UserDetailRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{user:array<string,mixed>,roles:list<array{id:int,code:string,name:string,description:?string}>}|null
     */
    public function find(int $userId, bool $includeSensitive): ?array
    {
        $select = 'SELECT u.id, u.username, u.display_name, u.is_active, u.is_temporary, '
            . 'u.must_change_password, u.approval_status, u.created_at, u.updated_at, u.deleted_at';
        $joins = '';

        if ($includeSensitive) {
            $select .= ', u.email, u.last_login_at, u.creation_reason, u.approved_at, '
                . 'creator.id AS creator_id, creator.username AS creator_username, creator.display_name AS creator_name, '
                . 'approver.id AS approver_id, approver.username AS approver_username, approver.display_name AS approver_name';
            $joins = ' LEFT JOIN users creator ON creator.id = u.created_by '
                . 'LEFT JOIN users approver ON approver.id = u.approved_by';
        }

        $stmt = $this->pdo->prepare($select . ' FROM users u' . $joins . ' WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $rolesStmt = $this->pdo->prepare(
            'SELECT r.id, r.code, r.name, r.description FROM roles r '
            . 'JOIN user_roles ur ON ur.role_id = r.id '
            . 'WHERE ur.user_id = :user_id ORDER BY FIELD(r.code,\'system_owner\',\'administrator\',\'operator\',\'viewer\'), r.name'
        );
        $rolesStmt->execute(['user_id' => $userId]);
        $roles = array_map(
            static fn (array $role): array => [
                'id' => (int) $role['id'],
                'code' => (string) $role['code'],
                'name' => (string) $role['name'],
                'description' => $role['description'] !== null ? (string) $role['description'] : null,
            ],
            $rolesStmt->fetchAll()
        );

        return ['user' => $user, 'roles' => $roles];
    }
}
