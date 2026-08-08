<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Personnel/functions.php';

$user = personnel_require_owner();
$query = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 150) : '';
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : 'active';
if (!in_array($status, ['active', 'archived', 'all'], true)) {
    $status = 'active';
}
$birthDate = isset($_GET['birth_date']) && is_string($_GET['birth_date']) && $_GET['birth_date'] !== '' ? $_GET['birth_date'] : null;
if ($birthDate !== null) {
    $parsedBirthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
    if (!$parsedBirthDate || $parsedBirthDate->format('Y-m-d') !== $birthDate) {
        $birthDate = null;
    }
}
try {
    $page = isset($_GET['page']) ? personnel_positive_int($_GET['page']) : 1;
} catch (DomainException) {
    $page = 1;
}
try {
    $perPage = isset($_GET['per_page']) ? personnel_positive_int($_GET['per_page']) : 50;
} catch (DomainException) {
    $perPage = 50;
}
$perPage = min(100, max(1, $perPage));
$list = personnel_repository()->listPersons($query, $status, $birthDate, $page, $perPage);
$domainError = flash('personnel_error');
$domainSuccess = flash('personnel_success');
require __DIR__ . '/views/person-list.php';
