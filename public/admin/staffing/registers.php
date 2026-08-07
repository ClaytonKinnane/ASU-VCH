<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Staffing/functions.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

$user = require_permission('staffing.registers.view');
$canCreate = has_permission('staffing.registers.create');
$query = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 150) : '';
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$structureId = null;
if (isset($_GET['structure_id']) && $_GET['structure_id'] !== '') {
    try {
        $structureId = staffing_positive_int($_GET['structure_id']);
    } catch (DomainException) {
        $structureId = null;
    }
}
$registers = staffing_repository()->listRegisters($query, $status, $structureId);
$structures = staffing_repository()->organizationalStructures();
$domainError = flash('staffing_error');
$domainSuccess = flash('staffing_success');
require __DIR__ . '/views/register-list.php';
