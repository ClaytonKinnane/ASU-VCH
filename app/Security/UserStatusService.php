<?php

declare(strict_types=1);

final class UserStatusService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{ok:bool,username?:string,is_active?:bool,error?:string} */
    public function setActive(int $userId, bool $isActive): array
    {
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
            if ($target['approval_status'] !== 'approved') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Изменять активность можно только у подтвержденной учетной записи.'];
            }

            $currentActive = (int) $target['is_active'] === 1;
            if ($currentActive === $isActive) {
                $this->pdo->commit();
                return [
                    'ok' => true,
                    'username' => (string) $target['username'],
                    'is_active' => $isActive,
                ];
            }

            if (!$isActive && $currentActive) {
                $ownerStmt = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM user_roles ur JOIN roles r ON r.id = ur.role_id "
                    . "WHERE ur.user_id = :user_id AND r.code = 'system_owner'"
                );
                $ownerStmt->execute(['user_id' => $userId]);
                if ((int) $ownerStmt->fetchColumn() > 0) {
                    $ownersStmt = $this->pdo->query(
                        "SELECT u.id FROM users u "
                        . "JOIN user_roles ur ON ur.user_id = u.id "
                        . "JOIN roles r ON r.id = ur.role_id "
                        . "WHERE r.code = 'system_owner' AND u.is_active = 1 "
                        . "AND u.approval_status = 'approved' AND u.deleted_at IS NULL FOR UPDATE"
                    );
                    if (count($ownersStmt->fetchAll()) <= 1) {
                        $this->pdo->rollBack();
                        return ['ok' => false, 'error' => 'Нельзя заблокировать последнего активного владельца системы.'];
                    }
                }
            }

            $update = $this->pdo->prepare(
                'UPDATE users SET is_active = :is_active, updated_at = :updated_at WHERE id = :id'
            );
            $update->execute([
                'is_active' => $isActive ? 1 : 0,
                'updated_at' => $now,
                'id' => $userId,
            ]);

            $this->pdo->commit();
            return [
                'ok' => true,
                'username' => (string) $target['username'],
                'is_active' => $isActive,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
