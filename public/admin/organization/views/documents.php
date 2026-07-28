    <section id="documents" class="organization-panel glass-tile">
        <div class="organization-section-heading"><div><h2>Документы-основания</h2><p>В v1 хранятся реквизиты без загрузки файлов.</p></div></div>
        <?php if ($documents === []): ?>
            <p class="organization-empty">Документы не связаны с выбранной версией.</p>
        <?php else: ?>
            <div class="organization-document-list">
            <?php foreach ($documents as $document): ?>
                <article>
                    <div><span class="status-badge"><?= e($roleLabel((string) $document['document_role'])) ?></span><h3><?= e((string) $document['title']) ?></h3><p><?= e((string) $document['document_type']) ?> от <?= e((string) $document['document_date']) ?> № <?= e((string) $document['document_number']) ?></p><?php if ($document['note']): ?><small><?= e((string) $document['note']) ?></small><?php endif; ?></div>
                    <?php if ($canUpdate && $isDraft): ?>
                    <div class="organization-document-actions">
                        <details>
                            <summary class="organization-disclosure organization-disclosure--edit"><span class="organization-disclosure-icon" aria-hidden="true"></span><span>Изменить</span></summary>
                            <form method="post" action="/admin/organization/documents/update.php" class="organization-compact-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="structure_id" value="<?= $structureId ?>">
                                <input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>">
                                <input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>">
                                <input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>">
                                <label>Роль<select name="document_role"><option value="primary_basis" <?= $document['document_role'] === 'primary_basis' ? 'selected' : '' ?>>Основной документ</option><option value="additional_basis" <?= $document['document_role'] === 'additional_basis' ? 'selected' : '' ?>>Дополнительное основание</option><option value="amendment" <?= $document['document_role'] === 'amendment' ? 'selected' : '' ?>>Изменение</option></select></label>
                                <label>Вид документа<input name="document_type" maxlength="128" required value="<?= e((string) $document['document_type']) ?>"></label>
                                <label class="organization-date-label"><span>Дата</span><span class="organization-date-control"><input type="date" name="document_date" required value="<?= e((string) $document['document_date']) ?>"><span class="organization-date-icon" aria-hidden="true"></span></span></label>
                                <label>Номер<input name="document_number" maxlength="128" required value="<?= e((string) $document['document_number']) ?>"></label>
                                <label>Наименование<input name="title" maxlength="255" required value="<?= e((string) $document['title']) ?>"></label>
                                <label>Примечание<textarea name="note" maxlength="4000"><?= e((string) ($document['note'] ?? '')) ?></textarea></label>
                                <button class="primary-button" type="submit">Сохранить документ</button>
                            </form>
                        </details>
                        <form method="post" action="/admin/organization/documents/unlink.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><button class="danger-button" type="submit">Отвязать</button></form>
                    </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($canUpdate && $isDraft): ?>
        <details>
            <summary class="organization-disclosure organization-disclosure--add"><span class="organization-disclosure-icon" aria-hidden="true"></span><span>Добавить документ-основание</span></summary>
            <form method="post" action="/admin/organization/documents/create.php" class="organization-form-grid">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="structure_id" value="<?= $structureId ?>">
                <input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>">
                <input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>">
                <label>Роль<select name="document_role"><option value="primary_basis">Основной документ</option><option value="additional_basis">Дополнительное основание</option><option value="amendment">Изменение</option></select></label>
                <label>Вид документа<input name="document_type" maxlength="128" required></label>
                <label class="organization-date-label"><span>Дата</span><span class="organization-date-control"><input type="date" name="document_date" required><span class="organization-date-icon" aria-hidden="true"></span></span></label>
                <label>Номер<input name="document_number" maxlength="128" required placeholder="Без номера"></label>
                <label class="span-2">Наименование<input name="title" maxlength="255" required></label>
                <label class="span-2">Примечание<textarea name="note" maxlength="4000"></textarea></label>
                <div class="span-2"><button class="primary-button" type="submit">Добавить документ</button></div>
            </form>
        </details>
        <?php endif; ?>
    </section>