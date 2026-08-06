<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/Staffing/functions.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

require_permission('staffing.registers.history');
$registerId = staffing_get_positive_int('register_id');
try {
    $register = staffing_repository()->register($registerId);
    $events = staffing_repository()->history($registerId);
} catch (OutOfBoundsException) {
    http_response_code(404);
    exit('Штатный реестр не найден.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История — <?= e((string) $register['name']) ?></title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/organization.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile">
    <div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">История штатного реестра</h1><p class="site-description"><?= e((string) $register['name']) ?></p></div>
    <a class="secondary-button" href="/admin/staffing/register.php?id=<?= $registerId ?>">К реестру</a>
</div></div></header>
<main class="admin-main"><div class="container organization-layout">
    <section class="organization-list" aria-label="Предметная история">
    <?php if ($events === []): ?>
        <article class="organization-empty glass-tile"><h2>События отсутствуют</h2></article>
    <?php else: foreach ($events as $event): $before = staffing_format_history_state($event['before_state']); $after = staffing_format_history_state($event['after_state']); ?>
        <article class="organization-card glass-tile"><div><span class="status-badge"><?= e((string) $event['event_type']) ?></span><h2><?= e((string) $event['target_type']) ?><?= $event['target_id'] !== null ? ' #' . (int) $event['target_id'] : '' ?></h2><p><?= e((string) $event['created_at']) ?> · <?= e((string) ($event['actor_name'] ?? 'Системный субъект')) ?><?= $event['version_number'] !== null ? ' · версия № ' . (int) $event['version_number'] : '' ?></p><?php if ($event['reason'] !== null): ?><p><?= nl2br(e((string) $event['reason'])) ?></p><?php endif; ?></div>
            <?php if ($before !== null || $after !== null): ?><details><summary>Состояние</summary><div class="organization-form-grid"><div><h3>До</h3><pre><?= e((string) ($before ?? '—')) ?></pre></div><div><h3>После</h3><pre><?= e((string) ($after ?? '—')) ?></pre></div></div></details><?php endif; ?>
        </article>
    <?php endforeach; endif; ?>
    </section>
    <p class="organization-footnote">История является append-only предметным журналом и не заменяет будущий общий Security Audit.</p>
</div></main>
</body>
</html>
