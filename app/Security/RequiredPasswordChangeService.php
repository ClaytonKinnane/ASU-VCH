<?php

declare(strict_types=1);

final class RequiredPasswordChangeService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{ok:bool,errors:array<string,string>} */
    public function change(int $userId, array $input): array
    {
        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmation = (string) ($input['new_password_confirmation'] ?? '');

        $errors = [];
        if ($currentPassword === '') {
            $errors['current_password'] = 'Укажите текущий пароль.';
        }
        if (strlen($newPassword) < 10 || strlen($newPassword) > 128 || preg_match('/\p{L}/u', $newPassword) !== 1 || preg_match('/\d/', $newPassword) !== 1) {
            $errors['new_password'] = 'Пароль должен содержать 10–128 символов, минимум одну букву и одну цифру.';
        }
        if (!hash_equals($newPassword, $confirmation)) {
            $errors['new_password_confirmation'] = 'Подтверждение пароля не совпадает.';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                'SELECT id, password_hash, is_active, approval_status, deleted_at, must_change_password '
                . 'FROM users WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();

            if (!$user || (int) $user['is_active'] !== 1 || $user['approval_status'] !== 'approved' || $user['deleted_at'] !== null) {
                $this->pdo->rollBack();
                return ['ok' => false, 'errors' => ['form' => 'Учетная запись больше недоступна. Выполните вход повторно.']];
            }
            if ((int) $user['must_change_password'] !== 1) {
                $this->pdo->rollBack();
                return ['ok' => false, 'errors' => ['form' => 'Обязательная смена пароля для этой учетной записи больше не требуется.']];
            }
            if (!password_verify($currentPassword, (string) $user['password_hash'])) {
                $this->pdo->rollBack();
                return ['ok' => false, 'errors' => ['current_password' => 'Текущий пароль указан неверно.']];
            }
            if (password_verify($newPassword, (string) $user['password_hash'])) {
                $this->pdo->rollBack();
                return ['ok' => false, 'errors' => ['new_password' => 'Новый пароль должен отличаться от текущего.']];
            }

            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $update = $this->pdo->prepare(
                'UPDATE users SET password_hash = :password_hash, must_change_password = 0, is_temporary = 0, updated_at = :updated_at WHERE id = :id'
            );
            $update->execute([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at' => $now,
                'id' => $userId,
            ]);
            $this->pdo->commit();

            return ['ok' => true, 'errors' => []];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
