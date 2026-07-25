<?php

declare(strict_types=1);

final class UserRejectionService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{ok:bool,username?:string,error?:string,errors?:array{reason?:string}}
     */
    public function reject(int $userId, int $actorId, string $reason): array
    {
        $reason = trim($reason);
        $length = mb_strlen($reason, 'UTF-8');
        $hasInvalidControlCharacters = preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $reason) === 1;

        if (!mb_check_encoding($reason, 'UTF-8') || $length < 10 || $length > 500 || $hasInvalidControlCharacters) {
            return [
                'ok' => false,
                'errors' => [
                    'reason' => 'Основание отклонения должно содержать от 10 до 500 символов.',
                ],
            ];
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->pdo->beginTransaction();

            $select = $this->pdo->prepare(
                'SELECT id, username, approval_status, deleted_at FROM users WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $select->execute(['id' => $userId]);
            $user = $select->fetch();

            if (!$user || $user['deleted_at'] !== null) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись не найдена.'];
            }

            if ($user['approval_status'] !== 'pending') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись уже обработана.'];
            }

            $update = $this->pdo->prepare(
                "UPDATE users SET approval_status = 'rejected', is_active = 0, "
                . 'approved_by = NULL, approved_at = NULL, rejected_by = :rejected_by, '
                . 'rejected_at = :rejected_at, rejection_reason = :rejection_reason, updated_at = :updated_at '
                . 'WHERE id = :id'
            );
            $update->execute([
                'rejected_by' => $actorId,
                'rejected_at' => $now,
                'rejection_reason' => $reason,
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
}
