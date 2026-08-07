<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Staffing/functions.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

$user = require_permission('staffing.registers.view');
$registerId = staffing_get_positive_int('id');
try {
    $register = staffing_repository()->register($registerId);
    $versions = staffing_repository()->versions($registerId);
} catch (OutOfBoundsException) {
    http_response_code(404);
    exit('Штатный реестр не найден.');
}

$selectedVersionId = null;
if (isset($_GET['version_id']) && $_GET['version_id'] !== '') {
    try {
        $selectedVersionId = staffing_positive_int($_GET['version_id']);
    } catch (DomainException) {
        $selectedVersionId = null;
    }
}
if ($selectedVersionId === null) {
    $selectedVersionId = $register['pending_version_id'] !== null
        ? (int) $register['pending_version_id']
        : ($register['active_version_id'] !== null ? (int) $register['active_version_id'] : ($versions[0]['id'] ?? null));
}

$selectedVersion = null;
$nodes = $positionTypes = $positionVariants = $ranks = $vusDisclosures = $documents = $slots = [];
if ($selectedVersionId !== null) {
    try {
        $selectedVersion = staffing_repository()->version($registerId, (int) $selectedVersionId);
        $nodes = staffing_flatten_organization_nodes(
            staffing_repository()->organizationNodes((int) $selectedVersion['organizational_structure_version_id'])
        );
        $positionTypes = staffing_repository()->positionTypes((int) $selectedVersion['position_catalog_version_id']);
        $positionVariants = staffing_repository()->positionVariants((int) $selectedVersion['position_catalog_version_id']);
        $ranks = staffing_repository()->ranks((int) $selectedVersion['rank_catalog_version_id']);
        $vusDisclosures = staffing_repository()->vusDisclosures((int) $selectedVersion['vus_catalog_version_id']);
        $documents = staffing_repository()->documents((int) $selectedVersion['id']);
        $slots = staffing_repository()->slots((int) $selectedVersion['id']);
    } catch (OutOfBoundsException) {
        $selectedVersion = null;
    }
}

$organizationVersions = staffing_repository()->eligibleOrganizationVersions((int) $register['organizational_structure_id']);
$canCreate = has_permission('staffing.registers.create');
$canUpdate = has_permission('staffing.registers.update');
$canPublish = has_permission('staffing.registers.publish');
$canArchive = has_permission('staffing.registers.archive');
$canHistory = has_permission('staffing.registers.history');
$domainError = flash('staffing_error');
$domainSuccess = flash('staffing_success');
require __DIR__ . '/views/register-card.php';
