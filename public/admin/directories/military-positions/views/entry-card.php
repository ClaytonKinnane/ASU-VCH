<?php
/** @var array<string,mixed> $entry */
$entryStatus = (string) $entry['status'];
$stateActionLabel = $entryStatus === 'active' ? 'Архивировать должность' : 'Восстановить должность';
$stateReasonLabel = $entryStatus === 'active' ? 'Основание архивирования' : 'Основание восстановления';
$stateSubmitLabel = $entryStatus === 'active' ? 'Архивировать' : 'Восстановить';
$entryActionGroupName = 'military-position-entry-actions-' . (int) $entry['id'];
$entryEditPanelId = 'military-position-entry-edit-' . (int) $entry['id'];
$entryStatePanelId = 'military-position-entry-state-' . (int) $entry['id'];
?>
<article class="military-position-entry-card glass-tile <?= $entryStatus === 'archived' ? 'is-archived' : '' ?>">
    <header><div><span class="status-badge"><?= e(military_positions_status_label($entryStatus)) ?></span><?php if ((int) $entry['is_combined'] === 1): ?><span class="military-position-kind-badge">Составная</span><?php endif; ?><h2><?= e((string) $entry['name']) ?></h2><p><?= e((string) ($entry['full_name'] ?: 'Полное наименование не указано')) ?></p></div><span class="military-position-order">№ <?= (int) $entry['sort_order'] ?></span></header>
    <dl class="military-position-entry-fields"><div><dt>Краткое наименование</dt><dd><?= e((string) ($entry['short_name'] ?: 'Не указано')) ?></dd></div><div><dt>Источник</dt><dd><?= e(military_positions_source_label((string) $entry['source_type'])) ?></dd></div><div><dt>Реквизит источника</dt><dd><?= e((string) ($entry['source_reference'] ?: 'Не указан')) ?></dd></div><div><dt>Используется в штатных позициях</dt><dd><?= (int) $entry['usage_count'] ?></dd></div><?php if ($entry['note'] !== null): ?><div class="span-2"><dt>Примечание</dt><dd><?= e((string) $entry['note']) ?></dd></div><?php endif; ?></dl>
    <?php if ($canManage): ?>
    <div class="military-position-entry-actions">
        <details class="military-position-entry-action military-position-entry-edit-action" name="<?= e($entryActionGroupName) ?>">
            <summary aria-controls="<?= e($entryEditPanelId) ?>">Изменить</summary>
        </details>
        <details class="military-position-entry-action military-position-state-action" name="<?= e($entryActionGroupName) ?>">
            <summary aria-controls="<?= e($entryStatePanelId) ?>"><?= e($stateActionLabel) ?></summary>
        </details>
        <div class="military-position-entry-action-panel military-position-entry-edit-panel" id="<?= e($entryEditPanelId) ?>">
            <?php $entryForm = $entry; $formAction = '/admin/directories/military-positions/entries/update.php'; $submitLabel = 'Сохранить изменения'; $entryId = (int) $entry['id']; $entryRevision = (int) $entry['revision']; require __DIR__ . '/entry-form.php'; ?>
        </div>
        <div class="military-position-entry-action-panel military-position-entry-state-panel" id="<?= e($entryStatePanelId) ?>">
            <form method="post" action="/admin/directories/military-positions/entries/<?= $entryStatus === 'active' ? 'archive' : 'restore' ?>.php" class="military-position-state-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="version_id" value="<?= (int) $version['id'] ?>"><input type="hidden" name="entry_id" value="<?= (int) $entry['id'] ?>"><input type="hidden" name="expected_catalog_revision" value="<?= (int) $version['revision'] ?>"><input type="hidden" name="expected_entry_revision" value="<?= (int) $entry['revision'] ?>"><input type="hidden" name="return_to" value="/admin/directories/military-positions/version.php?id=<?= (int) $version['id'] ?>">
                <label><?= e($stateReasonLabel) ?><input name="change_reason" maxlength="1000" required></label>
                <button class="secondary-button" type="submit"><?= e($stateSubmitLabel) ?></button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</article>
