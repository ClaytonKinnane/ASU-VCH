<?php declare(strict_types=1); $isDraft = $selectedVersion['status'] === 'draft'; ?>
<section class="organization-panel glass-tile">
    <div class="organization-section-heading"><div><span class="status-badge"><?= e((string) $selectedVersion['status']) ?></span><h2>Версия № <?= (int) $selectedVersion['version_number'] ?> · <?= e((string) $selectedVersion['version_label']) ?></h2></div><span>Ревизия <?= (int) $selectedVersion['revision'] ?></span></div>
    <dl class="organization-metrics">
        <div><dt>Оргструктура</dt><dd>версия № <?= (int) $selectedVersion['organization_version_number'] ?> · <?= e((string) $selectedVersion['organization_version_status']) ?></dd></div>
        <div><dt>Должности</dt><dd><?= e((string) $selectedVersion['position_catalog_name']) ?></dd></div>
        <div><dt>Звания</dt><dd><?= e((string) $selectedVersion['rank_catalog_name']) ?></dd></div>
        <div><dt>Публичные ВУС</dt><dd><?= e((string) $selectedVersion['vus_catalog_name']) ?></dd></div>
        <div><dt>Период</dt><dd><?= e((string) $selectedVersion['effective_from']) ?> — <?= e((string) ($selectedVersion['effective_to'] ?? '…')) ?></dd></div>
        <div><dt>Назначения</dt><dd>Не ведутся в v1</dd></div>
    </dl>
    <p><?= nl2br(e((string) $selectedVersion['change_reason'])) ?></p>

    <?php if ($isDraft && $canPublish): ?>
    <div class="organization-actions">
        <form method="post" action="/admin/staffing/versions/approve.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><button class="primary-button" type="submit">Утвердить</button></form>
        <form method="post" action="/admin/staffing/versions/cancel.php" class="organization-inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><input name="reason" maxlength="1000" required placeholder="Основание отмены"><button class="danger-button" type="submit">Отменить</button></form>
    </div>
    <?php elseif ($selectedVersion['status'] === 'approved' && $canPublish): ?>
    <div class="organization-actions">
        <form method="post" action="/admin/staffing/versions/activate.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><button class="primary-button" type="submit">Активировать</button></form>
        <form method="post" action="/admin/staffing/versions/cancel.php" class="organization-inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><input name="reason" maxlength="1000" required placeholder="Основание отмены"><button class="danger-button" type="submit">Отменить</button></form>
    </div>
    <?php endif; ?>
</section>

<section class="organization-panel glass-tile">
    <div class="organization-section-heading"><h2>Документы-основания</h2><span><?= count($documents) ?></span></div>
    <?php if ($documents === []): ?><p>Документы не связаны.</p><?php else: ?><div class="organization-list"><?php foreach ($documents as $document): ?>
        <article class="organization-card"><div><span class="status-badge"><?= e((string) $document['document_role']) ?></span><h3><?= e((string) $document['title']) ?></h3><p><?= e((string) $document['document_type']) ?> · № <?= e((string) $document['document_number']) ?> от <?= e((string) $document['document_date']) ?></p></div>
        <?php if ($isDraft && $canUpdate): ?><details><summary>Изменить</summary><?php $documentForm = $document; require __DIR__ . '/document-form.php'; ?><form method="post" action="/admin/staffing/documents/unlink.php" class="organization-inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><input name="reason" maxlength="1000" required placeholder="Основание исключения"><button class="danger-button" type="submit">Исключить</button></form></details><?php endif; ?>
        </article>
    <?php endforeach; ?></div><?php endif; ?>
    <?php if ($isDraft && $canUpdate): ?><details><summary>Добавить документ</summary><?php $documentForm = null; require __DIR__ . '/document-form.php'; ?></details><?php endif; ?>
</section>

<section class="organization-panel glass-tile">
    <div class="organization-section-heading"><h2>Индивидуальные штатные позиции</h2><span><?= count($slots) ?></span></div>
    <?php if ($slots === []): ?><p>Позиции отсутствуют.</p><?php else: ?><div class="organization-list"><?php foreach ($slots as $slot): ?>
        <article class="organization-card"><div><span class="status-badge <?= $slot['normative_state'] !== 'active' ? 'is-muted' : '' ?>"><?= e((string) $slot['normative_state']) ?></span><h3><?= e((string) $slot['display_name']) ?></h3><p><?= e((string) $slot['organizational_element_name']) ?> · <?= e((string) $slot['position_type_name']) ?><?= $slot['position_variant_name'] !== null ? ' · ' . e((string) $slot['position_variant_name']) : '' ?></p><p>Звания: <?= e((string) ($slot['minimum_rank_name'] ?? '—')) ?> — <?= e((string) ($slot['maximum_rank_name'] ?? '—')) ?>; предпочтительно: <?= e((string) ($slot['preferred_rank_name'] ?? '—')) ?></p><p>ВУС: <?= e((string) ($slot['vus_summary'] ?? 'не заданы')) ?> · assignment_state=not-managed-in-v1</p></div>
        <?php if ($isDraft && $canUpdate): ?><details><summary>Изменить позицию</summary><?php $slotForm = $slot; require __DIR__ . '/slot-form.php'; ?><form method="post" action="/admin/staffing/slots/remove.php" class="organization-inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="register_id" value="<?= $registerId ?>"><input type="hidden" name="version_id" value="<?= (int) $selectedVersion['id'] ?>"><input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>"><input type="hidden" name="expected_revision" value="<?= (int) $selectedVersion['revision'] ?>"><input name="reason" maxlength="1000" required placeholder="Основание удаления"><button class="danger-button" type="submit">Удалить из черновика</button></form></details><?php endif; ?>
        </article>
    <?php endforeach; ?></div><?php endif; ?>
    <?php if ($isDraft && $canUpdate): ?><details><summary>Добавить штатную позицию</summary><?php $slotForm = null; require __DIR__ . '/slot-form.php'; ?></details><?php endif; ?>
</section>
