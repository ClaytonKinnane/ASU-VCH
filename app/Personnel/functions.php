<?php

declare(strict_types=1);

require_once __DIR__ . '/PersonnelRepository.php';
require_once __DIR__ . '/PersonnelService.php';

function personnel_repository(): PersonnelRepository
{
    static $repository = null;
    if (!$repository instanceof PersonnelRepository) {
        $repository = new PersonnelRepository(db());
    }
    return $repository;
}

function personnel_service(): PersonnelService
{
    static $service = null;
    if (!$service instanceof PersonnelService) {
        $service = new PersonnelService(db());
    }
    return $service;
}

function personnel_security_headers(): void
{
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('Referrer-Policy: same-origin');
    header('X-Content-Type-Options: nosniff');
}

function personnel_require_owner(): array
{
    personnel_security_headers();
    return require_system_owner();
}

function personnel_positive_int(mixed $value, string $message = 'Некорректный идентификатор.'): int
{
    if ((!is_int($value) && !is_string($value)) || preg_match('/\A[1-9][0-9]*\z/D', (string) $value) !== 1) {
        throw new DomainException($message);
    }
    $normalized = (string) $value;
    if (strlen($normalized) > strlen((string) PHP_INT_MAX)
        || (strlen($normalized) === strlen((string) PHP_INT_MAX) && strcmp($normalized, (string) PHP_INT_MAX) > 0)) {
        throw new DomainException($message);
    }
    return (int) $normalized;
}

function personnel_get_id(string $key = 'id'): int
{
    try {
        return personnel_positive_int($_GET[$key] ?? null);
    } catch (DomainException) {
        http_response_code(404);
        exit('Карточка военнослужащего не найдена.');
    }
}

function personnel_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function personnel_post_nullable_string(string $key): ?string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? $value : null;
}

function personnel_safe_card_path(int $id): string
{
    return '/admin/personnel/person.php?id=' . $id;
}

function personnel_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Метод не поддерживается.');
    }
}

function personnel_handle_action(callable $callback, string $fallbackPath = '/admin/personnel/persons.php'): never
{
    personnel_require_owner();
    personnel_require_post();
    try {
        require_csrf();
        $path = $callback();
        if (!is_string($path) || !str_starts_with($path, '/admin/personnel/')) {
            throw new LogicException('Некорректный путь возврата Personnel.');
        }
        flash('personnel_success', 'Операция выполнена.');
        redirect($path);
    } catch (DomainException | OutOfBoundsException $exception) {
        flash('personnel_error', $exception->getMessage());
        redirect($fallbackPath);
    } catch (PDOException $exception) {
        error_log('Personnel database operation failed: ' . $exception->getCode());
        flash('personnel_error', 'Операция не выполнена из-за ограничения целостности данных.');
        redirect($fallbackPath);
    } catch (Throwable $exception) {
        error_log('Personnel operation failed: ' . $exception::class);
        flash('personnel_error', 'Операция не выполнена из-за серверной ошибки.');
        redirect($fallbackPath);
    }
}

function personnel_full_name(array $person): string
{
    return trim(implode(' ', array_filter([
        (string) ($person['last_name'] ?? ''),
        (string) ($person['first_name'] ?? ''),
        (string) ($person['middle_name'] ?? ''),
    ], static fn (string $value): bool => $value !== '')));
}

function personnel_history_summary(array $event): string
{
    return match ((string) $event['event_type']) {
        'personnel.created' => 'Создана карточка военнослужащего.',
        'personnel.core_updated' => 'Изменены основные персональные сведения.',
        'personnel.archived' => 'Карточка помещена в архив.',
        'personnel.restored' => 'Карточка восстановлена из архива.',
        'identifier.added' => 'Добавлен идентификатор.',
        'identifier.replaced' => 'Идентификатор заменён с сохранением истории.',
        'identifier.ended' => 'Прекращено действие идентификатора.',
        default => 'Зафиксировано изменение карточки.',
    };
}
