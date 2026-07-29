<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Organization/functions.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

$user = require_permission('organization.structures.view');
$structureId = organization_get_positive_int('id', 'Организационная структура не найдена.');
$repository = organizational_structure_repository();
$structure = $repository->findStructure($structureId);
if ($structure === null) {
    http_response_code(404);
    exit('Организационная структура не найдена.');
}
$versions = $repository->versionsForStructure($structureId);
$requestedVersionId = null;
if (isset($_GET['version_id']) && $_GET['version_id'] !== '') {
    $requestedVersionId = organization_get_positive_int('version_id', 'Версия организационной структуры не найдена.');
}
$selectedVersion = null;
if ($requestedVersionId !== null) {
    $candidate = $repository->findVersion($requestedVersionId);
    if ($candidate !== null && (int) $candidate['organizational_structure_id'] === $structureId) {
        $selectedVersion = $candidate;
    }
} else {
    $defaultVersion = $repository->pendingVersion($structureId) ?? $repository->activeVersion($structureId);
    if ($defaultVersion !== null) {
        $selectedVersion = $repository->findVersion((int) $defaultVersion['id']);
    } elseif ($versions !== []) {
        $selectedVersion = $repository->findVersion((int) $versions[0]['id']);
    }
}
if ($requestedVersionId !== null && $selectedVersion === null) {
    http_response_code(404);
    exit('Версия организационной структуры не найдена.');
}

$nodes = $selectedVersion !== null ? $repository->nodesForVersion((int) $selectedVersion['id']) : [];
$flatTree = organization_flatten_tree($nodes);
$documents = $selectedVersion !== null ? $repository->documentsForVersion((int) $selectedVersion['id']) : [];
$types = $selectedVersion !== null ? $repository->typesForCatalog((int) $selectedVersion['catalog_version_id']) : [];
$rootTypes = $selectedVersion !== null ? $repository->rootTypesForCatalog((int) $selectedVersion['catalog_version_id']) : [];
$history = has_permission('organization.structures.history') ? $repository->historyForStructure($structureId) : [];
$diff = [];
if ($selectedVersion !== null && $selectedVersion['based_on_version_id'] !== null) {
    $diff = $repository->diffVersions((int) $selectedVersion['based_on_version_id'], (int) $selectedVersion['id']);
} elseif ($selectedVersion !== null) {
    foreach ($nodes as $node) {
        $diff[] = [
            'element_id' => (int) $node['organizational_structure_element_id'],
            'base_node_id' => null,
            'target_node_id' => (int) $node['id'],
            'base_name' => null,
            'target_name' => (string) $node['name'],
            'base_short_name' => null,
            'target_short_name' => $node['short_name'],
            'base_internal_code' => null,
            'target_internal_code' => $node['internal_code'],
            'base_type_id' => null,
            'target_type_id' => (int) $node['organizational_element_type_id'],
            'base_parent_element_id' => null,
            'target_parent_element_id' => null,
            'base_sort_order' => null,
            'target_sort_order' => (int) $node['sort_order'],
            'base_note' => null,
            'target_note' => $node['note'],
        ];
    }
}

$canUpdate = has_permission('organization.structures.update');
$canPublish = has_permission('organization.structures.publish');
$canArchive = has_permission('organization.structures.archive');
$canHistory = has_permission('organization.structures.history');
$isDraft = $selectedVersion !== null && (string) $selectedVersion['status'] === 'draft';
$isApproved = $selectedVersion !== null && (string) $selectedVersion['status'] === 'approved';
$domainError = flash('organization_error');
$success = flash('success');
$error = flash('error');

$statusLabel = static fn (string $status): string => match ($status) {
    'draft' => 'Черновик',
    'approved' => 'Утверждена',
    'active' => 'Действует',
    'superseded' => 'Заменена',
    'cancelled' => 'Отменена',
    default => $status,
};
$roleLabel = static fn (string $role): string => match ($role) {
    'primary_basis' => 'Основной документ',
    'additional_basis' => 'Дополнительное основание',
    'amendment' => 'Изменение',
    default => $role,
};

require __DIR__ . '/views/layout-start.php';
require __DIR__ . '/views/summary-navigation.php';
require __DIR__ . '/views/versions.php';
require __DIR__ . '/views/tree.php';
require __DIR__ . '/views/documents.php';
require __DIR__ . '/views/compare.php';
require __DIR__ . '/views/history.php';
require __DIR__ . '/views/layout-end.php';
