<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Personnel/functions.php';

$user = personnel_require_owner();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    personnel_handle_action(static function () use ($user): string {
        $id = personnel_service()->createPerson($_POST, (int) $user['id']);
        return personnel_safe_card_path($id);
    }, '/admin/personnel/persons/create.php');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Метод не поддерживается.');
}
$mode = 'create';
$person = [
    'last_name' => '', 'first_name' => '', 'middle_name' => '', 'birth_date' => '',
    'birth_place' => '', 'citizenship' => '', 'nationality' => '', 'religion' => '',
];
$domainError = flash('personnel_error');
require dirname(__DIR__) . '/views/person-form.php';
