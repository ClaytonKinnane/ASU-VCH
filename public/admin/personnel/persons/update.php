<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Personnel/functions.php';

$user = personnel_require_owner();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = 0;
    try {
        $id = personnel_positive_int($_POST['id'] ?? null);
    } catch (DomainException) {
        personnel_handle_action(static fn (): string => throw new DomainException('Карточка военнослужащего не найдена.'), '/admin/personnel/persons.php');
    }
    personnel_handle_action(static function () use ($user, $id): string {
        $expectedRevision = personnel_positive_int($_POST['expected_revision'] ?? null, 'Некорректная версия карточки.');
        personnel_service()->updatePerson($id, $_POST, $expectedRevision, personnel_post_nullable_string('reason'), (int) $user['id']);
        return personnel_safe_card_path($id);
    }, personnel_safe_card_path($id));
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Метод не поддерживается.');
}
$id = personnel_get_id();
$person = personnel_repository()->person($id);
if ($person === null) {
    http_response_code(404);
    exit('Карточка военнослужащего не найдена.');
}
if ((string) $person['record_status'] !== 'active') {
    flash('personnel_error', 'Архивная карточка доступна только для чтения.');
    redirect(personnel_safe_card_path($id));
}
$mode = 'update';
$domainError = flash('personnel_error');
require dirname(__DIR__) . '/views/person-form.php';
