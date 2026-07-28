    <section class="organization-summary glass-tile">
        <div><span class="status-badge <?= $structure['status'] === 'archived' ? 'is-muted' : '' ?>"><?= $structure['status'] === 'archived' ? 'Архивная структура' : 'Действующая структура' ?></span><strong><?= e((string) $structure['code']) ?></strong></div>
        <div class="organization-actions">
            <?php if ($canUpdate && $structure['status'] === 'active' && $repository->pendingVersion($structureId) === null && $versions !== []): ?>
            <form method="post" action="/admin/organization/versions/create-draft.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><label>Основание новой версии<input name="change_reason" maxlength="1000" required></label><button class="primary-button" type="submit">Создать черновик</button></form>
            <?php endif; ?>
            <?php if ($canUpdate && $structure['status'] === 'active'): ?>
            <details><summary>Изменить карточку</summary><form method="post" action="/admin/organization/structures/update.php" class="organization-compact-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><label>Название структуры<input name="display_name" maxlength="255" required value="<?= e((string) $structure['display_name']) ?>"></label><label>Краткое название<input name="short_name" maxlength="128" value="<?= e((string) ($structure['short_name'] ?? '')) ?>"></label><button class="primary-button" type="submit">Сохранить карточку</button></form></details>
            <?php endif; ?>
            <?php if ($canArchive): ?>
                <?php if ($structure['status'] === 'active'): ?><form method="post" action="/admin/organization/structures/archive.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><label>Основание архивирования<input name="reason" maxlength="1000" required></label><button class="danger-button" type="submit">Архивировать</button></form>
                <?php else: ?><form method="post" action="/admin/organization/structures/restore.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><label>Основание восстановления<input name="reason" maxlength="1000" required></label><button class="primary-button" type="submit">Восстановить</button></form><?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <nav class="organization-tabs glass-tile" aria-label="Разделы карточки">
        <a href="#tree">Дерево</a><a href="#versions">Версии</a><a href="#documents">Документы</a><?php if ($diff !== []): ?><a href="#compare">Сравнение</a><?php endif; ?><?php if ($canHistory): ?><a href="#history">История</a><?php endif; ?>
    </nav>

