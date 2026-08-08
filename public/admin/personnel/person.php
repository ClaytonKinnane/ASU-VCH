<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Personnel/functions.php';

$user = personnel_require_owner();
$id = personnel_get_id();
$person = personnel_repository()->person($id);
if ($person === null) {
    http_response_code(404);
    exit('Карточка военнослужащего не найдена.');
}
$identifierTypes = personnel_repository()->identifierTypes();
$identifiers = personnel_repository()->identifiers($id);
$activeIdentifiers = personnel_repository()->activeIdentifiersByType($id);
$history = array_slice(personnel_repository()->history($id), 0, 8);
$domainError = flash('personnel_error');
$domainSuccess = flash('personnel_success');
require __DIR__ . '/views/person-card.php';
