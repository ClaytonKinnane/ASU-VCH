<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Staffing/functions.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

require_permission('staffing.registers.view');
$registerId = staffing_get_positive_int('register_id');
try {
    $register = staffing_repository()->register($registerId);
    $versions = staffing_repository()->versions($registerId);
} catch (OutOfBoundsException) {
    http_response_code(404);
    exit('Штатный реестр не найден.');
}

$leftId = null;
$rightId = null;
try {
    $leftId = isset($_GET['left_id']) ? staffing_positive_int($_GET['left_id']) : null;
    $rightId = isset($_GET['right_id']) ? staffing_positive_int($_GET['right_id']) : null;
} catch (DomainException) {
    $leftId = $rightId = null;
}
if ($leftId === null || $rightId === null) {
    $leftId = isset($versions[1]['id']) ? (int) $versions[1]['id'] : (isset($versions[0]['id']) ? (int) $versions[0]['id'] : null);
    $rightId = isset($versions[0]['id']) ? (int) $versions[0]['id'] : null;
}

$comparison = null;
$error = null;
if ($leftId !== null && $rightId !== null) {
    try {
        $comparison = staffing_repository()->compare($registerId, $leftId, $rightId);
    } catch (OutOfBoundsException $exception) {
        $error = $exception->getMessage();
    }
}

function staffing_compare_row_status(array $row): string
{
    if ($row['left_slot_id'] === null) {
        return 'added';
    }
    if ($row['right_slot_id'] === null) {
        return 'removed';
    }
    $pairs = [
        ['left_name', 'right_name'],
        ['left_element_id', 'right_element_id'],
        ['left_position_type_id', 'right_position_type_id'],
        ['left_variant_id', 'right_variant_id'],
        ['left_min_rank', 'right_min_rank'],
        ['left_max_rank', 'right_max_rank'],
        ['left_preferred_rank', 'right_preferred_rank'],
        ['left_state', 'right_state'],
        ['left_vus', 'right_vus'],
    ];
    foreach ($pairs as [$left, $right]) {
        if (($row[$left] ?? null) != ($row[$right] ?? null)) {
            return 'changed';
        }
    }
    return 'unchanged';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сравнение версий — <?= e((string) $register['name']) ?></title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Сравнение штатных версий</h1><p class="site-description"><?= e((string) $register['name']) ?></p></div>
    <a class="secondary-button" href="/admin/staffing/register.php?id=<?= $registerId ?>">К реестру</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <section class="organization-toolbar glass-tile">
        <form method="get" class="organization-filter-form">
            <input type="hidden" name="register_id" value="<?= $registerId ?>">
            <label>Исходная версия<select name="left_id" required><?php foreach ($versions as $version): ?><option value="<?= (int) $version['id'] ?>" <?= $leftId === (int) $version['id'] ? 'selected' : '' ?>>№ <?= (int) $version['version_number'] ?> · <?= e((string) $version['version_label']) ?> · <?= e((string) $version['status']) ?></option><?php endforeach; ?></select></label>
            <label>Сравниваемая версия<select name="right_id" required><?php foreach ($versions as $version): ?><option value="<?= (int) $version['id'] ?>" <?= $rightId === (int) $version['id'] ? 'selected' : '' ?>>№ <?= (int) $version['version_number'] ?> · <?= e((string) $version['version_label']) ?> · <?= e((string) $version['status']) ?></option><?php endforeach; ?></select></label>
            <button class="primary-button" type="submit">Сравнить</button>
        </form>
    </section>
    <?php if ($error !== null): ?><div class="form-message is-error is-visible"><?= e($error) ?></div><?php endif; ?>
    <?php if ($comparison === null): ?>
        <section class="organization-empty glass-tile"><h2>Недостаточно версий</h2><p>Для сравнения нужны две версии штатного реестра.</p></section>
    <?php else: ?>
        <section class="organization-panel glass-tile">
            <h2>Позиции: № <?= (int) $comparison['left']['version_number'] ?> → № <?= (int) $comparison['right']['version_number'] ?></h2>
            <div class="organization-list">
            <?php foreach ($comparison['rows'] as $row): $status = staffing_compare_row_status($row); ?>
                <article class="organization-card"><div><span class="status-badge <?= $status === 'unchanged' ? 'is-muted' : '' ?>"><?= e(match ($status) { 'added' => 'Добавлена', 'removed' => 'Исключена', 'changed' => 'Изменена', default => 'Без изменений' }) ?></span><h3><?= e((string) ($row['right_name'] ?? $row['left_name'] ?? ('Позиция #' . $row['identity_id']))) ?></h3><p>Stable identity: <?= (int) $row['identity_id'] ?></p></div>
                    <dl class="organization-metrics"><div><dt>Оргэлемент</dt><dd><?= e((string) ($row['left_element_id'] ?? '—')) ?> → <?= e((string) ($row['right_element_id'] ?? '—')) ?></dd></div><div><dt>Должность</dt><dd><?= e((string) ($row['left_position_type_id'] ?? '—')) ?> → <?= e((string) ($row['right_position_type_id'] ?? '—')) ?></dd></div><div><dt>Состояние</dt><dd><?= e((string) ($row['left_state'] ?? '—')) ?> → <?= e((string) ($row['right_state'] ?? '—')) ?></dd></div><div><dt>ВУС</dt><dd><?= e((string) ($row['left_vus'] ?? '—')) ?> → <?= e((string) ($row['right_vus'] ?? '—')) ?></dd></div></dl>
                </article>
            <?php endforeach; ?>
            </div>
        </section>
        <section class="organization-panel glass-tile">
            <h2>Документы-основания</h2>
            <div class="organization-list"><article class="organization-card"><div><h3>Версия № <?= (int) $comparison['left']['version_number'] ?></h3><?php foreach ($comparison['left_documents'] as $document): ?><p><?= e((string) $document['document_role']) ?> · <?= e((string) $document['title']) ?> · № <?= e((string) $document['document_number']) ?></p><?php endforeach; ?></div></article><article class="organization-card"><div><h3>Версия № <?= (int) $comparison['right']['version_number'] ?></h3><?php foreach ($comparison['right_documents'] as $document): ?><p><?= e((string) $document['document_role']) ?> · <?= e((string) $document['title']) ?> · № <?= e((string) $document['document_number']) ?></p><?php endforeach; ?></div></article></div>
        </section>
    <?php endif; ?>
    <p class="organization-footnote">Сравнение отражает нормативные snapshots. Фактическая занятость и вакансии в v1 не определяются.</p>
</div></main>
</body>
</html>
