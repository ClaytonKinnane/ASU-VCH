<?php

declare(strict_types=1);

require_once __DIR__ . '/StaffingRepository.php';
require_once __DIR__ . '/StaffingService.php';

function staffing_repository(): StaffingRepository
{
    static $repository = null;
    if (!$repository instanceof StaffingRepository) {
        $repository = new StaffingRepository(db());
    }
    return $repository;
}

function staffing_service(): StaffingService
{
    static $service = null;
    if (!$service instanceof StaffingService) {
        $service = new StaffingService(db());
    }
    return $service;
}

function staffing_positive_int(mixed $value, string $message = 'Некорректный идентификатор.'): int
{
    if ((!is_int($value) && !is_string($value)) || preg_match('/\A[1-9][0-9]*\z/D', (string) $value) !== 1) {
        throw new DomainException($message);
    }
    $normalized = (string) $value;
    $max = (string) PHP_INT_MAX;
    if (strlen($normalized) > strlen($max)
        || (strlen($normalized) === strlen($max) && strcmp($normalized, $max) > 0)) {
        throw new DomainException($message);
    }
    $result = (int) $normalized;
    if ($result < 1) {
        throw new DomainException($message);
    }
    return $result;
}

function staffing_get_positive_int(string $key, string $message = 'Запрошенный объект не найден.'): int
{
    try {
        return staffing_positive_int($_GET[$key] ?? null);
    } catch (DomainException) {
        http_response_code(404);
        exit($message);
    }
}

function staffing_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function staffing_post_nullable_string(string $key): ?string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? $value : null;
}

function staffing_require_action(string $permission): array
{
    $user = require_permission('staffing.registers.view');
    if (!has_permission($permission)) {
        return require_permission($permission);
    }
    return $user;
}

function staffing_safe_return_path(int $registerId, ?int $versionId = null): string
{
    $path = '/admin/staffing/register.php?id=' . $registerId;
    if ($versionId !== null) {
        $path .= '&version_id=' . $versionId;
    }
    return $path;
}

function staffing_action_fallback_path(): string
{
    try {
        $registerId = staffing_positive_int($_POST['register_id'] ?? null);
    } catch (DomainException) {
        return '/admin/staffing/registers.php';
    }
    try {
        $versionId = staffing_positive_int($_POST['version_id'] ?? null);
    } catch (DomainException) {
        return staffing_safe_return_path($registerId);
    }
    return staffing_safe_return_path($registerId, $versionId);
}

function staffing_handle_action(callable $callback, ?string $fallbackPath = null): never
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Метод не поддерживается.');
    }
    $successPath = $fallbackPath ?? staffing_action_fallback_path();
    try {
        require_csrf();
        $resolvedPath = $callback();
        if (!is_string($resolvedPath) || !str_starts_with($resolvedPath, '/admin/staffing/')) {
            throw new LogicException('Некорректный путь возврата модуля штатной структуры.');
        }
        $successPath = $resolvedPath;
        flash('staffing_success', 'Операция выполнена.');
        redirect($successPath);
    } catch (DomainException | OutOfBoundsException $exception) {
        flash('staffing_error', $exception->getMessage());
        redirect($successPath);
    } catch (PDOException $exception) {
        error_log('Staffing database operation failed: ' . $exception->getCode());
        flash('staffing_error', 'Операция не выполнена из-за ограничения целостности данных.');
        redirect($successPath);
    } catch (Throwable $exception) {
        error_log('Staffing operation failed: ' . $exception::class);
        flash('staffing_error', 'Операция не выполнена из-за серверной ошибки.');
        redirect($successPath);
    }
}

function staffing_format_history_state(?string $json): ?string
{
    if ($json === null || $json === '') {
        return null;
    }
    try {
        $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return null;
        }
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }
}

/** @param list<array<string,mixed>> $nodes @return list<array{node:array<string,mixed>,depth:int}> */
function staffing_flatten_organization_nodes(array $nodes): array
{
    $byParent = [];
    foreach ($nodes as $node) {
        $parent = $node['parent_node_id'] === null ? 0 : (int) $node['parent_node_id'];
        $byParent[$parent][] = $node;
    }
    foreach ($byParent as &$siblings) {
        usort($siblings, static fn (array $a, array $b): int => [(int) $a['sort_order'], (int) $a['id']] <=> [(int) $b['sort_order'], (int) $b['id']]);
    }
    unset($siblings);
    $result = [];
    $stack = [];
    foreach (array_reverse($byParent[0] ?? []) as $node) {
        $stack[] = ['node' => $node, 'depth' => 0];
    }
    $seen = [];
    while ($stack !== []) {
        $entry = array_pop($stack);
        $id = (int) $entry['node']['id'];
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $result[] = $entry;
        foreach (array_reverse($byParent[$id] ?? []) as $child) {
            $stack[] = ['node' => $child, 'depth' => $entry['depth'] + 1];
        }
    }
    return $result;
}

/** @return list<array{public_disclosure_id:int,requirement_role:string,sort_order:int}> */
function staffing_parse_vus_requirements_from_post(): array
{
    $ids = $_POST['vus_id'] ?? [];
    $roles = $_POST['vus_role'] ?? [];
    if (!is_array($ids) || !is_array($roles)) {
        return [];
    }
    $result = [];
    $order = 1;
    foreach ($ids as $index => $id) {
        if ($id === '' || $id === null) {
            continue;
        }
        $role = $roles[$index] ?? 'required';
        $result[] = [
            'public_disclosure_id' => staffing_positive_int($id, 'Некорректный идентификатор ВУС.'),
            'requirement_role' => is_string($role) ? $role : 'required',
            'sort_order' => $order++,
        ];
    }
    return $result;
}
