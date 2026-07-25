<?php

declare(strict_types=1);

final class UserUpdateService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{ok:bool,username?:string,errors:array<string,string>} */
    public function update(int $userId, array $input): array
    {
        $username = trim((string) ($input['username'] ?? ''));
        $displayName = trim((string) ($input['display_name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $isTemporary = isset($input['is_temporary']) ? 1 : 0;
        $mustChangePassword = isset($input['must_change_password']) ? 1 : 0;

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
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $usernameCanonical = mb_strtolower($username, 'UTF-8');
        $emailCanonical = $email === '' ? null : mb_strtolower($email, 'UTF-8');
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->pdo->beginTransaction();

            $targetStmt = $this->pdo->prepare('SELECT id, deleted_at FROM users WHERE id = :id FOR UPDATE');
            $targetStmt->execute(['id' => $userId]);
            $target = $targetStmt->fetch();
            if (!$target) {
                $this->pdo->rollBack();
                return ['ok' => false, 'errors' => ['form' => 'Учетная запись не найдена.']];
            }
            if ($target['deleted_at'] !== null) {
                $this->pdo->rollBack();
                return ['ok' => false, 'errors' => ['form' => 'Архивированная учетная запись недоступна для редактирования.']];
            }

            $duplicateStmt = $this->pdo->prepare(
                'SELECT username_canonical, email_canonical FROM users '
                . 'WHERE id <> :excluded_id AND (username_canonical = :username_value '
                . 'OR (:email_check IS NOT NULL AND email_canonical = :email_value)) LIMIT 1'
            );
            $duplicateStmt->execute([
                'excluded_id' => $userId,
                'username_value' => $usernameCanonical,
                'email_check' => $emailCanonical,
                'email_value' => $emailCanonical,
            ]);
            $duplicate = $duplicateStmt->fetch();
            if ($duplicate) {
                if ($duplicate['username_canonical'] === $usernameCanonical) {
                    $errors['username'] = 'Пользователь с таким логином уже существует.';
                }
                if ($emailCanonical !== null && $duplicate['email_canonical'] === $emailCanonical) {
                    $errors['email'] = 'Пользователь с таким email уже существует.';
                }
                $this->pdo->rollBack();
                return ['ok' => false, 'errors' => $errors];
            }

            $update = $this->pdo->prepare(
                'UPDATE users SET username = :username, username_canonical = :username_canonical, '
                . 'display_name = :display_name, email = :email, email_canonical = :email_canonical, '
                . 'is_temporary = :is_temporary, must_change_password = :must_change_password, updated_at = :updated_at '
                . 'WHERE id = :id'
            );
            $update->execute([
                'username' => $username,
                'username_canonical' => $usernameCanonical,
                'display_name' => $displayName,
                'email' => $email === '' ? null : $email,
                'email_canonical' => $emailCanonical,
                'is_temporary' => $isTemporary,
                'must_change_password' => $mustChangePassword,
                'updated_at' => $now,
                'id' => $userId,
            ]);

            $this->pdo->commit();
            return ['ok' => true, 'username' => $username, 'errors' => []];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ((string) $exception->getCode() === '23000') {
                return ['ok' => false, 'errors' => ['form' => 'Пользователь с таким логином или email уже существует.']];
            }
            throw $exception;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
