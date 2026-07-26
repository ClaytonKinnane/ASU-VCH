<?php

declare(strict_types=1);

final class UserArchiveRestoreService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{ok:bool,username?:string,error?:string,errors?:array{reason?:string}}
     */
    public function archive(int $userId, int $actorId, string $reason): array
    {
        $validation = $this->validateReason($reason, 'архивирования');
        if ($validation['error'] !== null) {
            return ['ok' => false, 'errors' => ['reason' => $validation['error']]];
        }
        $reason = $validation['reason'];
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->pdo->beginTransaction();

            $select = $this->pdo->prepare(
                'SELECT id, username, is_active, approval_status, deleted_at '
                . 'FROM users WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $select->execute(['id' => $userId]);
            $user = $select->fetch();

            if (!$user) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись не найдена.'];
            }
            if ($user['deleted_at'] !== null) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись уже архивирована.'];
            }
            if ($userId === $actorId) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Нельзя архивировать собственную учетную запись.'];
            }

            if ((int) $user['is_active'] === 1 && $user['approval_status'] === 'approved') {
                $ownerRole = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM user_roles ur JOIN roles r ON r.id = ur.role_id "
                    . "WHERE ur.user_id = :user_id AND r.code = 'system_owner'"
                );
                $ownerRole->execute(['user_id' => $userId]);

                if ((int) $ownerRole->fetchColumn() > 0) {
                    $activeOwners = $this->pdo->query(
                        "SELECT u.id FROM users u "
                        . "JOIN user_roles ur ON ur.user_id = u.id "
                        . "JOIN roles r ON r.id = ur.role_id "
                        . "WHERE r.code = 'system_owner' AND u.is_active = 1 "
                        . "AND u.approval_status = 'approved' AND u.deleted_at IS NULL "
                        . 'ORDER BY u.id FOR UPDATE'
                    )->fetchAll();

                    if (count($activeOwners) <= 1) {
                        $this->pdo->rollBack();
                        return ['ok' => false, 'error' => 'Нельзя архивировать последнего активного владельца системы.'];
                    }
                }
            }

            $update = $this->pdo->prepare(
                'UPDATE users SET is_active = 0, deleted_at = :deleted_at, archived_by = :archived_by, '
                . 'last_archived_at = :last_archived_at, archive_reason = :archive_reason, '
                . 'restored_by = NULL, restored_at = NULL, restore_reason = NULL, updated_at = :updated_at '
                . 'WHERE id = :id'
            );
            $update->execute([
                'deleted_at' => $now,
                'archived_by' => $actorId,
                'last_archived_at' => $now,
                'archive_reason' => $reason,
                'updated_at' => $now,
                'id' => $userId,
            ]);

            $this->pdo->commit();
            return ['ok' => true, 'username' => (string) $user['username']];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * @return array{ok:bool,username?:string,error?:string,errors?:array{reason?:string}}
     */
    public function restore(int $userId, int $actorId, string $reason): array
    {
        $validation = $this->validateReason($reason, 'восстановления');
        if ($validation['error'] !== null) {
            return ['ok' => false, 'errors' => ['reason' => $validation['error']]];
        }
        $reason = $validation['reason'];
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->pdo->beginTransaction();

            $select = $this->pdo->prepare(
                'SELECT id, username, deleted_at FROM users WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $select->execute(['id' => $userId]);
            $user = $select->fetch();

            if (!$user) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись не найдена.'];
            }
            if ($user['deleted_at'] === null) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись не находится в архиве.'];
            }

            $update = $this->pdo->prepare(
                'UPDATE users SET deleted_at = NULL, is_active = 0, restored_by = :restored_by, '
                . 'restored_at = :restored_at, restore_reason = :restore_reason, updated_at = :updated_at '
                . 'WHERE id = :id'
            );
            $update->execute([
                'restored_by' => $actorId,
                'restored_at' => $now,
                'restore_reason' => $reason,
                'updated_at' => $now,
                'id' => $userId,
            ]);

            $this->pdo->commit();
            return ['ok' => true, 'username' => (string) $user['username']];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array{reason:string,error:?string} */
    private function validateReason(string $reason, string $operation): array
    {
        $reason = trim($reason);
        $validEncoding = mb_check_encoding($reason, 'UTF-8');
        $length = $validEncoding ? mb_strlen($reason, 'UTF-8') : 0;
        $invalidControls = $validEncoding
            && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $reason) === 1;

        if (!$validEncoding || $length < 10 || $length > 500 || $invalidControls) {
            return [
                'reason' => '',
                'error' => "Основание {$operation} должно содержать от 10 до 500 символов.",
            ];
        }

        return ['reason' => $reason, 'error' => null];
    }
}
