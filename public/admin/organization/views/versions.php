    <section id="versions" class="organization-panel glass-tile">
        <div class="organization-section-heading"><div><h2>Версии структуры</h2><p>Содержимое версий после выхода из черновика неизменяемо.</p></div></div>
        <div class="organization-version-list">
            <?php foreach ($versions as $version): ?>
                <a class="organization-version-card <?= $selectedVersion !== null && (int) $selectedVersion['id'] === (int) $version['id'] ? 'is-selected' : '' ?>" href="/admin/organization/structure.php?id=<?= $structureId ?>&version_id=<?= (int) $version['id'] ?>">
                    <strong>Версия № <?= (int) $version['version_number'] ?></strong><span><?= e($statusLabel((string) $version['status'])) ?></span><small><?= (int) $version['node_count'] ?> элементов · <?= e((string) ($version['effective_from'] ?? 'дата не задана')) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($selectedVersion !== null): ?>
        <div class="organization-version-controls">
            <div><strong>Выбрана версия № <?= (int) $selectedVersion['version_number'] ?></strong><span class="status-badge"><?= e($statusLabel((string) $selectedVersion['status'])) ?></span><small>Редакция формы: <?= (int) $selectedVersion['revision'] ?> · классификатор <?= e((string) $selectedVersion['catalog_code']) ?></small></div>
            <?php if ($canPublish && $isDraft): ?>
            <form method="post" action="/admin/organization/versions/approve.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>">
                <input type="hidden" name="structure_id" value="<?= $structureId ?>">
                <input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>">
                <div class="organization-date-field"><label for="organization-effective-from">Дата вступления в действие</label><span class="organization-date-control"><input id="organization-effective-from" type="date" name="effective_from" required><button type="button" class="organization-date-picker-button" data-date-picker-target="organization-effective-from" aria-label="Открыть календарь для даты вступления в действие" title="Открыть календарь"><span class="organization-date-icon" aria-hidden="true"></span></button></span></div>
                <button class="primary-button" type="submit">Утвердить версию</button>
            </form>
            <?php endif; ?>
            <?php if ($canPublish && $isApproved): ?>
            <form method="post" action="/admin/organization/versions/activate.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><button class="primary-button" type="submit">Ввести в действие</button></form>
            <?php endif; ?>
            <?php if ($canPublish && ($isDraft || $isApproved)): ?>
            <form method="post" action="/admin/organization/versions/cancel.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><label>Основание отмены<input name="reason" maxlength="1000" required></label><button class="danger-button" type="submit">Отменить версию</button></form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>