<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$app = require $root . '/config/app.php';
$localFile = $root . '/config/local.php';
if (!is_file($localFile)) {
    fwrite(STDERR, "Не найден config/local.php.\n");
    exit(1);
}

require_once $root . '/app/Security/UserArchiveRestoreService.php';
require_once $root . '/app/Security/UserApprovalService.php';
require_once $root . '/app/Security/UserRejectionService.php';
require_once $root . '/app/Security/UserStatusService.php';
require_once $root . '/app/Security/UserUpdateService.php';
require_once $root . '/app/Security/UserRoleUpdateService.php';
require_once $root . '/app/Security/UserListRepository.php';

$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];
$fixtureIds = [];
$exitCode = 0;

function archive_restore_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function archive_restore_insert_fixture(
    PDO $pdo,
    int $actorId,
    int $viewerRoleId,
    string $suffix,
    string $approvalStatus,
    bool $isActive
): array {
    $username = 'archive_check_' . $suffix . '_' . bin2hex(random_bytes(4));
    $email = $username . '@example.test';
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $passwordHash = password_hash('ArchiveRestore123', PASSWORD_DEFAULT);
    $approvedBy = $approvalStatus === 'approved' ? $actorId : null;
    $approvedAt = $approvalStatus === 'approved' ? $now : null;
    $rejectedBy = $approvalStatus === 'rejected' ? $actorId : null;
    $rejectedAt = $approvalStatus === 'rejected' ? $now : null;
    $rejectionReason = $approvalStatus === 'rejected'
        ? 'Исходное тестовое решение об отклонении учетной записи.'
        : null;

    $insert = $pdo->prepare(
        'INSERT INTO users (username, username_canonical, email, email_canonical, password_hash, display_name, '
        . 'is_active, is_temporary, must_change_password, last_login_at, created_at, updated_at, deleted_at, '
        . 'created_by, creation_reason, approval_status, approved_by, approved_at, rejected_by, rejected_at, rejection_reason) '
        . 'VALUES (:username, :username_canonical, :email, :email_canonical, :password_hash, :display_name, '
        . ':is_active, 0, 0, NULL, :created_at, :updated_at, NULL, :created_by, :creation_reason, '
        . ':approval_status, :approved_by, :approved_at, :rejected_by, :rejected_at, :rejection_reason)'
    );
    $insert->execute([
        'username' => $username,
        'username_canonical' => $username,
        'email' => $email,
        'email_canonical' => $email,
        'password_hash' => $passwordHash,
        'display_name' => 'Archive Restore Check ' . $suffix,
        'is_active' => $isActive ? 1 : 0,
        'created_at' => $now,
        'updated_at' => $now,
        'created_by' => $actorId,
        'creation_reason' => 'Автоматизированная проверка архивирования и восстановления.',
        'approval_status' => $approvalStatus,
        'approved_by' => $approvedBy,
        'approved_at' => $approvedAt,
        'rejected_by' => $rejectedBy,
        'rejected_at' => $rejectedAt,
        'rejection_reason' => $rejectionReason,
    ]);
    $userId = (int) $pdo->lastInsertId();

    $role = $pdo->prepare(
        'INSERT INTO user_roles (user_id, role_id, assigned_at, assigned_by) '
        . 'VALUES (:user_id, :role_id, :assigned_at, :assigned_by)'
    );
    $role->execute([
        'user_id' => $userId,
        'role_id' => $viewerRoleId,
        'assigned_at' => $now,
        'assigned_by' => $actorId,
    ]);

    return [
        'id' => $userId,
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'approved_by' => $approvedBy,
        'approved_at' => $approvedAt,
        'rejected_by' => $rejectedBy,
        'rejected_at' => $rejectedAt,
        'rejection_reason' => $rejectionReason,
    ];
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $migration = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $migration->execute(['migration' => '005_security_user_archive_restore.sql']);
    archive_restore_check((int) $migration->fetchColumn() === 1, 'Миграция 005 не зарегистрирована.');
    echo "OK migration 005\n";

    $column = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name AND column_name = :column_name'
    );
    $columns = ['archived_by', 'last_archived_at', 'archive_reason', 'restored_by', 'restored_at', 'restore_reason'];
    foreach ($columns as $columnName) {
        $column->execute([
            'schema_name' => $db['name'],
            'table_name' => 'users',
            'column_name' => $columnName,
        ]);
        archive_restore_check((int) $column->fetchColumn() === 1, "В таблице users отсутствует {$columnName}.");
    }
    echo 'OK archive restore columns: ' . count($columns) . "\n";

    $foreignKey = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.referential_constraints '
        . 'WHERE constraint_schema = :schema_name AND constraint_name = :constraint_name '
        . "AND delete_rule = 'SET NULL'"
    );
    foreach (['fk_users_archived_by', 'fk_users_restored_by'] as $constraintName) {
        $foreignKey->execute([
            'schema_name' => $db['name'],
            'constraint_name' => $constraintName,
        ]);
        archive_restore_check((int) $foreignKey->fetchColumn() === 1, "Не найден {$constraintName} с ON DELETE SET NULL.");
    }
    echo "OK archive restore foreign keys\n";

    $index = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name AND index_name = :index_name'
    );
    $indexes = [
        'idx_users_deleted_at',
        'idx_users_archived_by',
        'idx_users_last_archived_at',
        'idx_users_restored_by',
        'idx_users_restored_at',
    ];
    foreach ($indexes as $indexName) {
        $index->execute([
            'schema_name' => $db['name'],
            'table_name' => 'users',
            'index_name' => $indexName,
        ]);
        archive_restore_check((int) $index->fetchColumn() >= 1, "Не найден индекс {$indexName}.");
    }
    echo 'OK archive restore indexes: ' . count($indexes) . "\n";

    $permissionCount = (int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE is_system = 1")->fetchColumn();
    archive_restore_check($permissionCount === 19, "Ожидалось 19 системных разрешений, найдено {$permissionCount}.");
    echo "OK system permissions: 19\n";

    $adminGrants = (int) $pdo->query(
        "SELECT COUNT(*) FROM role_permissions rp "
        . "JOIN roles r ON r.id = rp.role_id "
        . "JOIN permissions p ON p.id = rp.permission_id "
        . "WHERE r.code = 'administrator' AND p.code IN ('security.users.archive','security.users.restore')"
    )->fetchColumn();
    archive_restore_check($adminGrants === 2, 'Administrator не получил оба archive/restore permission.');
    echo "OK administrator archive restore permissions\n";

    $actorId = (int) $pdo->query(
        "SELECT u.id FROM users u "
        . "JOIN user_roles ur ON ur.user_id = u.id "
        . "JOIN roles r ON r.id = ur.role_id "
        . "WHERE r.code = 'system_owner' AND u.is_active = 1 "
        . "AND u.approval_status = 'approved' AND u.deleted_at IS NULL "
        . 'ORDER BY u.id LIMIT 1'
    )->fetchColumn();
    archive_restore_check($actorId > 0, 'Не найден действующий владелец для проверки аудита.');

    $viewerRoleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'viewer' LIMIT 1")->fetchColumn();
    archive_restore_check($viewerRoleId > 0, 'Не найдена роль viewer.');

    $service = new UserArchiveRestoreService($pdo);
    $approvalService = new UserApprovalService($pdo);
    $rejectionService = new UserRejectionService($pdo);
    $statusService = new UserStatusService($pdo);
    $updateService = new UserUpdateService($pdo);
    $roleService = new UserRoleUpdateService($pdo);
    $listRepository = new UserListRepository($pdo);

    $selfArchive = $service->archive(
        $actorId,
        $actorId,
        'Проверка серверного запрета самоархивирования владельца.'
    );
    archive_restore_check(!$selfArchive['ok'], 'Самоархивирование не было отклонено.');
    echo "OK self archive rejected\n";

    $approved = archive_restore_insert_fixture($pdo, $actorId, $viewerRoleId, 'approved', 'approved', true);
    $fixtureIds[] = $approved['id'];
    $pending = archive_restore_insert_fixture($pdo, $actorId, $viewerRoleId, 'pending', 'pending', false);
    $fixtureIds[] = $pending['id'];
    $rejected = archive_restore_insert_fixture($pdo, $actorId, $viewerRoleId, 'rejected', 'rejected', false);
    $fixtureIds[] = $rejected['id'];

    $shortArchive = $service->archive($approved['id'], $actorId, 'Коротко');
    archive_restore_check(!$shortArchive['ok'] && isset($shortArchive['errors']['reason']), 'Короткое основание archive принято.');
    echo "OK short archive reason rejected\n";

    $archiveReason = 'Проверка штатного архивирования подтвержденной учетной записи.';
    $archiveResult = $service->archive($approved['id'], $actorId, $archiveReason);
    archive_restore_check($archiveResult['ok'], 'Подтвержденная учетная запись не архивирована.');

    $rowStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $rowStmt->execute(['id' => $approved['id']]);
    $archivedRow = $rowStmt->fetch();
    archive_restore_check($archivedRow !== false, 'Архивированная запись не найдена.');
    archive_restore_check((int) $archivedRow['is_active'] === 0, 'Архивированная запись осталась активной.');
    archive_restore_check($archivedRow['deleted_at'] !== null, 'deleted_at не заполнен.');
    archive_restore_check((int) $archivedRow['archived_by'] === $actorId, 'archived_by не заполнен.');
    archive_restore_check($archivedRow['last_archived_at'] !== null, 'last_archived_at не заполнен.');
    archive_restore_check($archivedRow['archive_reason'] === $archiveReason, 'archive_reason не совпадает.');
    archive_restore_check($archivedRow['restored_by'] === null && $archivedRow['restored_at'] === null && $archivedRow['restore_reason'] === null, 'Restore audit не очищен при archive.');
    echo "OK approved user archived with audit\n";

    $repeatArchive = $service->archive($approved['id'], $actorId, 'Повторная попытка архивирования учетной записи.');
    archive_restore_check(!$repeatArchive['ok'], 'Повторное архивирование не отклонено.');
    echo "OK repeated archive rejected\n";

    $login = $pdo->prepare(
        "SELECT id FROM users WHERE username_canonical = :identifier "
        . "AND is_active = 1 AND approval_status = 'approved' AND deleted_at IS NULL LIMIT 1"
    );
    $login->execute(['identifier' => $approved['username']]);
    archive_restore_check($login->fetchColumn() === false, 'Архивированная запись прошла login-query.');
    echo "OK archived login rejected\n";

    $updateBlocked = $updateService->update($approved['id'], [
        'username' => $approved['username'],
        'display_name' => 'Archive Restore Check approved',
        'email' => $approved['email'],
    ]);
    archive_restore_check(!$updateBlocked['ok'], 'Update архивированной записи не заблокирован.');

    $roleBlocked = $roleService->update($approved['id'], [$viewerRoleId], $actorId, true);
    archive_restore_check(!$roleBlocked['ok'], 'Role update архивированной записи не заблокирован.');

    $statusBlocked = $statusService->setActive($approved['id'], true);
    archive_restore_check(!$statusBlocked['ok'], 'Status update архивированной записи не заблокирован.');

    $approvalBlocked = $approvalService->approve($approved['id'], $actorId);
    archive_restore_check(!$approvalBlocked['ok'], 'Approval архивированной записи не заблокирован.');

    $rejectionBlocked = $rejectionService->reject(
        $approved['id'],
        $actorId,
        'Попытка отклонения архивированной учетной записи.'
    );
    archive_restore_check(!$rejectionBlocked['ok'], 'Rejection архивированной записи не заблокирован.');
    echo "OK archived mutations rejected\n";

    $defaultList = $listRepository->search($approved['username'], 'all', 1, true);
    $archiveList = $listRepository->search($approved['username'], 'archived', 1, true);
    archive_restore_check($defaultList['total'] === 0, 'Архивированная запись попала в default list.');
    archive_restore_check($archiveList['total'] === 1, 'Архивированная запись отсутствует в archive filter.');
    echo "OK archive list isolation\n";

    $shortRestore = $service->restore($approved['id'], $actorId, 'Коротко');
    archive_restore_check(!$shortRestore['ok'] && isset($shortRestore['errors']['reason']), 'Короткое основание restore принято.');
    echo "OK short restore reason rejected\n";

    $restoreReason = 'Проверка безопасного восстановления без автоматической активации.';
    $restoreResult = $service->restore($approved['id'], $actorId, $restoreReason);
    archive_restore_check($restoreResult['ok'], 'Подтвержденная запись не восстановлена.');

    $rowStmt->execute(['id' => $approved['id']]);
    $restoredRow = $rowStmt->fetch();
    archive_restore_check($restoredRow !== false, 'Восстановленная запись не найдена.');
    archive_restore_check($restoredRow['deleted_at'] === null, 'deleted_at не очищен при restore.');
    archive_restore_check((int) $restoredRow['is_active'] === 0, 'Restore автоматически активировал пользователя.');
    archive_restore_check($restoredRow['approval_status'] === 'approved', 'Approval status изменен при restore.');
    archive_restore_check($restoredRow['username'] === $approved['username'], 'Логин изменен при restore.');
    archive_restore_check($restoredRow['email'] === $approved['email'], 'Email изменен при restore.');
    archive_restore_check($restoredRow['password_hash'] === $approved['password_hash'], 'Password hash изменен при restore.');
    archive_restore_check((int) $restoredRow['approved_by'] === $approved['approved_by'], 'approved_by изменен при restore.');
    archive_restore_check($restoredRow['approved_at'] === $approved['approved_at'], 'approved_at изменен при restore.');
    archive_restore_check((int) $restoredRow['restored_by'] === $actorId, 'restored_by не заполнен.');
    archive_restore_check($restoredRow['restored_at'] !== null, 'restored_at не заполнен.');
    archive_restore_check($restoredRow['restore_reason'] === $restoreReason, 'restore_reason не совпадает.');

    $roleCount = $pdo->prepare('SELECT COUNT(*) FROM user_roles WHERE user_id = :user_id AND role_id = :role_id');
    $roleCount->execute(['user_id' => $approved['id'], 'role_id' => $viewerRoleId]);
    archive_restore_check((int) $roleCount->fetchColumn() === 1, 'Роль не сохранена при archive/restore.');
    echo "OK approved user restored blocked with audit\n";

    $repeatRestore = $service->restore($approved['id'], $actorId, 'Повторная попытка восстановления учетной записи.');
    archive_restore_check(!$repeatRestore['ok'], 'Повторное восстановление не отклонено.');
    echo "OK repeated restore rejected\n";

    $secondArchiveReason = 'Проверка нового цикла архивирования после восстановления.';
    $secondArchive = $service->archive($approved['id'], $actorId, $secondArchiveReason);
    archive_restore_check($secondArchive['ok'], 'Повторный цикл archive после restore не выполнен.');
    $rowStmt->execute(['id' => $approved['id']]);
    $secondArchiveRow = $rowStmt->fetch();
    archive_restore_check($secondArchiveRow['archive_reason'] === $secondArchiveReason, 'Новый archive audit не записан.');
    archive_restore_check($secondArchiveRow['restored_by'] === null && $secondArchiveRow['restored_at'] === null && $secondArchiveRow['restore_reason'] === null, 'Restore audit не очищен в новом цикле.');
    echo "OK second archive cycle recorded\n";

    foreach ([
        [$pending, 'pending'],
        [$rejected, 'rejected'],
    ] as [$fixture, $expectedStatus]) {
        $archive = $service->archive(
            $fixture['id'],
            $actorId,
            'Проверка архивирования записи в состоянии ' . $expectedStatus . '.'
        );
        archive_restore_check($archive['ok'], "Fixture {$expectedStatus} не архивирован.");
        $restore = $service->restore(
            $fixture['id'],
            $actorId,
            'Проверка восстановления записи в состоянии ' . $expectedStatus . '.'
        );
        archive_restore_check($restore['ok'], "Fixture {$expectedStatus} не восстановлен.");
        $rowStmt->execute(['id' => $fixture['id']]);
        $matrixRow = $rowStmt->fetch();
        archive_restore_check($matrixRow['approval_status'] === $expectedStatus, "Workflow {$expectedStatus} изменен.");
        archive_restore_check((int) $matrixRow['is_active'] === 0, "Fixture {$expectedStatus} активирован при restore.");
        if ($expectedStatus === 'rejected') {
            archive_restore_check((int) $matrixRow['rejected_by'] === $fixture['rejected_by'], 'rejected_by изменен.');
            archive_restore_check($matrixRow['rejected_at'] === $fixture['rejected_at'], 'rejected_at изменен.');
            archive_restore_check($matrixRow['rejection_reason'] === $fixture['rejection_reason'], 'rejection_reason изменен.');
        }
    }
    echo "OK pending and rejected restore matrix\n";

    echo "OK archive restore integration check completed\n";
} catch (Throwable $exception) {
    $exitCode = 1;
    fwrite(STDERR, 'ERROR ' . $exception->getMessage() . PHP_EOL);
} finally {
    if (isset($pdo) && $pdo instanceof PDO && $fixtureIds !== []) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $placeholders = implode(',', array_fill(0, count($fixtureIds), '?'));
            $deleteRoles = $pdo->prepare("DELETE FROM user_roles WHERE user_id IN ({$placeholders})");
            $deleteRoles->execute($fixtureIds);
            $deleteUsers = $pdo->prepare("DELETE FROM users WHERE id IN ({$placeholders})");
            $deleteUsers->execute($fixtureIds);
        } catch (Throwable $cleanupException) {
            fwrite(STDERR, 'ERROR cleanup failed: ' . get_class($cleanupException) . PHP_EOL);
            $exitCode = 1;
        }
    }
}

exit($exitCode);
