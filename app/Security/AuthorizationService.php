<?php

declare(strict_types=1);

final class AuthorizationService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<string>
     */
    public function roleCodesForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.code FROM roles r '
            . 'JOIN user_roles ur ON ur.role_id = r.id '
            . 'WHERE ur.user_id = :user_id ORDER BY r.code'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    }

    /**
     * @return list<string>
     */
    public function permissionCodesForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT p.code FROM permissions p '
            . 'JOIN role_permissions rp ON rp.permission_id = p.id '
            . 'JOIN user_roles ur ON ur.role_id = rp.role_id '
            . 'WHERE ur.user_id = :user_id ORDER BY p.code'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->permissionCodesForUser($userId);

        return in_array('system.*.*', $permissions, true)
            || in_array($permission, $permissions, true);
    }
}
