<?php
/** @var array<string,mixed> $version */
/** @var string|null $versionCardMode */
/** @var bool|null $canViewHistory */
$versionStatus = (string) $version['status'];
$versionKind = (string) $version['catalog_kind'];
$isDetailMode = ($versionCardMode ?? 'list') === 'detail';
$showHistoryAction = $isDetailMode && isset($canViewHistory) && $canViewHistory;
$versionAnchor = 'military-position-version-' . (int) $version['id'];
?>
<article id="<?= e($versionAnchor) ?>" class="military-position-version-card glass-tile status-<?= e($versionStatus) ?>">
    <header>
        <div><span class="status-badge"><?= e(military_positions_status_label($versionStatus)) ?></span><span class="military-position-kind-badge"><?= $versionKind === 'canonical' ? 'Каноническая' : 'Историческая' ?></span><h2>№ <?= (int) $version['version_number'] ?> · <?= e((string) $version['version_label']) ?></h2><p><?= e((string) $version['coverage_note']) ?></p></div>
        <div class="military-position-version-actions">
            <?php if ($isDetailMode): ?>
                <?php if ($showHistoryAction): ?><a class="secondary-button" href="/admin/directories/military-positions/history.php?version_id=<?= (int) $version['id'] ?>">История этой версии</a><?php endif; ?>
                <a class="secondary-button" href="/admin/directories/military-positions.php#<?= e($versionAnchor) ?>">Закрыть</a>
            <?php else: ?>
                <a class="primary-button" href="/admin/directories/military-positions/version.php?id=<?= (int) $version['id'] ?>">Открыть</a>
            <?php endif; ?>
        </div>
    </header>
    <dl class="military-position-version-metrics"><div><dt>Начало действия</dt><dd><?= e(military_positions_date((string) $version['valid_from'])) ?></dd></div><div><dt>Окончание</dt><dd><?= e(military_positions_date($version['valid_to'] !== null ? (string) $version['valid_to'] : null)) ?></dd></div><div><dt>Записей</dt><dd><?= (int) $version['entry_count'] ?> / действующих <?= (int) $version['active_entry_count'] ?></dd></div><div><dt>Редакция</dt><dd><?= (int) $version['revision'] ?></dd></div></dl>
    <p class="military-position-version-reason"><strong>Основание:</strong> <?= e((string) $version['change_reason']) ?></p>
</article>
