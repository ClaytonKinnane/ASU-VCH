<?php

declare(strict_types=1);

final class UserRoleUpdateService
{
    private const SYSTEM_ROLE_CODES = ['system_owner', 'administrator', 'operator', 'viewer'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array{id:int,code:string,name:string,description:?string}> */
    public function availableRoles(bool $actorIsOwner): array
    {
        $sql = "SELECT id, code, name, description FROM roles WHERE code IN ('system_owner','administrator','operator','viewer')";
        if (!$actorIsOwner) {
            $sql .= " AND code <> 'system_owner'";
        }
        $sql .= " ORDER BY FIELD(code,'system_owner','administrator','operator','viewer')";

        return array_map(
            static fn (array $role): array => [
                'id' => (int) $role['id'],
                'code' => (string) $role['code'],
                'name' => (string) $role['name'],
                'description' => $role['description'] !== null ? (string) $role['description'] : null,
            ],
            $this->pdo->query($sql)->fetchAll()
        );
    }

    /** @return array{ok:bool,username?:string,error?:string} */
    public function update(int $userId, array $requestedRoleIds, int $actorId, bool $actorIsOwner): array
    {
        $requestedRoleIds = array_values(array_unique(array_filter(
            array_map('intval', $requestedRoleIds),
            static fn (int $id): bool => $id > 0
        )));
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->pdo->beginTransaction();

            $targetStmt = $this->pdo->prepare(
                'SELECT id, username, is_active, approval_status, deleted_at FROM users WHERE id = :id FOR UPDATE'
            );
            $targetStmt->execute(['id' => $userId]);
            $target = $targetStmt->fetch();
            if (!$target || $target['deleted_at'] !== null) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись не найдена или архивирована.'];
            }

            $allRoles = [];
            $rolesStmt = $this->pdo->query(
                "SELECT id, code FROM roles WHERE code IN ('system_owner','administrator','operator','viewer') FOR UPDATE"
            );
            foreach ($rolesStmt->fetchAll() as $role) {
                $allRoles[(int) $role['id']] = (string) $role['code'];
            }

            foreach ($requestedRoleIds as $roleId) {
                if (!isset($allRoles[$roleId])) {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => 'Выбрана недоступная роль.'];
                }
                if ($allRoles[$roleId] === 'system_owner' && !$actorIsOwner) {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => 'Роль владельца системы может изменять только действующий владелец.'];
                }
            }

            $currentStmt = $this->pdo->prepare(
                'SELECT r.id, r.code FROM roles r JOIN user_roles ur ON ur.role_id = r.id '
                . 'WHERE ur.user_id = :user_id FOR UPDATE'
            );
            $currentStmt->execute(['user_id' => $userId]);
            $currentRoles = [];
            $currentOwnerRoleId = null;
            foreach ($currentStmt->fetchAll() as $role) {
                $roleId = (int) $role['id'];
                $currentRoles[$roleId] = (string) $role['code'];
                if ($role['code'] === 'system_owner') {
                    $currentOwnerRoleId = $roleId;
                }
            }

            $desiredRoleIds = $requestedRoleIds;
            foreach ($currentRoles as $roleId => $roleCode) {
                if (!in_array($roleCode, self::SYSTEM_ROLE_CODES, true)) {
                    $desiredRoleIds[] = $roleId;
                }
            }
            if (!$actorIsOwner && $currentOwnerRoleId !== null) {
                $desiredRoleIds[] = $currentOwnerRoleId;
            }
            $desiredRoleIds = array_values(array_unique($desiredRoleIds));

            $removesOwner = $currentOwnerRoleId !== null && !in_array($currentOwnerRoleId, $desiredRoleIds, true);
            if ($removesOwner && (int) $target['is_active'] === 1 && $target['approval_status'] === 'approved') {
                $ownersStmt = $this->pdo->query(
                    "SELECT u.id FROM users u "
                    . "JOIN user_roles ur ON ur.user_id = u.id "
                    . "JOIN roles r ON r.id = ur.role_id "
                    . "WHERE r.code = 'system_owner' AND u.is_active = 1 "
                    . "AND u.approval_status = 'approved' AND u.deleted_at IS NULL FOR UPDATE"
                );
                if (count($ownersStmt->fetchAll()) <= 1) {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => 'Нельзя снять роль у последнего активного владельца системы.'];
                }
            }

            $currentRoleIds = array_keys($currentRoles);
            $toDelete = array_values(array_diff($currentRoleIds, $desiredRoleIds));
            $toInsert = array_values(array_diff($desiredRoleIds, $currentRoleIds));

            if ($toDelete !== []) {
                $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
                $delete = $this->pdo->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id IN ({$placeholders})");
                $delete->execute(array_merge([$userId], $toDelete));
            }

            if ($toInsert !== []) {
                $insert = $this->pdo->prepare(
                    'INSERT INTO user_roles (user_id, role_id, assigned_at, assigned_by) '
                    . 'VALUES (:user_id, :role_id, :assigned_at, :assigned_by)'
                );
                foreach ($toInsert as $roleId) {
                    $insert->execute([
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'assigned_at' => $now,
                        'assigned_by' => $actorId,
                    ]);
                }
            }

            if ($toDelete !== [] || $toInsert !== []) {
                $touch = $this->pdo->prepare('UPDATE users SET updated_at = :updated_at WHERE id = :id');
                $touch->execute(['updated_at' => $now, 'id' => $userId]);
            }

            $this->pdo->commit();
            return ['ok' => true, 'username' => (string) $target['username']];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
