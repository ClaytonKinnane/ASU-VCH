<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Personnel/functions.php';

$user = personnel_require_owner();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $personnelId = 0;
    try {
        $personnelId = personnel_positive_int($_POST['personnel_id'] ?? null);
    } catch (DomainException) {
        personnel_handle_action(static fn (): string => throw new DomainException('Карточка военнослужащего не найдена.'), '/admin/personnel/persons.php');
    }
    personnel_handle_action(static function () use ($user, $personnelId): string {
        $typeId = personnel_positive_int($_POST['identifier_type_id'] ?? null, 'Некорректный тип идентификатора.');
        $revision = personnel_positive_int($_POST['expected_revision'] ?? null, 'Некорректная версия карточки.');
        personnel_service()->replaceIdentifier(
            $personnelId,
            $typeId,
            $_POST['new_value'] ?? null,
            $_POST['effective_date'] ?? null,
            $_POST['reason'] ?? null,
            $revision,
            (int) $user['id']
        );
        return personnel_safe_card_path($personnelId);
    }, personnel_safe_card_path($personnelId));
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Метод не поддерживается.');
}
$personnelId = personnel_get_id('personnel_id');
try {
    $selectedTypeId = personnel_positive_int($_GET['type_id'] ?? null, 'Некорректный тип идентификатора.');
} catch (DomainException) {
    http_response_code(404);
    exit('Идентификатор не найден.');
}
$person = personnel_repository()->person($personnelId);
if ($person === null) {
    http_response_code(404);
    exit('Карточка военнослужащего не найдена.');
}
if ((string) $person['record_status'] !== 'active') {
    flash('personnel_error', 'Архивная карточка доступна только для чтения.');
    redirect(personnel_safe_card_path($personnelId));
}
$identifierTypes = personnel_repository()->identifierTypes();
$activeIdentifiers = personnel_repository()->activeIdentifiersByType($personnelId);
if (!isset($activeIdentifiers[$selectedTypeId])) {
    http_response_code(404);
    exit('Действующий идентификатор не найден.');
}
$mode = 'replace';
$domainError = flash('personnel_error');
require dirname(__DIR__) . '/views/identifier-form.php';
