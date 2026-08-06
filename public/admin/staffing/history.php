<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Staffing/functions.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

require_permission('staffing.registers.history');
$registerId = staffing_get_positive_int('register_id');
try {
    $register = staffing_repository()->register($registerId);
    $events = staffing_repository()->history($registerId);
} catch (OutOfBoundsException) {
    http_response_code(404);
    exit('Штатный реестр не найден.');
}

$historyEventLabels = [
    'register.created' => 'Реестр создан',
    'register.updated' => 'Карточка реестра изменена',
    'register.archived' => 'Реестр архивирован',
    'register.restored' => 'Реестр восстановлен',
    'version.created' => 'Версия создана',
    'version.approved' => 'Версия утверждена',
    'version.cancelled' => 'Версия отменена',
    'version.superseded' => 'Версия заменена',
    'version.activated' => 'Версия введена в действие',
    'document.created' => 'Документ добавлен',
    'document.updated' => 'Документ изменён',
    'document.copied_on_write' => 'Документ скопирован для изменения',
    'document.unlinked' => 'Документ исключён из версии',
    'slot.created' => 'Штатная позиция добавлена',
    'slot.updated' => 'Штатная позиция изменена',
    'slot.removed' => 'Штатная позиция удалена из черновика',
];
$historyTargetLabels = [
    'register' => 'Штатный реестр',
    'version' => 'Штатная версия',
    'document' => 'Документ-основание',
    'slot' => 'Штатная позиция',
];
$historyKeyLabels = [
    'id' => 'Идентификатор',
    'code' => 'Код',
    'name' => 'Наименование',
    'status' => 'Состояние',
    'revision' => 'Ревизия',
    'note' => 'Примечание',
    'linked' => 'Связан с версией',
    'staffing_register_id' => 'Идентификатор штатного реестра',
    'staffing_version_id' => 'Идентификатор штатной версии',
    'staffing_slot_identity_id' => 'Стабильный идентификатор позиции',
    'organizational_structure_id' => 'Идентификатор организационной структуры',
    'organizational_structure_version_id' => 'Идентификатор версии организационной структуры',
    'organizational_structure_element_id' => 'Идентификатор организационного элемента',
    'position_catalog_version_id' => 'Идентификатор версии справочника должностей',
    'position_type_id' => 'Идентификатор типа должности',
    'position_variant_id' => 'Идентификатор варианта должности',
    'rank_catalog_version_id' => 'Идентификатор версии справочника званий',
    'vus_catalog_version_id' => 'Идентификатор версии справочника ВУС',
    'minimum_rank_id' => 'Минимальное звание',
    'maximum_rank_id' => 'Максимальное звание',
    'preferred_rank_id' => 'Предпочтительное звание',
    'internal_code' => 'Внутренний код',
    'display_name' => 'Наименование позиции',
    'normative_state' => 'Нормативное состояние',
    'sort_order' => 'Порядок',
    'document_type' => 'Тип документа',
    'document_date' => 'Дата документа',
    'document_number' => 'Номер документа',
    'title' => 'Наименование документа',
    'document_role' => 'Роль документа',
    'version_number' => 'Номер версии',
    'version_label' => 'Обозначение версии',
    'based_on_version_id' => 'Базовая версия',
    'effective_from' => 'Дата начала действия',
    'effective_to' => 'Дата окончания действия',
    'change_reason' => 'Основание изменения',
    'vus_requirements' => 'Требования ВУС',
    'public_disclosure_id' => 'Публичная запись ВУС',
    'requirement_role' => 'Роль требования ВУС',
    'created_by' => 'Создал',
    'created_at' => 'Создано',
    'updated_by' => 'Изменил',
    'updated_at' => 'Изменено',
    'approved_by' => 'Утвердил',
    'approved_at' => 'Утверждено',
    'activated_by' => 'Ввёл в действие',
    'activated_at' => 'Введено в действие',
    'cancelled_by' => 'Отменил',
    'cancelled_at' => 'Отменено',
    'cancellation_reason' => 'Основание отмены',
    'archived_by' => 'Архивировал',
    'archived_at' => 'Архивировано',
    'archive_reason' => 'Основание архивирования',
    'restored_by' => 'Восстановил',
    'restored_at' => 'Восстановлено',
    'restore_reason' => 'Основание восстановления',
];
$historyValueLabels = [
    'draft' => 'Черновик',
    'approved' => 'Утверждено',
    'active' => 'Активно',
    'superseded' => 'Заменено',
    'cancelled' => 'Отменено',
    'canceled' => 'Отменено',
    'archived' => 'В архиве',
    'suspended' => 'Приостановлено',
    'closed' => 'Закрыто',
    'staffing_order' => 'Штатный приказ',
    'amendment_order' => 'Приказ об изменении',
    'approval_act' => 'Акт утверждения',
    'other_basis' => 'Иное основание',
    'primary_basis' => 'Основное основание',
    'additional_basis' => 'Дополнительное основание',
    'amendment' => 'Изменение',
    'required' => 'Требуется',
    'allowed' => 'Допускается',
    'preferred' => 'Предпочтительно',
];

$formatHistoryTimestamp = static function (string $value): string {
    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y H:i:s') : $value;
};

$translateHistoryState = null;
$translateHistoryState = static function (mixed $value) use (
    &$translateHistoryState,
    $historyKeyLabels,
    $historyValueLabels
): mixed {
    if (is_array($value)) {
        $translated = [];
        foreach ($value as $key => $item) {
            $displayKey = is_string($key) ? ($historyKeyLabels[$key] ?? $key) : $key;
            $translated[$displayKey] = $translateHistoryState($item);
        }
        return $translated;
    }
    if (is_bool($value)) {
        return $value ? 'Да' : 'Нет';
    }
    if (!is_string($value)) {
        return $value;
    }
    if (isset($historyValueLabels[$value])) {
        return $historyValueLabels[$value];
    }
    if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $value) === 1) {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTimeImmutable ? $date->format('d.m.Y') : $value;
    }
    if (preg_match('/\A\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\z/D', $value) === 1) {
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        return $date instanceof DateTimeImmutable ? $date->format('d.m.Y H:i:s') : $value;
    }
    return $value;
};

$formatHistoryState = static function (?string $json) use ($translateHistoryState): ?string {
    if ($json === null || $json === '') {
        return null;
    }
    try {
        $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return null;
        }
        return json_encode(
            $translateHistoryState($decoded),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        return null;
    }
};
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История — <?= e((string) $register['name']) ?></title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">История штатного реестра</h1><p class="site-description"><?= e((string) $register['name']) ?></p></div>
    <a class="secondary-button" href="/admin/staffing/register.php?id=<?= $registerId ?>">К реестру</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <section class="organization-history-list" aria-label="Предметная история">
    <?php if ($events === []): ?>
        <article class="organization-empty glass-tile"><h2>События отсутствуют</h2></article>
    <?php else: foreach ($events as $event):
        $before = $formatHistoryState($event['before_state']);
        $after = $formatHistoryState($event['after_state']);
        $eventCode = (string) $event['event_type'];
        $targetCode = (string) $event['target_type'];
        $eventLabel = $historyEventLabels[$eventCode] ?? 'Событие предметной истории';
        $targetLabel = $historyTargetLabels[$targetCode] ?? 'Объект истории';
    ?>
        <article class="glass-tile" data-event-code="<?= e($eventCode) ?>" data-target-code="<?= e($targetCode) ?>">
            <div>
                <div>
                    <span class="status-badge"><?= e($eventLabel) ?></span>
                    <h2><?= e($targetLabel) ?><?= $event['target_id'] !== null ? ' № ' . (int) $event['target_id'] : '' ?></h2>
                    <p><?= e($formatHistoryTimestamp((string) $event['created_at'])) ?> · <?= e((string) ($event['actor_name'] ?? 'Системный субъект')) ?><?= $event['version_number'] !== null ? ' · версия № ' . (int) $event['version_number'] : '' ?></p>
                    <?php if ($event['reason'] !== null): ?><p><?= nl2br(e((string) $event['reason'])) ?></p><?php endif; ?>
                </div>
            </div>
            <?php if ($before !== null || $after !== null): ?>
                <details class="organization-history-state">
                    <summary>Изменения</summary>
                    <div>
                        <section><h3>До</h3><pre><?= e((string) ($before ?? '—')) ?></pre></section>
                        <section><h3>После</h3><pre><?= e((string) ($after ?? '—')) ?></pre></section>
                    </div>
                </details>
            <?php endif; ?>
        </article>
    <?php endforeach; endif; ?>
    </section>
    <p class="organization-footnote">История является неизменяемым предметным журналом и не заменяет будущий общий аудит безопасности.</p>
</div></main>
</body>
</html>
