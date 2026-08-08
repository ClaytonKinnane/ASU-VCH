<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
require_once dirname(__DIR__, 4) . '/app/Personnel/functions.php';

$user = personnel_require_owner();
$id = 0;
try {
    $id = personnel_positive_int($_POST['id'] ?? null);
} catch (DomainException) {
    personnel_handle_action(static fn (): string => throw new DomainException('Карточка военнослужащего не найдена.'), '/admin/personnel/persons.php');
}
personnel_handle_action(static function () use ($user, $id): string {
    $revision = personnel_positive_int($_POST['expected_revision'] ?? null, 'Некорректная версия карточки.');
    personnel_service()->archivePerson($id, $revision, $_POST['reason'] ?? null, (int) $user['id']);
    return personnel_safe_card_path($id);
}, personnel_safe_card_path($id));
