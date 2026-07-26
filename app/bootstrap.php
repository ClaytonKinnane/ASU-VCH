<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$app = require $root . '/config/app.php';
$localFile = $root . '/config/local.php';
if (!is_file($localFile)) {
    throw new RuntimeException('Не найден config/local.php. Скопируйте config/local.example.php и укажите параметры БД.');
}
$local = require $localFile;
$config = array_replace_recursive($app, $local);

require_once __DIR__ . '/BootstrapOwnerService.php';
require_once __DIR__ . '/Security/AuthorizationService.php';
require_once __DIR__ . '/Security/UserListRepository.php';
require_once __DIR__ . '/Security/UserCreateService.php';
require_once __DIR__ . '/Security/UserApprovalService.php';
require_once __DIR__ . '/Security/UserRejectionService.php';
require_once __DIR__ . '/Security/UserArchiveRestoreService.php';
require_once __DIR__ . '/Security/UserDetailRepository.php';
require_once __DIR__ . '/Security/UserUpdateService.php';
require_once __DIR__ . '/Security/UserRoleUpdateService.php';
require_once __DIR__ . '/Security/UserStatusService.php';
require_once __DIR__ . '/Security/RequiredPasswordChangeService.php';

date_default_timezone_set((string) $config['timezone']);

session_name((string) $config['session_name']);
session_set_cookie_params([
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Lax',
    'path' => '/',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    global $config;
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $db = app_config('database');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function bootstrap_owner_service(): BootstrapOwnerService
{
    return new BootstrapOwnerService(db());
}

function authorization_service(): AuthorizationService
{
    static $service = null;
    if (!$service instanceof AuthorizationService) {
        $service = new AuthorizationService(db());
    }
    return $service;
}

function user_list_repository(): UserListRepository
{
    return new UserListRepository(db());
}

function user_create_service(): UserCreateService
{
    return new UserCreateService(db());
}

function user_approval_service(): UserApprovalService
{
    return new UserApprovalService(db());
}

function user_rejection_service(): UserRejectionService
{
    return new UserRejectionService(db());
}

function user_archive_restore_service(): UserArchiveRestoreService
{
    return new UserArchiveRestoreService(db());
}

function user_detail_repository(): UserDetailRepository
{
    return new UserDetailRepository(db());
}

function user_update_service(): UserUpdateService
{
    return new UserUpdateService(db());
}

function user_role_update_service(): UserRoleUpdateService
{
    return new UserRoleUpdateService(db());
}

function user_status_service(): UserStatusService
{
    return new UserStatusService(db());
}

function required_password_change_service(): RequiredPasswordChangeService
{
    return new RequiredPasswordChangeService(db());
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Недействительный CSRF-токен.');
    }
}

function installation_completed(): bool
{
    $stmt = db()->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'installation_completed' LIMIT 1");
    $stmt->execute();
    return $stmt->fetchColumn() === '1';
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !ctype_digit((string) $id)) {
        return null;
    }
    $stmt = db()->prepare(
        "SELECT id, username, display_name, is_temporary, must_change_password, last_login_at FROM users WHERE id = :id AND is_active = 1 AND approval_status = 'approved' AND deleted_at IS NULL LIMIT 1"
    );
    $stmt->execute(['id' => (int) $id]);
    $row = $stmt->fetch();
    $user = $row ?: null;
    return $user;
}

function require_authenticated_user(bool $allowRequiredPasswordChange = false): array
{
    $user = current_user();
    if ($user === null) {
        redirect('/');
    }
    if (!$allowRequiredPasswordChange && (int) $user['must_change_password'] === 1) {
        redirect('/account/change-password.php');
    }
    return $user;
}

/** @return list<string> */
function current_user_role_codes(): array
{
    $user = current_user();
    return $user === null ? [] : authorization_service()->roleCodesForUser((int) $user['id']);
}

/** @return list<string> */
function current_user_permission_codes(): array
{
    $user = current_user();
    return $user === null ? [] : authorization_service()->permissionCodesForUser((int) $user['id']);
}

function has_permission(string $permission): bool
{
    $user = current_user();
    return $user !== null && authorization_service()->hasPermission((int) $user['id'], $permission);
}

function require_permission(string $permission): array
{
    $user = require_authenticated_user();
    if (!has_permission($permission)) {
        http_response_code(403);
        exit('<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Доступ запрещен — АСУ-ВЧ</title><link rel="stylesheet" href="/themes/asu-blue/assets/css/theme.css"></head><body><main class="site-main"><section class="auth-card glass-tile"><h1 class="auth-heading">Доступ запрещен</h1><p class="auth-description">У вашей учетной записи нет разрешения на открытие этого раздела.</p><a class="secondary-button" href="/admin/">К панели</a></section></main></body></html>');
    }
    return $user;
}

function require_system_owner(): array
{
    $user = require_authenticated_user();
    if (!in_array('system_owner', current_user_role_codes(), true)) {
        redirect('/');
    }
    return $user;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($message) ? $message : null;
}
