<?php
/** @var array<string,mixed> $version */
$versionStatus = (string) $version['status'];
$versionKind = (string) $version['catalog_kind'];
?>
<article class="military-position-version-card glass-tile status-<?= e($versionStatus) ?>">
    <header><div><span class="status-badge"><?= e(military_positions_status_label($versionStatus)) ?></span><span class="military-position-kind-badge"><?= $versionKind === 'canonical' ? 'Каноническая' : 'Историческая' ?></span><h2>№ <?= (int) $version['version_number'] ?> · <?= e((string) $version['version_label']) ?></h2><p><?= e((string) $version['coverage_note']) ?></p></div><a class="primary-button" href="/admin/directories/military-positions/version.php?id=<?= (int) $version['id'] ?>">Открыть</a></header>
    <dl class="military-position-version-metrics"><div><dt>Начало действия</dt><dd><?= e(military_positions_date((string) $version['valid_from'])) ?></dd></div><div><dt>Окончание</dt><dd><?= e(military_positions_date($version['valid_to'] !== null ? (string) $version['valid_to'] : null)) ?></dd></div><div><dt>Записей</dt><dd><?= (int) $version['entry_count'] ?> / действующих <?= (int) $version['active_entry_count'] ?></dd></div><div><dt>Редакция</dt><dd><?= (int) $version['revision'] ?></dd></div></dl>
    <p class="military-position-version-reason"><strong>Основание:</strong> <?= e((string) $version['change_reason']) ?></p>
</article>
