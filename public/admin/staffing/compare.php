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

function staffing_compare_status_label(string $status): string
{
    return match ($status) {
        'added' => 'Добавлена',
        'removed' => 'Исключена',
        'changed' => 'Изменена',
        default => 'Без изменений',
    };
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
        'removed' => 'Исключена',
        'abolished' => 'Упразднена',
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

function staffing_compare_document_type_label(string $type): string
{
    return match ($type) {
        'staffing_order' => 'Штатный приказ',
        'amendment_order' => 'Приказ об изменении',
        'approval_act' => 'Акт утверждения',
        'other_basis' => 'Иное основание',
        default => 'Иное основание',
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

function staffing_compare_date_label(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y') : $value;
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
function staffing_compare_variant_labels(array $rows): array
{
    $result = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $label = trim((string) ($row['designation'] ?? ''));
        $result[$id] = $label !== '' ? $label : ('Вариант № ' . $id);
    }
    return $result;
}

/** @param list<array<string,mixed>> $rows @return array<int,string> */
function staffing_compare_rank_labels(array $rows): array
{
    $result = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $label = trim((string) ($row['troop_name'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($row['naval_name'] ?? ''));
        }
        $result[$id] = $label !== '' ? $label : ('Звание № ' . $id);
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

/** @return list<array{label:string,left:string,right:string,changed:bool}> */
function staffing_compare_slot_fields(
    array $row,
    array $leftOrganizationLabels,
    array $rightOrganizationLabels,
    array $leftPositionLabels,
    array $rightPositionLabels,
    array $leftVariantLabels,
    array $rightVariantLabels,
    array $leftRankLabels,
    array $rightRankLabels,
    array $leftVusLabels,
    array $rightVusLabels
): array {
    $fields = [
        ['Наименование', (string) ($row['left_name'] ?? '—'), (string) ($row['right_name'] ?? '—')],
        ['Организационный элемент', staffing_compare_reference_label($row['left_element_id'] ?? null, $leftOrganizationLabels, 'Элемент'), staffing_compare_reference_label($row['right_element_id'] ?? null, $rightOrganizationLabels, 'Элемент')],
        ['Тип должности', staffing_compare_reference_label($row['left_position_type_id'] ?? null, $leftPositionLabels, 'Должность'), staffing_compare_reference_label($row['right_position_type_id'] ?? null, $rightPositionLabels, 'Должность')],
        ['Вариант должности', staffing_compare_reference_label($row['left_variant_id'] ?? null, $leftVariantLabels, 'Вариант'), staffing_compare_reference_label($row['right_variant_id'] ?? null, $rightVariantLabels, 'Вариант')],
        ['Минимальное звание', staffing_compare_reference_label($row['left_min_rank'] ?? null, $leftRankLabels, 'Звание'), staffing_compare_reference_label($row['right_min_rank'] ?? null, $rightRankLabels, 'Звание')],
        ['Максимальное звание', staffing_compare_reference_label($row['left_max_rank'] ?? null, $leftRankLabels, 'Звание'), staffing_compare_reference_label($row['right_max_rank'] ?? null, $rightRankLabels, 'Звание')],
        ['Предпочтительное звание', staffing_compare_reference_label($row['left_preferred_rank'] ?? null, $leftRankLabels, 'Звание'), staffing_compare_reference_label($row['right_preferred_rank'] ?? null, $rightRankLabels, 'Звание')],
        ['Нормативное состояние', staffing_compare_normative_state_label(isset($row['left_state']) ? (string) $row['left_state'] : null), staffing_compare_normative_state_label(isset($row['right_state']) ? (string) $row['right_state'] : null)],
        ['ВУС', staffing_compare_vus_summary(isset($row['left_vus']) ? (string) $row['left_vus'] : null, $leftVusLabels), staffing_compare_vus_summary(isset($row['right_vus']) ? (string) $row['right_vus'] : null, $rightVusLabels)],
    ];
    return array_map(static fn(array $field): array => [
        'label' => $field[0],
        'left' => $field[1] !== '' ? $field[1] : '—',
        'right' => $field[2] !== '' ? $field[2] : '—',
        'changed' => $field[1] !== $field[2],
    ], $fields);
}

/** @return list<array{label:string,left:string,right:string,changed:bool}> */
function staffing_compare_document_fields(?array $left, ?array $right): array
{
    $leftValues = $left === null ? [] : [
        'role' => staffing_compare_document_role_label((string) ($left['document_role'] ?? '')),
        'type' => staffing_compare_document_type_label((string) ($left['document_type'] ?? '')),
        'number' => (string) ($left['document_number'] ?? '—'),
        'date' => staffing_compare_date_label(isset($left['document_date']) ? (string) $left['document_date'] : null),
        'title' => (string) ($left['title'] ?? '—'),
        'sort' => (string) ($left['sort_order'] ?? '—'),
        'note' => trim((string) ($left['note'] ?? '')) !== '' ? (string) $left['note'] : '—',
    ];
    $rightValues = $right === null ? [] : [
        'role' => staffing_compare_document_role_label((string) ($right['document_role'] ?? '')),
        'type' => staffing_compare_document_type_label((string) ($right['document_type'] ?? '')),
        'number' => (string) ($right['document_number'] ?? '—'),
        'date' => staffing_compare_date_label(isset($right['document_date']) ? (string) $right['document_date'] : null),
        'title' => (string) ($right['title'] ?? '—'),
        'sort' => (string) ($right['sort_order'] ?? '—'),
        'note' => trim((string) ($right['note'] ?? '')) !== '' ? (string) $right['note'] : '—',
    ];
    $definitions = [
        'role' => 'Роль',
        'type' => 'Тип документа',
        'number' => 'Номер',
        'date' => 'Дата',
        'title' => 'Наименование',
        'sort' => 'Порядок',
        'note' => 'Примечание',
    ];
    $result = [];
    foreach ($definitions as $key => $label) {
        $leftValue = $leftValues[$key] ?? '—';
        $rightValue = $rightValues[$key] ?? '—';
        $result[] = [
            'label' => $label,
            'left' => $leftValue,
            'right' => $rightValue,
            'changed' => $leftValue !== $rightValue,
        ];
    }
    return $result;
}

/** @param list<array{label:string,left:string,right:string,changed:bool}> $fields */
function staffing_compare_changed_fields(array $fields): array
{
    return array_values(array_filter($fields, static fn(array $field): bool => $field['changed']));
}

/** @param list<array{label:string,left:string,right:string,changed:bool}> $fields */
function staffing_compare_unchanged_fields(array $fields): array
{
    return array_values(array_filter($fields, static fn(array $field): bool => !$field['changed']));
}

$leftOrganizationLabels = [];
$rightOrganizationLabels = [];
$leftPositionLabels = [];
$rightPositionLabels = [];
$leftVariantLabels = [];
$rightVariantLabels = [];
$leftRankLabels = [];
$rightRankLabels = [];
$leftVusLabels = [];
$rightVusLabels = [];
$positionCounts = ['added' => 0, 'removed' => 0, 'changed' => 0, 'unchanged' => 0];
$documentPairs = [];
$documentCounts = ['added' => 0, 'removed' => 0, 'changed' => 0, 'unchanged' => 0];

if ($comparison !== null) {
    $leftOrganizationLabels = staffing_compare_organization_labels(staffing_repository()->organizationNodes((int) $comparison['left']['organizational_structure_version_id']));
    $rightOrganizationLabels = staffing_compare_organization_labels(staffing_repository()->organizationNodes((int) $comparison['right']['organizational_structure_version_id']));
    $leftPositionLabels = staffing_compare_position_labels(staffing_repository()->positionTypes((int) $comparison['left']['position_catalog_version_id']));
    $rightPositionLabels = staffing_compare_position_labels(staffing_repository()->positionTypes((int) $comparison['right']['position_catalog_version_id']));
    $leftVariantLabels = staffing_compare_variant_labels(staffing_repository()->positionVariants((int) $comparison['left']['position_catalog_version_id']));
    $rightVariantLabels = staffing_compare_variant_labels(staffing_repository()->positionVariants((int) $comparison['right']['position_catalog_version_id']));
    $leftRankLabels = staffing_compare_rank_labels(staffing_repository()->ranks((int) $comparison['left']['rank_catalog_version_id']));
    $rightRankLabels = staffing_compare_rank_labels(staffing_repository()->ranks((int) $comparison['right']['rank_catalog_version_id']));
    $leftVusLabels = staffing_compare_vus_labels(staffing_repository()->vusDisclosures((int) $comparison['left']['vus_catalog_version_id']));
    $rightVusLabels = staffing_compare_vus_labels(staffing_repository()->vusDisclosures((int) $comparison['right']['vus_catalog_version_id']));

    foreach ($comparison['rows'] as $row) {
        ++$positionCounts[staffing_compare_row_status($row)];
    }

    $documentCount = max(count($comparison['left_documents']), count($comparison['right_documents']));
    for ($index = 0; $index < $documentCount; ++$index) {
        $leftDocument = $comparison['left_documents'][$index] ?? null;
        $rightDocument = $comparison['right_documents'][$index] ?? null;
        $fields = staffing_compare_document_fields($leftDocument, $rightDocument);
        if ($leftDocument === null) {
            $status = 'added';
        } elseif ($rightDocument === null) {
            $status = 'removed';
        } elseif (staffing_compare_changed_fields($fields) !== []) {
            $status = 'changed';
        } else {
            $status = 'unchanged';
        }
        ++$documentCounts[$status];
        $documentPairs[] = ['left' => $leftDocument, 'right' => $rightDocument, 'fields' => $fields, 'status' => $status];
    }
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
    <style>
        .staffing-compare-toolbar-form { grid-template-columns: minmax(260px, 1fr) minmax(260px, 1fr) auto; }
        .staffing-compare-summary { display: grid; gap: 14px; }
        .staffing-compare-summary h2 { margin: 0; }
        .staffing-compare-summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .staffing-compare-summary-group { padding: 14px; border: 1px solid var(--input-border); border-radius: var(--control-radius); background: var(--input-background); }
        .staffing-compare-summary-group h3 { margin: 0 0 10px; }
        .staffing-compare-counts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .staffing-compare-count { padding: 9px; border: 1px solid var(--input-border); border-radius: var(--control-radius); }
        .staffing-compare-count span { display: block; color: var(--text-secondary); font-size: 12px; }
        .staffing-compare-count strong { display: block; margin-top: 4px; font-size: 20px; }
        .staffing-compare-list { display: grid; gap: 12px; }
        .staffing-compare-item { padding: 16px; border: 1px solid var(--input-border); border-radius: var(--control-radius); background: var(--input-background); }
        .staffing-compare-item__header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        .staffing-compare-item__header h3 { margin: 7px 0 0; }
        .staffing-compare-item__meta { color: var(--text-secondary); font-size: 13px; }
        .staffing-compare-section-title { margin: 16px 0 8px; font-size: 14px; }
        .staffing-compare-columns, .staffing-compare-field { display: grid; grid-template-columns: minmax(150px, .7fr) repeat(2, minmax(0, 1fr)); gap: 8px; }
        .staffing-compare-columns { padding: 0 10px 6px; color: var(--text-secondary); font-size: 12px; font-weight: 700; }
        .staffing-compare-field { margin-top: 6px; }
        .staffing-compare-field > div { min-width: 0; padding: 9px 10px; border: 1px solid var(--input-border); border-radius: var(--control-radius); overflow-wrap: anywhere; }
        .staffing-compare-field__label { color: var(--text-secondary); font-size: 12px; font-weight: 700; }
        .staffing-compare-field.is-changed > div { border-color: var(--focus-color); background: color-mix(in srgb, var(--focus-color) 8%, var(--input-background)); }
        .staffing-compare-unchanged { margin-top: 12px; }
        .staffing-compare-unchanged > summary { color: var(--text-secondary); font-weight: 700; }
        .staffing-compare-unchanged[open] > summary { margin-bottom: 8px; }
        .staffing-compare-empty { margin: 8px 0 0; color: var(--text-secondary); }
        @media (max-width: 900px) {
            .staffing-compare-toolbar-form, .staffing-compare-summary-grid { grid-template-columns: 1fr; }
            .staffing-compare-counts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 680px) {
            .staffing-compare-columns { display: none; }
            .staffing-compare-field { grid-template-columns: 1fr; }
            .staffing-compare-field > div:nth-child(2)::before { content: "Исходная версия: "; color: var(--text-secondary); font-weight: 700; }
            .staffing-compare-field > div:nth-child(3)::before { content: "Сравниваемая версия: "; color: var(--text-secondary); font-weight: 700; }
        }
    </style>
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Сравнение штатных версий</h1><p class="site-description"><?= e((string) $register['name']) ?></p></div>
    <a class="secondary-button" href="/admin/staffing/register.php?id=<?= $registerId ?>">К реестру</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <section class="organization-toolbar glass-tile">
        <form method="get" class="organization-filter-form staffing-compare-toolbar-form">
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
        <section class="organization-panel glass-tile staffing-compare-summary">
            <h2>Итог сравнения</h2>
            <div class="staffing-compare-summary-grid">
                <div class="staffing-compare-summary-group"><h3>Штатные позиции</h3><div class="staffing-compare-counts">
                    <div class="staffing-compare-count"><span>Изменено</span><strong><?= $positionCounts['changed'] ?></strong></div>
                    <div class="staffing-compare-count"><span>Добавлено</span><strong><?= $positionCounts['added'] ?></strong></div>
                    <div class="staffing-compare-count"><span>Исключено</span><strong><?= $positionCounts['removed'] ?></strong></div>
                    <div class="staffing-compare-count"><span>Без изменений</span><strong><?= $positionCounts['unchanged'] ?></strong></div>
                </div></div>
                <div class="staffing-compare-summary-group"><h3>Документы-основания</h3><div class="staffing-compare-counts">
                    <div class="staffing-compare-count"><span>Изменено</span><strong><?= $documentCounts['changed'] ?></strong></div>
                    <div class="staffing-compare-count"><span>Добавлено</span><strong><?= $documentCounts['added'] ?></strong></div>
                    <div class="staffing-compare-count"><span>Исключено</span><strong><?= $documentCounts['removed'] ?></strong></div>
                    <div class="staffing-compare-count"><span>Без изменений</span><strong><?= $documentCounts['unchanged'] ?></strong></div>
                </div></div>
            </div>
        </section>

        <section class="organization-panel glass-tile">
            <div class="organization-section-heading"><div><h2>Штатные позиции</h2><p>Показываются различия между версиями № <?= (int) $comparison['left']['version_number'] ?> и № <?= (int) $comparison['right']['version_number'] ?>.</p></div></div>
            <div class="staffing-compare-list">
            <?php foreach ($comparison['rows'] as $row):
                $status = staffing_compare_row_status($row);
                $fields = staffing_compare_slot_fields($row, $leftOrganizationLabels, $rightOrganizationLabels, $leftPositionLabels, $rightPositionLabels, $leftVariantLabels, $rightVariantLabels, $leftRankLabels, $rightRankLabels, $leftVusLabels, $rightVusLabels);
                $changedFields = staffing_compare_changed_fields($fields);
                $unchangedFields = staffing_compare_unchanged_fields($fields);
            ?>
                <article class="staffing-compare-item">
                    <div class="staffing-compare-item__header">
                        <div><span class="status-badge <?= $status === 'unchanged' ? 'is-muted' : '' ?>"><?= e(staffing_compare_status_label($status)) ?></span><h3>Штатная позиция № <?= (int) $row['identity_id'] ?></h3></div>
                        <span class="staffing-compare-item__meta">Стабильный идентификатор: <?= (int) $row['identity_id'] ?></span>
                    </div>
                    <?php if ($changedFields !== []): ?>
                        <h4 class="staffing-compare-section-title"><?= $status === 'unchanged' ? 'Параметры' : 'Изменённые параметры' ?></h4>
                        <div class="staffing-compare-columns"><div>Параметр</div><div>Версия № <?= (int) $comparison['left']['version_number'] ?></div><div>Версия № <?= (int) $comparison['right']['version_number'] ?></div></div>
                        <?php foreach ($changedFields as $field): ?><div class="staffing-compare-field is-changed"><div class="staffing-compare-field__label"><?= e($field['label']) ?></div><div><?= e($field['left']) ?></div><div><?= e($field['right']) ?></div></div><?php endforeach; ?>
                    <?php elseif ($status === 'unchanged'): ?>
                        <p class="staffing-compare-empty">Различий по позиции нет.</p>
                    <?php endif; ?>
                    <?php if ($unchangedFields !== []): ?>
                        <details class="staffing-compare-unchanged"><summary>Показать параметры без изменений (<?= count($unchangedFields) ?>)</summary>
                            <div class="staffing-compare-columns"><div>Параметр</div><div>Версия № <?= (int) $comparison['left']['version_number'] ?></div><div>Версия № <?= (int) $comparison['right']['version_number'] ?></div></div>
                            <?php foreach ($unchangedFields as $field): ?><div class="staffing-compare-field"><div class="staffing-compare-field__label"><?= e($field['label']) ?></div><div><?= e($field['left']) ?></div><div><?= e($field['right']) ?></div></div><?php endforeach; ?>
                        </details>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            </div>
        </section>

        <section class="organization-panel glass-tile">
            <div class="organization-section-heading"><div><h2>Документы-основания</h2><p>Сопоставление выполняется в порядке документов внутри каждой версии.</p></div></div>
            <?php if ($documentPairs === []): ?><p class="staffing-compare-empty">В сравниваемых версиях документы-основания отсутствуют.</p><?php else: ?>
            <div class="staffing-compare-list">
                <?php foreach ($documentPairs as $index => $pair):
                    $changedFields = staffing_compare_changed_fields($pair['fields']);
                    $unchangedFields = staffing_compare_unchanged_fields($pair['fields']);
                ?>
                <article class="staffing-compare-item">
                    <div class="staffing-compare-item__header"><div><span class="status-badge <?= $pair['status'] === 'unchanged' ? 'is-muted' : '' ?>"><?= e(staffing_compare_status_label($pair['status'])) ?></span><h3>Документ-основание № <?= $index + 1 ?></h3></div></div>
                    <?php if ($changedFields !== []): ?>
                        <h4 class="staffing-compare-section-title">Изменённые параметры</h4>
                        <div class="staffing-compare-columns"><div>Параметр</div><div>Версия № <?= (int) $comparison['left']['version_number'] ?></div><div>Версия № <?= (int) $comparison['right']['version_number'] ?></div></div>
                        <?php foreach ($changedFields as $field): ?><div class="staffing-compare-field is-changed"><div class="staffing-compare-field__label"><?= e($field['label']) ?></div><div><?= e($field['left']) ?></div><div><?= e($field['right']) ?></div></div><?php endforeach; ?>
                    <?php else: ?><p class="staffing-compare-empty">Различий по документу нет.</p><?php endif; ?>
                    <?php if ($unchangedFields !== []): ?>
                        <details class="staffing-compare-unchanged"><summary>Показать параметры без изменений (<?= count($unchangedFields) ?>)</summary>
                            <div class="staffing-compare-columns"><div>Параметр</div><div>Версия № <?= (int) $comparison['left']['version_number'] ?></div><div>Версия № <?= (int) $comparison['right']['version_number'] ?></div></div>
                            <?php foreach ($unchangedFields as $field): ?><div class="staffing-compare-field"><div class="staffing-compare-field__label"><?= e($field['label']) ?></div><div><?= e($field['left']) ?></div><div><?= e($field['right']) ?></div></div><?php endforeach; ?>
                        </details>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <p class="organization-footnote">Сравнение отражает нормативные снимки версий. Фактическая занятость и вакансии в v1 не определяются.</p>
</div></main>
</body>
</html>
