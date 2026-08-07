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

function staffing_compare_version_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Черновик',
        'approved' => 'Утверждена',
        'active' => 'Действующая',
        'superseded' => 'Заменена',
        'cancelled' => 'Отменена',
        default => 'Неизвестное состояние',
    };
}

function staffing_compare_normative_state_label(?string $state): string
{
    return match ($state) {
        'active' => 'Действующая',
        'suspended' => 'Приостановлена',
        'closed' => 'Закрыта',
        null, '' => '—',
        default => 'Неизвестное состояние',
    };
}

function staffing_compare_document_role_label(string $role): string
{
    return match ($role) {
        'primary_basis' => 'Основное основание',
        'additional_basis' => 'Дополнительное основание',
        'amendment' => 'Изменение',
        default => 'Основание',
    };
}

function staffing_compare_vus_role_label(string $role): string
{
    return match ($role) {
        'required' => 'Требуется',
        'allowed' => 'Допускается',
        'preferred' => 'Предпочтительно',
        default => 'Требование',
    };
}

/** @param list<array<string,mixed>> $rows @return array<int,string> */
function staffing_compare_organization_labels(array $rows): array
{
    $result = [];
    foreach ($rows as $row) {
        $id = (int) ($row['organizational_structure_element_id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $name = trim((string) ($row['name'] ?? ''));
        $result[$id] = $name !== '' ? $name : ('Элемент № ' . $id);
    }
    return $result;
}

/** @param list<array<string,mixed>> $rows @return array<int,string> */
function staffing_compare_position_labels(array $rows): array
{
    $result = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $name = trim((string) ($row['name'] ?? ''));
        $result[$id] = $name !== '' ? $name : ('Должность № ' . $id);
    }
    return $result;
}

/** @param list<array<string,mixed>> $rows @return array<int,string> */
function staffing_compare_vus_labels(array $rows): array
{
    $result = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $label = trim((string) ($row['raw_identifier'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($row['qualification_name'] ?? ''));
        }
        if ($label === '') {
            $label = trim((string) ($row['code'] ?? ''));
        }
        $result[$id] = $label !== '' ? $label : ('Запись ВУС № ' . $id);
    }
    return $result;
}

/** @param array<int,string> $labels */
function staffing_compare_vus_summary(?string $summary, array $labels): string
{
    $summary = trim((string) $summary);
    if ($summary === '') {
        return '—';
    }
    $parts = [];
    foreach (explode('|', $summary) as $item) {
        [$role, $id] = array_pad(explode(':', $item, 2), 2, '');
        $numericId = ctype_digit($id) ? (int) $id : 0;
        $value = $numericId > 0 ? ($labels[$numericId] ?? ('Запись ВУС № ' . $numericId)) : '—';
        $parts[] = staffing_compare_vus_role_label($role) . ': ' . $value;
    }
    return implode(' · ', $parts);
}

/** @param array<int,string> $labels */
function staffing_compare_reference_label(mixed $id, array $labels, string $fallbackPrefix): string
{
    if ($id === null || $id === '') {
        return '—';
    }
    $numericId = (int) $id;
    return $labels[$numericId] ?? ($fallbackPrefix . ' № ' . $numericId);
}

$leftOrganizationLabels = [];
$rightOrganizationLabels = [];
$leftPositionLabels = [];
$rightPositionLabels = [];
$leftVusLabels = [];
$rightVusLabels = [];
if ($comparison !== null) {
    $leftOrganizationLabels = staffing_compare_organization_labels(
        staffing_repository()->organizationNodes((int) $comparison['left']['organizational_structure_version_id'])
    );
    $rightOrganizationLabels = staffing_compare_organization_labels(
        staffing_repository()->organizationNodes((int) $comparison['right']['organizational_structure_version_id'])
    );
    $leftPositionLabels = staffing_compare_position_labels(
        staffing_repository()->positionTypes((int) $comparison['left']['position_catalog_version_id'])
    );
    $rightPositionLabels = staffing_compare_position_labels(
        staffing_repository()->positionTypes((int) $comparison['right']['position_catalog_version_id'])
    );
    $leftVusLabels = staffing_compare_vus_labels(
        staffing_repository()->vusDisclosures((int) $comparison['left']['vus_catalog_version_id'])
    );
    $rightVusLabels = staffing_compare_vus_labels(
        staffing_repository()->vusDisclosures((int) $comparison['right']['vus_catalog_version_id'])
    );
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
            <label>Исходная версия<select name="left_id" required><?php foreach ($versions as $version): ?><option value="<?= (int) $version['id'] ?>" <?= $leftId === (int) $version['id'] ? 'selected' : '' ?>>№ <?= (int) $version['version_number'] ?> · <?= e((string) $version['version_label']) ?> · <?= e(staffing_compare_version_status_label((string) $version['status'])) ?></option><?php endforeach; ?></select></label>
            <label>Сравниваемая версия<select name="right_id" required><?php foreach ($versions as $version): ?><option value="<?= (int) $version['id'] ?>" <?= $rightId === (int) $version['id'] ? 'selected' : '' ?>>№ <?= (int) $version['version_number'] ?> · <?= e((string) $version['version_label']) ?> · <?= e(staffing_compare_version_status_label((string) $version['status'])) ?></option><?php endforeach; ?></select></label>
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
                <article class="organization-card"><div><span class="status-badge <?= $status === 'unchanged' ? 'is-muted' : '' ?>"><?= e(match ($status) { 'added' => 'Добавлена', 'removed' => 'Исключена', 'changed' => 'Изменена', default => 'Без изменений' }) ?></span><h3><?= e((string) ($row['right_name'] ?? $row['left_name'] ?? ('Позиция № ' . $row['identity_id']))) ?></h3><p>Стабильный идентификатор позиции: <?= (int) $row['identity_id'] ?></p></div>
                    <dl class="organization-metrics">
                        <div><dt>Организационный элемент</dt><dd><?= e(staffing_compare_reference_label($row['left_element_id'] ?? null, $leftOrganizationLabels, 'Элемент')) ?> → <?= e(staffing_compare_reference_label($row['right_element_id'] ?? null, $rightOrganizationLabels, 'Элемент')) ?></dd></div>
                        <div><dt>Должность</dt><dd><?= e(staffing_compare_reference_label($row['left_position_type_id'] ?? null, $leftPositionLabels, 'Должность')) ?> → <?= e(staffing_compare_reference_label($row['right_position_type_id'] ?? null, $rightPositionLabels, 'Должность')) ?></dd></div>
                        <div><dt>Нормативное состояние</dt><dd><?= e(staffing_compare_normative_state_label(isset($row['left_state']) ? (string) $row['left_state'] : null)) ?> → <?= e(staffing_compare_normative_state_label(isset($row['right_state']) ? (string) $row['right_state'] : null)) ?></dd></div>
                        <div><dt>ВУС</dt><dd><?= e(staffing_compare_vus_summary(isset($row['left_vus']) ? (string) $row['left_vus'] : null, $leftVusLabels)) ?> → <?= e(staffing_compare_vus_summary(isset($row['right_vus']) ? (string) $row['right_vus'] : null, $rightVusLabels)) ?></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
            </div>
        </section>
        <section class="organization-panel glass-tile">
            <h2>Документы-основания</h2>
            <div class="organization-list"><article class="organization-card"><div><h3>Версия № <?= (int) $comparison['left']['version_number'] ?></h3><?php foreach ($comparison['left_documents'] as $document): ?><p><?= e(staffing_compare_document_role_label((string) $document['document_role'])) ?> · <?= e((string) $document['title']) ?> · № <?= e((string) $document['document_number']) ?></p><?php endforeach; ?></div></article><article class="organization-card"><div><h3>Версия № <?= (int) $comparison['right']['version_number'] ?></h3><?php foreach ($comparison['right_documents'] as $document): ?><p><?= e(staffing_compare_document_role_label((string) $document['document_role'])) ?> · <?= e((string) $document['title']) ?> · № <?= e((string) $document['document_number']) ?></p><?php endforeach; ?></div></article></div>
        </section>
    <?php endif; ?>
    <p class="organization-footnote">Сравнение отражает нормативные снимки версий. Фактическая занятость и вакансии в v1 не определяются.</p>
</div></main>
</body>
</html>
