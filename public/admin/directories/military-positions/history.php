<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/app/bootstrap.php';
header('Cache-Control: no-store, private');
$user = require_permission('directories.military_positions.history');
$rawVersionId = $_GET['version_id'] ?? null;
$versionId = is_string($rawVersionId) && preg_match('/\A[1-9][0-9]*\z/D', $rawVersionId) === 1 ? (int) $rawVersionId : null;
$version = $versionId !== null ? military_position_catalog_repository()->version($versionId) : null;
$events = military_position_catalog_repository()->history($versionId);
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>История воинских должностей — АСУ-ВЧ</title><link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>"><link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>"></head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">История справочника</h1><p class="site-description"><?= $version !== null ? 'Версия № ' . (int) $version['version_number'] . ' · ' . e((string) $version['version_label']) : 'Все версии воинских должностей' ?></p></div><a class="secondary-button" href="<?= $version !== null ? '/admin/directories/military-positions/version.php?id=' . (int) $version['id'] : '/admin/directories/military-positions.php' ?>">Назад</a></div></div></header>
<main class="admin-main"><div class="container military-position-layout"><section class="military-position-history-list">
<?php if ($events === []): ?><article class="military-position-empty glass-tile"><h2>Событий пока нет</h2></article><?php endif; ?>
<?php foreach ($events as $event): $before = military_positions_history_state($event['before_state']); $after = military_positions_history_state($event['after_state']); $fields = array_values(array_unique(array_merge(array_keys($before), array_keys($after)))); ?>
<article class="military-position-history-card glass-tile"><header><div><span class="tile-kicker"><?= e(military_positions_event_label((string) $event['event_type'])) ?></span><h2>Версия № <?= (int) $event['version_number'] ?> · <?= e((string) $event['version_label']) ?></h2></div><time><?= e(military_positions_datetime((string) $event['created_at'])) ?></time></header><p><strong>Исполнитель:</strong> <?= e((string) ($event['actor_name'] ?? 'Система')) ?></p><?php if ($event['reason'] !== null): ?><p><strong>Основание:</strong> <?= e((string) $event['reason']) ?></p><?php endif; ?>
<?php if ($fields !== []): ?><dl class="military-position-history-fields"><?php foreach ($fields as $field): $old = $before[$field] ?? null; $new = $after[$field] ?? null; if ($old === $new) continue; ?><div><dt><?= e(military_positions_field_label((string) $field)) ?></dt><dd><span><?= e(military_positions_history_value((string) $field, $old)) ?></span><strong>→</strong><span><?= e(military_positions_history_value((string) $field, $new)) ?></span></dd></div><?php endforeach; ?></dl><?php endif; ?></article>
<?php endforeach; ?>
</section></div></main></body></html>
