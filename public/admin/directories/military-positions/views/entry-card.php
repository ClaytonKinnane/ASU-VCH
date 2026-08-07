<?php
/** @var array<string,mixed> $entry */
$entryStatus = (string) $entry['status'];
?>
<article class="military-position-entry-card glass-tile <?= $entryStatus === 'archived' ? 'is-archived' : '' ?>">
    <header><div><span class="status-badge"><?= e(military_positions_status_label($entryStatus)) ?></span><?php if ((int) $entry['is_combined'] === 1): ?><span class="military-position-kind-badge">Составная</span><?php endif; ?><h2><?= e((string) $entry['name']) ?></h2><p><?= e((string) ($entry['full_name'] ?: 'Полное наименование не указано')) ?></p></div><span class="military-position-order">№ <?= (int) $entry['sort_order'] ?></span></header>
    <dl class="military-position-entry-fields"><div><dt>Краткое наименование</dt><dd><?= e((string) ($entry['short_name'] ?: 'Не указано')) ?></dd></div><div><dt>Источник</dt><dd><?= e(military_positions_source_label((string) $entry['source_type'])) ?></dd></div><div><dt>Реквизит источника</dt><dd><?= e((string) ($entry['source_reference'] ?: 'Не указан')) ?></dd></div><div><dt>Используется в штатных позициях</dt><dd><?= (int) $entry['usage_count'] ?></dd></div><?php if ($entry['note'] !== null): ?><div class="span-2"><dt>Примечание</dt><dd><?= e((string) $entry['note']) ?></dd></div><?php endif; ?></dl>
    <?php if ($canManage): ?>
    <div class="military-position-entry-actions">
        <details><summary>Изменить</summary><?php $entryForm = $entry; $formAction = '/admin/directories/military-positions/entries/update.php'; $submitLabel = 'Сохранить изменения'; $entryId = (int) $entry['id']; $entryRevision = (int) $entry['revision']; require __DIR__ . '/entry-form.php'; ?></details>
        <form method="post" action="/admin/directories/military-positions/entries/<?= $entryStatus === 'active' ? 'archive' : 'restore' ?>.php" class="military-position-state-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="version_id" value="<?= (int) $version['id'] ?>"><input type="hidden" name="entry_id" value="<?= (int) $entry['id'] ?>"><input type="hidden" name="expected_catalog_revision" value="<?= (int) $version['revision'] ?>"><input type="hidden" name="expected_entry_revision" value="<?= (int) $entry['revision'] ?>"><input type="hidden" name="return_to" value="/admin/directories/military-positions/version.php?id=<?= (int) $version['id'] ?>">
            <label><?= $entryStatus === 'active' ? 'Основание архивирования' : 'Основание восстановления' ?><input name="change_reason" maxlength="1000" required></label><button class="secondary-button" type="submit"><?= $entryStatus === 'active' ? 'Архивировать' : 'Восстановить' ?></button>
        </form>
    </div>
    <?php endif; ?>
</article>
