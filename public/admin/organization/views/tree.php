    <section id="tree" class="organization-panel glass-tile">
        <div class="organization-section-heading"><div><h2>Дерево организационной структуры</h2><p>Родительская связь отражает штатное включение и основную постоянную подчинённость.</p></div><div class="organization-tree-tools"><div class="organization-tree-tool-buttons"><button type="button" class="secondary-button" data-tree-expand>Раскрыть всё</button><button type="button" class="secondary-button" data-tree-collapse>Свернуть всё</button></div><label class="organization-tree-search">Поиск<input type="search" maxlength="150" data-tree-search placeholder="Наименование, код или тип"></label></div></div>
        <?php if ($selectedVersion === null): ?><div class="organization-empty"><p>Версии структуры отсутствуют.</p></div>
        <?php elseif ($flatTree === []): ?><div class="organization-empty"><p>Версия не содержит элементов.</p></div>
        <?php else: ?>
        <div class="organization-tree" data-organization-tree>
            <?php foreach ($flatTree as $entry): $node = $entry['node']; $depth = (int) $entry['depth']; $nodeTypeOptions = $node['parent_node_id'] === null ? $rootTypes : $types; $nodeId = (int) $node['id']; $movePanelId = 'organization-node-move-' . $nodeId; $editPanelId = 'organization-node-edit-' . $nodeId; $addPanelId = 'organization-node-add-' . $nodeId; $deletePanelId = 'organization-node-delete-' . $nodeId; ?>
            <article class="organization-tree-row" style="--tree-depth: <?= $depth ?>" data-tree-node data-parent-id="<?= $node['parent_node_id'] !== null ? (int) $node['parent_node_id'] : 0 ?>" data-node-id="<?= $nodeId ?>" data-search-text="<?= e(mb_strtolower((string) $node['name'] . ' ' . (string) ($node['internal_code'] ?? '') . ' ' . (string) $node['type_name'])) ?>">
                <div class="organization-node-main"><button type="button" class="tree-toggle" data-tree-toggle aria-label="Свернуть или раскрыть ветвь" <?= (int) $node['child_count'] === 0 ? 'disabled' : '' ?>>▾</button><div><span class="node-level">Уровень <?= $depth + 1 ?></span><h3><?= e((string) $node['name']) ?></h3><p><?= e((string) ($node['short_name'] ?: '')) ?><?php if ($node['internal_code'] !== null): ?> · код <?= e((string) $node['internal_code']) ?><?php endif; ?></p><small><?= e((string) $node['type_name']) ?> · дочерних: <?= (int) $node['child_count'] ?></small></div></div>
                <?php if ($canUpdate && $isDraft): ?>
                <div class="organization-node-actions" data-node-actions>
                    <div class="organization-node-action-bar">
                        <form method="post" action="/admin/organization/nodes/reorder.php" class="organization-node-reorder-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="structure_id" value="<?= $structureId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="node_id" value="<?= $nodeId ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><button class="secondary-button organization-direction-button" name="direction" value="up" type="submit" <?= $node['parent_node_id'] === null ? 'disabled' : '' ?>><span class="organization-direction-icon organization-direction-icon--up" aria-hidden="true"></span><span>Выше</span></button><button class="secondary-button organization-direction-button" name="direction" value="down" type="submit" <?= $node['parent_node_id'] === null ? 'disabled' : '' ?>><span class="organization-direction-icon organization-direction-icon--down" aria-hidden="true"></span><span>Ниже</span></button></form>
                        <?php if ($node['parent_node_id'] !== null): ?>
                        <button type="button" class="organization-disclosure organization-node-action-trigger" data-node-action-target="<?= e($movePanelId) ?>" aria-controls="<?= e($movePanelId) ?>" aria-expanded="false"><span class="organization-disclosure-icon" aria-hidden="true"></span><span>Переместить</span></button>
                        <?php endif; ?>
                        <button type="button" class="organization-disclosure organization-disclosure--edit organization-node-action-trigger" data-node-action-target="<?= e($editPanelId) ?>" aria-controls="<?= e($editPanelId) ?>" aria-expanded="false"><span class="organization-disclosure-icon" aria-hidden="true"></span><span>Изменить</span></button>
                        <button type="button" class="organization-disclosure organization-disclosure--add organization-node-action-trigger" data-node-action-target="<?= e($addPanelId) ?>" aria-controls="<?= e($addPanelId) ?>" aria-expanded="false"><span class="organization-disclosure-icon" aria-hidden="true"></span><span>Добавить дочерний</span></button>
                        <?php if ($node['parent_node_id'] !== null): ?>
                        <button type="button" class="organization-disclosure organization-disclosure--danger organization-node-action-trigger" data-node-action-target="<?= e($deletePanelId) ?>" aria-controls="<?= e($deletePanelId) ?>" aria-expanded="false"><span class="organization-disclosure-icon" aria-hidden="true"></span><span>Удалить</span></button>
                        <?php endif; ?>
                    </div>
                    <div class="organization-node-action-panels">
                        <?php if ($node['parent_node_id'] !== null): ?>
                        <section id="<?= e($movePanelId) ?>" class="organization-node-action-panel" data-node-action-panel hidden>
                            <form method="post" action="/admin/organization/nodes/move.php" class="organization-node-inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="structure_id" value="<?= $structureId ?>">
                                <input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>">
                                <input type="hidden" name="node_id" value="<?= $nodeId ?>">
                                <input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>">
                                <label>Новый родитель<select name="parent_node_id" required><?php foreach ($flatTree as $parentEntry): $parent = $parentEntry['node']; if ((int) $parent['id'] === $nodeId) continue; ?><option value="<?= (int) $parent['id'] ?>" <?= (int) ($node['parent_node_id'] ?? 0) === (int) $parent['id'] ? 'selected' : '' ?>><?= e(str_repeat('— ', (int) $parentEntry['depth']) . (string) $parent['name']) ?></option><?php endforeach; ?></select></label>
                                <button class="primary-button" type="submit">Переместить</button>
                            </form>
                        </section>
                        <?php endif; ?>
                        <section id="<?= e($editPanelId) ?>" class="organization-node-action-panel" data-node-action-panel hidden>
                            <form method="post" action="/admin/organization/nodes/update.php" class="organization-compact-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="structure_id" value="<?= $structureId ?>">
                                <input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>">
                                <input type="hidden" name="node_id" value="<?= $nodeId ?>">
                                <input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>">
                                <label>Тип<select name="type_id" required><?php foreach ($nodeTypeOptions as $type): ?><option value="<?= (int) $type['id'] ?>" <?= (int) $node['organizational_element_type_id'] === (int) $type['id'] ? 'selected' : '' ?>><?= e((string) $type['name']) ?></option><?php endforeach; ?></select></label>
                                <label>Полное наименование<input name="name" maxlength="255" required value="<?= e((string) $node['name']) ?>"></label>
                                <label>Краткое наименование<input name="short_name" maxlength="128" value="<?= e((string) ($node['short_name'] ?? '')) ?>"></label>
                                <label>Внутренний код<input name="internal_code" maxlength="64" value="<?= e((string) ($node['internal_code'] ?? '')) ?>"></label>
                                <label>Примечание<textarea name="note" maxlength="4000"><?= e((string) ($node['note'] ?? '')) ?></textarea></label>
                                <button class="primary-button" type="submit">Сохранить</button>
                            </form>
                        </section>
                        <section id="<?= e($addPanelId) ?>" class="organization-node-action-panel" data-node-action-panel hidden>
                            <form method="post" action="/admin/organization/nodes/create.php" class="organization-compact-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="structure_id" value="<?= $structureId ?>">
                                <input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>">
                                <input type="hidden" name="parent_node_id" value="<?= $nodeId ?>">
                                <input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>">
                                <label>Тип<select name="type_id" required><?php foreach ($types as $type): ?><option value="<?= (int) $type['id'] ?>"><?= e((string) $type['name']) ?><?= $type['class_names'] ? ' · ' . e((string) $type['class_names']) : '' ?></option><?php endforeach; ?></select></label>
                                <label>Полное наименование<input name="name" maxlength="255" required></label>
                                <label>Краткое наименование<input name="short_name" maxlength="128"></label>
                                <label>Внутренний код<input name="internal_code" maxlength="64"></label>
                                <label>Примечание<textarea name="note" maxlength="4000"></textarea></label>
                                <button class="primary-button" type="submit">Добавить</button>
                            </form>
                        </section>
                        <?php if ($node['parent_node_id'] !== null): ?>
                        <section id="<?= e($deletePanelId) ?>" class="organization-node-action-panel organization-node-action-panel--danger" data-node-action-panel hidden>
                            <p class="organization-node-action-note">Удаление исключит элемент из текущего черновика. Подтвердите действие.</p>
                            <form method="post" action="/admin/organization/nodes/delete.php" class="organization-compact-form organization-node-delete-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="structure_id" value="<?= $structureId ?>">
                                <input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>">
                                <input type="hidden" name="node_id" value="<?= $nodeId ?>">
                                <input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>">
                                <?php if ((int) $node['child_count'] > 0): ?><label class="checkbox-label"><input type="checkbox" name="confirm_subtree" value="1" required>Удалить всё поддерево (непосредственных дочерних: <?= (int) $node['child_count'] ?>)</label><label>Основание<textarea name="reason" maxlength="1000" required></textarea></label><?php endif; ?>
                                <button class="danger-button" type="submit">Подтвердить удаление</button>
                            </form>
                        </section>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>