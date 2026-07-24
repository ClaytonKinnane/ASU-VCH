<?php

declare(strict_types=1);

final class UserCreateService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array{id:int,code:string,name:string,description:?string}> */
    public function availableRoles(bool $canAssignRoles, bool $isOwner): array
    {
        if (!$canAssignRoles) {
            return [];
        }
        $sql = "SELECT id, code, name, description FROM roles WHERE code IN ('system_owner','administrator','operator','viewer')";
        if (!$isOwner) {
            $sql .= " AND code <> 'system_owner'";
        }
        $sql .= ' ORDER BY FIELD(code,\'system_owner\',\'administrator\',\'operator\',\'viewer\')';
        return $this->pdo->query($sql)->fetchAll();
    }

    /** @return array{ok:bool,user_id?:int,username?:string,errors:array<string,string>} */
    public function create(array $input, int $actorId, bool $canAssignRoles, bool $isOwner): array
    {
        $username = trim((string) ($input['username'] ?? ''));
        $displayName = trim((string) ($input['display_name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $reason = trim((string) ($input['creation_reason'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $isTemporary = isset($input['is_temporary']) ? 1 : 0;
        $mustChange = isset($input['must_change_password']) ? 1 : 0;
        $roleIds = $input['role_ids'] ?? [];
        $roleIds = is_array($roleIds) ? array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn(int $id): bool => $id > 0))) : [];

        $errors = [];
        if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._-]{1,98}[A-Za-z0-9])?$/', $username)) {
            $errors['username'] = 'Логин должен содержать 3–100 символов: латинские буквы, цифры, точку, дефис или подчеркивание.';
        }
        if ($displayName === '' || mb_strlen($displayName, 'UTF-8') > 150 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $displayName)) {
            $errors['display_name'] = 'Укажите отображаемое имя длиной до 150 символов.';
        }
        if ($email !== '' && (mb_strlen($email, 'UTF-8') > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $errors['email'] = 'Укажите корректный email.';
        }
        $reasonLength = mb_strlen($reason, 'UTF-8');
        if ($reasonLength < 10 || $reasonLength > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $reason)) {
            $errors['creation_reason'] = 'Основание должно содержать от 10 до 500 символов.';
        }
        if (strlen($password) < 10 || strlen($password) > 128 || preg_match('/\p{L}/u', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            $errors['password'] = 'Пароль должен содержать 10–128 символов, минимум одну букву и одну цифру.';
        }
        if (!hash_equals($password, $confirmation)) {
            $errors['password_confirmation'] = 'Подтверждение пароля не совпадает.';
        }
        if ($roleIds !== [] && !$canAssignRoles) {
            $errors['role_ids'] = 'Недостаточно прав для назначения ролей.';
        }

        $roles = [];
        if ($roleIds !== []) {
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $stmt = $this->pdo->prepare("SELECT id, code FROM roles WHERE id IN ({$placeholders})");
            $stmt->execute($roleIds);
            $roles = $stmt->fetchAll();
            if (count($roles) !== count($roleIds)) {
                $errors['role_ids'] = 'Выбрана недоступная роль.';
            }
            foreach ($roles as $role) {
                if ($role['code'] === 'system_owner' && !$isOwner) {
                    $errors['role_ids'] = 'Роль владельца системы может назначить только действующий владелец.';
                }
            }
        }

        $usernameCanonical = mb_strtolower($username, 'UTF-8');
        $emailCanonical = $email === '' ? null : mb_strtolower($email, 'UTF-8');
        if ($errors === []) {
            $stmt = $this->pdo->prepare(
                'SELECT username_canonical, email_canonical FROM users '
                . 'WHERE username_canonical = :username '
                . 'OR (:email_check IS NOT NULL AND email_canonical = :email_value) LIMIT 1'
            );
            $stmt->execute([
                'username' => $usernameCanonical,
                'email_check' => $emailCanonical,
                'email_value' => $emailCanonical,
            ]);
            $duplicate = $stmt->fetch();
            if ($duplicate) {
                if ($duplicate['username_canonical'] === $usernameCanonical) {
                    $errors['username'] = 'Пользователь с таким логином уже существует.';
                } elseif ($emailCanonical !== null) {
                    $errors['email'] = 'Пользователь с таким email уже существует.';
                }
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, username_canonical, email, email_canonical, password_hash, display_name, is_active, is_temporary, must_change_password, created_at, updated_at, created_by, creation_reason, approval_status) '
                . "VALUES (:username, :username_canonical, :email, :email_canonical, :password_hash, :display_name, 0, :is_temporary, :must_change_password, :created_at, :updated_at, :created_by, :creation_reason, 'pending')"
            );
            $stmt->execute([
                'username' => $username,
                'username_canonical' => $usernameCanonical,
                'email' => $email === '' ? null : $email,
                'email_canonical' => $emailCanonical,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $displayName,
                'is_temporary' => $isTemporary,
                'must_change_password' => $mustChange,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => $actorId,
                'creation_reason' => $reason,
            ]);
            $userId = (int) $this->pdo->lastInsertId();
            if ($roles !== []) {
                $assign = $this->pdo->prepare('INSERT INTO user_roles (user_id, role_id, assigned_at, assigned_by) VALUES (:user_id, :role_id, :assigned_at, :assigned_by)');
                foreach ($roles as $role) {
                    $assign->execute(['user_id' => $userId, 'role_id' => (int) $role['id'], 'assigned_at' => $now, 'assigned_by' => $actorId]);
                }
            }
            $this->pdo->commit();
            return ['ok' => true, 'user_id' => $userId, 'username' => $username, 'errors' => []];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ((string) $exception->getCode() === '23000') {
                return ['ok' => false, 'errors' => ['form' => 'Пользователь с таким логином или email уже существует.']];
            }
            throw $exception;
        }
    }
}
