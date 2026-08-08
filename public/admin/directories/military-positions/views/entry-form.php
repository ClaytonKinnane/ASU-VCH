<?php
/** @var array<string,mixed> $version */
/** @var array<string,mixed> $entryForm */
/** @var string $formAction */
/** @var string $submitLabel */
$returnTo = '/admin/directories/military-positions/version.php?id=' . (int) $version['id'];
?>
<form method="post" action="<?= e($formAction) ?>" class="military-position-form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="version_id" value="<?= (int) $version['id'] ?>">
    <input type="hidden" name="expected_catalog_revision" value="<?= (int) $version['revision'] ?>">
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <?php if ($entryId !== null): ?><input type="hidden" name="entry_id" value="<?= (int) $entryId ?>"><input type="hidden" name="expected_entry_revision" value="<?= (int) $entryRevision ?>"><?php endif; ?>
    <label class="span-2">Каноническое наименование<input name="name" maxlength="255" required value="<?= e((string) ($entryForm['name'] ?? '')) ?>"></label>
    <label>Полное наименование<input name="full_name" maxlength="255" value="<?= e((string) ($entryForm['full_name'] ?? '')) ?>"></label>
    <label>Краткое наименование<input name="short_name" maxlength="128" value="<?= e((string) ($entryForm['short_name'] ?? '')) ?>"></label>
    <label>Источник<select name="source_type" required><option value="official"<?= ($entryForm['source_type'] ?? '') === 'official' ? ' selected' : '' ?>>Официальный</option><option value="local"<?= ($entryForm['source_type'] ?? '') === 'local' ? ' selected' : '' ?>>Локальный синтетический</option><option value="imported"<?= ($entryForm['source_type'] ?? '') === 'imported' ? ' selected' : '' ?>>Импортированный</option></select></label>
    <label>Порядок<input type="number" name="sort_order" min="1" required value="<?= (int) ($entryForm['sort_order'] ?? 1) ?>"></label>
    <label class="span-2">Реквизит или ссылка источника<textarea name="source_reference" maxlength="1000"><?= e((string) ($entryForm['source_reference'] ?? '')) ?></textarea></label>
    <label class="span-2">Примечание<textarea name="note" maxlength="5000"><?= e((string) ($entryForm['note'] ?? '')) ?></textarea></label>
    <label class="military-position-checkbox span-2"><input type="checkbox" name="is_combined" value="1"<?= (int) ($entryForm['is_combined'] ?? 0) === 1 ? ' checked' : '' ?>><span>Составная должность (явный признак)</span></label>
    <label class="span-2">Основание изменения<textarea name="change_reason" maxlength="1000" required></textarea></label>
    <div class="span-2"><button class="primary-button" type="submit"><?= e($submitLabel) ?></button></div>
</form>
