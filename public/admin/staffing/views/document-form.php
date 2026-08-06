<?php

declare(strict_types=1);

$isDocumentEdit = is_array($documentForm);
$documentAction = $isDocumentEdit ? '/admin/staffing/documents/update.php' : '/admin/staffing/documents/create.php';
$documentDateInputId = 'staffing-document-date-' . ($isDocumentEdit ? (string) (int) $documentForm['id'] : 'new');
?>
<form method="post" action="<?= e($documentAction) ?>" class="organization-form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><?php if ($isDocumentEdit): ?><input type="hidden" name="document_id" value="<?= (int) $documentForm['id'] ?>"><?php endif; ?>
    <label>Тип<select name="document_type" required><?php foreach (['staffing_order'=>'Штатный приказ','amendment_order'=>'Приказ об изменении','approval_act'=>'Акт утверждения','other_basis'=>'Иное основание'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($documentForm['document_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
    <label>Дата
        <span class="staffing-date-control">
            <input id="<?= e($documentDateInputId) ?>" type="date" name="document_date" value="<?= e((string) ($documentForm['document_date'] ?? '')) ?>" required>
            <button class="secondary-button staffing-date-picker" type="button" title="Выбрать дату" aria-label="Открыть календарь для даты документа" data-date-picker-target="<?= e($documentDateInputId) ?>">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path></svg>
            </button>
        </span>
    </label>
    <label>Номер<input name="document_number" maxlength="128" value="<?= e((string) ($documentForm['document_number'] ?? '')) ?>" required></label><label>Роль<select name="document_role"><?php foreach (['primary_basis'=>'Основное основание','additional_basis'=>'Дополнительное','amendment'=>'Изменение'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($documentForm['document_role'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
    <label class="span-2">Наименование<input name="title" maxlength="255" value="<?= e((string) ($documentForm['title'] ?? '')) ?>" required></label><label>Порядок<input type="number" min="1" name="sort_order" value="<?= (int) ($documentForm['sort_order'] ?? (count($documents)+1)) ?>" required></label><label class="span-2">Примечание<textarea name="note" maxlength="5000"><?= e((string) ($documentForm['note'] ?? '')) ?></textarea></label><div class="span-2 organization-actions"><button class="primary-button" type="submit"><?= $isDocumentEdit ? 'Сохранить документ' : 'Добавить документ' ?></button></div>
</form>
