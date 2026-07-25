<?php

declare(strict_types=1);

final class UserApprovalService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{ok:bool,username?:string,error?:string} */
    public function approve(int $userId, int $actorId): array
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('SELECT id, username, approval_status, deleted_at FROM users WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            if (!$user || $user['deleted_at'] !== null) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись не найдена.'];
            }
            if ($user['approval_status'] !== 'pending') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'Учетная запись уже обработана.'];
            }
            $update = $this->pdo->prepare(
                "UPDATE users SET approval_status = 'approved', is_active = 1, approved_by = :approved_by, approved_at = :approved_at, updated_at = :updated_at WHERE id = :id"
            );
            $update->execute([
                'approved_by' => $actorId,
                'approved_at' => $now,
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
