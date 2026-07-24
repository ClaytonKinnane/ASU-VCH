<?php

declare(strict_types=1);

final class BootstrapOwnerService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{user_id:int, created:bool}
     */
    public function createOwner(
        string $username,
        string $displayName,
        string $password,
        ?string $email = null,
        bool $isTemporary = false,
        bool $mustChangePassword = false
    ): array {
        $username = trim($username);
        $displayName = trim($displayName);
        $email = $email !== null ? trim($email) : null;

        if ($username === '' || mb_strlen($username, 'UTF-8') < 3) {
            throw new InvalidArgumentException('Имя пользователя должно содержать не менее 3 символов.');
        }
        if ($displayName === '') {
            throw new InvalidArgumentException('Укажите отображаемое имя.');
        }
        if (strlen($password) < 5) {
            throw new InvalidArgumentException('Пароль должен содержать не менее 5 символов.');
        }

        $lockAcquired = false;

        try {
            $lockAcquired = (int) $this->pdo
                ->query("SELECT GET_LOCK('asu_vch_first_owner', 10)")
                ->fetchColumn() === 1;

            if (!$lockAcquired) {
                throw new RuntimeException('Не удалось получить блокировку первичной регистрации.');
            }

            $this->pdo->beginTransaction();

            $completedStmt = $this->pdo->prepare(
                "SELECT setting_value FROM system_settings WHERE setting_key = 'installation_completed' LIMIT 1 FOR UPDATE"
            );
            $completedStmt->execute();
            $completed = $completedStmt->fetchColumn() === '1';

            $userCount = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($completed || $userCount > 0) {
                throw new RuntimeException('Первичная регистрация уже отключена.');
            }

            $roleId = (int) $this->pdo
                ->query("SELECT id FROM roles WHERE code = 'system_owner' LIMIT 1")
                ->fetchColumn();
            if ($roleId < 1) {
                throw new RuntimeException('Системная роль не подготовлена. Запустите установку БД.');
            }

            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $stmt = $this->pdo->prepare(
                'INSERT INTO users '
                . '(username, username_canonical, email, email_canonical, password_hash, display_name, '
                . 'is_active, is_temporary, must_change_password, created_at, updated_at) '
                . 'VALUES (:username, :username_canonical, :email, :email_canonical, :password_hash, :display_name, '
                . '1, :is_temporary, :must_change_password, :created_at, :updated_at)'
            );
            $stmt->execute([
                'username' => $username,
                'username_canonical' => mb_strtolower($username, 'UTF-8'),
                'email' => $email !== '' ? $email : null,
                'email_canonical' => $email !== null && $email !== '' ? mb_strtolower($email, 'UTF-8') : null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $displayName,
                'is_temporary' => $isTemporary ? 1 : 0,
                'must_change_password' => $mustChangePassword ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $userId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (:user_id, :role_id, :assigned_at)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_at' => $now,
            ]);

            $stmt = $this->pdo->prepare(
                "UPDATE system_settings SET setting_value = '1', updated_at = :updated_at "
                . "WHERE setting_key = 'installation_completed'"
            );
            $stmt->execute(['updated_at' => $now]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Не удалось зафиксировать завершение первичной установки.');
            }

            $this->pdo->commit();

            return ['user_id' => $userId, 'created' => true];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        } finally {
            if ($lockAcquired) {
                try {
                    $this->pdo->query("SELECT RELEASE_LOCK('asu_vch_first_owner')");
                } catch (Throwable) {
                }
            }
        }
    }
}
