<?php

declare(strict_types=1);

require_once __DIR__ . '/OrganizationalStructureRepository.php';
require_once __DIR__ . '/OrganizationalStructureService.php';

function organizational_structure_repository(): OrganizationalStructureRepository
{
    static $repository = null;
    if (!$repository instanceof OrganizationalStructureRepository) {
        $repository = new OrganizationalStructureRepository(db());
    }
    return $repository;
}

function organizational_structure_service(): OrganizationalStructureService
{
    static $service = null;
    if (!$service instanceof OrganizationalStructureService) {
        $service = new OrganizationalStructureService(db());
    }
    return $service;
}

function organization_positive_int(mixed $value, string $message = 'Некорректный идентификатор.'): int
{
    if ((!is_int($value) && !is_string($value)) || preg_match('/\A[1-9][0-9]*\z/D', (string) $value) !== 1) {
        throw new DomainException($message);
    }
    $normalized = (string) $value;
    $max = (string) PHP_INT_MAX;
    if (strlen($normalized) > strlen($max) || (strlen($normalized) === strlen($max) && strcmp($normalized, $max) > 0)) {
        throw new DomainException($message);
    }
    $result = (int) $normalized;
    if ($result < 1) {
        throw new DomainException($message);
    }
    return $result;
}

function organization_require_action(string $permission): array
{
    $user = require_permission('organization.structures.view');
    if (!has_permission($permission)) {
        return require_permission($permission);
    }
    return $user;
}

function organization_get_positive_int(string $key, string $message = 'Запрошенный объект не найден.'): int
{
    try {
        return organization_positive_int($_GET[$key] ?? null);
    } catch (DomainException) {
        http_response_code(404);
        exit($message);
    }
}

function organization_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function organization_post_nullable_string(string $key): ?string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? $value : null;
}

function organization_result_url(string $path, string $type, string $message): string
{
    $separator = str_contains($path, '?') ? '&' : '?';
    return $path . $separator . 'result=' . rawurlencode(create_operation_result($type, $message));
}

function organization_safe_return_path(int $structureId, ?int $versionId = null): string
{
    $path = '/admin/organization/structure.php?id=' . $structureId;
    if ($versionId !== null) {
        $path .= '&version_id=' . $versionId;
    }
    return $path;
}

function organization_action_fallback_path(): string
{
    try {
        $structureId = organization_positive_int($_POST['structure_id'] ?? null);
    } catch (DomainException) {
        return '/admin/organization/structures.php';
    }
    try {
        $versionId = organization_positive_int($_POST['version_id'] ?? null);
    } catch (DomainException) {
        return organization_safe_return_path($structureId);
    }
    return organization_safe_return_path($structureId, $versionId);
}

function organization_handle_action(callable $callback, ?string $fallbackPath = null): never
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Метод не поддерживается.');
    }
    $successPath = $fallbackPath ?? organization_action_fallback_path();
    try {
        require_csrf();
        $resolvedPath = $callback();
        if (!is_string($resolvedPath) || !str_starts_with($resolvedPath, '/admin/organization/')) {
            throw new LogicException('Некорректный путь возврата организационного модуля.');
        }
        $successPath = $resolvedPath;
        redirect(organization_result_url($successPath, 'success', 'Операция выполнена.'));
    } catch (DomainException $exception) {
        flash('organization_error', $exception->getMessage());
        redirect(organization_result_url($successPath, 'error', 'Не удалось выполнить операцию.'));
    } catch (PDOException $exception) {
        error_log('Organizational structure database operation failed: ' . $exception->getCode());
        flash('organization_error', 'Операция не выполнена из-за ограничения целостности данных.');
        redirect(organization_result_url($successPath, 'error', 'Не удалось выполнить операцию.'));
    } catch (Throwable $exception) {
        error_log('Organizational structure operation failed: ' . $exception::class);
        flash('organization_error', 'Операция не выполнена из-за серверной ошибки.');
        redirect(organization_result_url($successPath, 'error', 'Не удалось выполнить операцию.'));
    }
}

function organization_format_history_state(?string $json): ?string
{
    if ($json === null || $json === '') {
        return null;
    }
    try {
        $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return null;
        }
        return json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        return null;
    }
}

/** @param list<array<string,mixed>> $nodes
 *  @return list<array{node:array<string,mixed>,depth:int}>
 */
function organization_flatten_tree(array $nodes): array
{
    $byParent = [];
    foreach ($nodes as $node) {
        $parentKey = $node['parent_node_id'] === null ? 0 : (int) $node['parent_node_id'];
        $byParent[$parentKey][] = $node;
    }
    foreach ($byParent as &$siblings) {
        usort($siblings, static fn (array $a, array $b): int => [(int) $a['sort_order'], (int) $a['id']] <=> [(int) $b['sort_order'], (int) $b['id']]);
    }
    unset($siblings);

    $result = [];
    $roots = $byParent[0] ?? [];
    $stack = [];
    for ($index = count($roots) - 1; $index >= 0; $index--) {
        $stack[] = ['node' => $roots[$index], 'depth' => 0];
    }
    $visited = [];
    while ($stack !== []) {
        $entry = array_pop($stack);
        $node = $entry['node'];
        $id = (int) $node['id'];
        if (isset($visited[$id])) {
            continue;
        }
        $visited[$id] = true;
        $result[] = $entry;
        $children = $byParent[$id] ?? [];
        for ($index = count($children) - 1; $index >= 0; $index--) {
            $stack[] = ['node' => $children[$index], 'depth' => $entry['depth'] + 1];
        }
    }
    return $result;
}
