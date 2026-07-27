<?php

declare(strict_types=1);
require dirname(__DIR__, 3) . '/app/bootstrap.php';
$user = require_permission('system.*.*');

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query, 'UTF-8') > 150) {
    $query = mb_substr($query, 0, 150, 'UTF-8');
}

$repository = military_rank_catalog_repository();
$version = $repository->currentVersion();
$sources = $repository->sources();
$compositions = $repository->compositions();

$compositionCode = trim((string) ($_GET['composition'] ?? ''));
$allowedCompositionCodes = [];
foreach ($compositions as $composition) {
    $allowedCompositionCodes[(string) $composition['code']] = true;
}
if ($compositionCode !== '' && !isset($allowedCompositionCodes[$compositionCode])) {
    $compositionCode = '';
}

$result = $repository->search($query, $compositionCode);

function military_rank_directory_date(string $value): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y') : $value;
}

function military_rank_source_role(string $role): string
{
    return match ($role) {
        'primary-list' => 'Основной перечень',
        'equivalence-and-order' => 'Соответствие и старшинство',
        default => 'Нормативный источник',
    };
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Составы военнослужащих и воинские звания — АСУ-ВЧ</title>
    <link rel="stylesheet" href="<?= e(theme_asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(theme_asset('css/directories.css')) ?>">
</head>
<body>
<header class="site-header"><div class="container"><div class="header-content glass-tile"><div class="site-logo">АСУ</div><div class="site-heading"><h1 class="site-title">Составы военнослужащих и воинские звания</h1><p class="site-description">Нормативный системный справочник Российской Федерации</p></div><a class="secondary-button" href="/admin/directories.php">К справочникам</a></div></div></header>
<main class="admin-main"><div class="container">
<section class="directory-hero glass-tile" aria-labelledby="directory-version-title">
    <div class="directory-hero-heading"><div><h2 id="directory-version-title"><?= e($version['name']) ?></h2><p>Данные сформированы по нормативным правовым актам и доступны только для просмотра. Порядок строк соответствует нормативному старшинству воинских званий.</p></div><span class="directory-readonly-badge">Только чтение</span></div>
    <dl class="directory-meta">
        <div><dt>Версия каталога</dt><dd><?= e($version['code']) ?></dd></div>
        <div><dt>Действует в АСУ-ВЧ с</dt><dd><?= e(military_rank_directory_date($version['valid_from'])) ?></dd></div>
        <div><dt>Актуальность проверена</dt><dd><?= e(military_rank_directory_date($version['verified_at'])) ?></dd></div>
    </dl>
</section>
<section class="legal-sources-grid" aria-label="Нормативные источники">
<?php foreach ($sources as $source): ?>
<article class="legal-source-card glass-tile">
    <span class="legal-source-kicker"><?= e(military_rank_source_role($source['source_role'])) ?></span>
    <h2><?= e($source['document_type']) ?> от <?= e(military_rank_directory_date($source['document_date'])) ?> № <?= e($source['document_number']) ?></h2>
    <p><?= e($source['title']) ?></p>
    <dl>
        <div><dt>Положение</dt><dd><?= e($source['provision']) ?></dd></div>
        <div><dt>Проверено</dt><dd><?= e(military_rank_directory_date($source['verified_at'])) ?></dd></div>
    </dl>
    <a class="legal-source-link" href="<?= e($source['official_url']) ?>" target="_blank" rel="noopener noreferrer">Открыть официальный источник →</a>
</article>
<?php endforeach; ?>
</section>
<section class="directory-panel glass-tile" aria-labelledby="military-ranks-list-title">
    <div class="directory-panel-heading"><div><h2 id="military-ranks-list-title">Нормативный перечень</h2><p>Поиск выполняется по войсковым и корабельным наименованиям.</p></div><span class="directory-result-count">Найдено: <?= (int) $result['total'] ?></span></div>
    <form class="directory-filters" method="get" action="/admin/directories/military-ranks.php">
        <label><span>Поиск</span><input class="form-input" type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Например: сержант или адмирал"></label>
        <label><span>Состав военнослужащих</span><select class="form-input" name="composition"><option value="">Все составы</option><?php foreach ($compositions as $composition): ?><?php $optionLabel = $composition['parent_name'] !== null ? '— ' . $composition['name'] : $composition['name'] . ($composition['code'] === 'officers' ? ' (все)' : ''); ?><option value="<?= e($composition['code']) ?>"<?= $compositionCode === $composition['code'] ? ' selected' : '' ?>><?= e($optionLabel) ?></option><?php endforeach; ?></select></label>
        <button class="primary-button directory-filter-submit" type="submit">Показать</button>
        <?php if ($query !== '' || $compositionCode !== ''): ?><a class="secondary-button" href="/admin/directories/military-ranks.php">Сбросить</a><?php endif; ?>
    </form>
    <?php if ($result['items'] === []): ?>
    <div class="directory-empty"><strong>По заданным условиям воинские звания не найдены.</strong></div>
    <?php else: ?>
    <div class="directory-table-wrap"><table class="directory-table"><thead><tr><th>№</th><th>Состав военнослужащих</th><th>Войсковое звание</th><th>Корабельное звание</th></tr></thead><tbody>
    <?php $previousCompositionPath = null; ?>
    <?php foreach ($result['items'] as $rank): ?>
        <?php $isGroupStart = $previousCompositionPath !== $rank['composition_path']; $previousCompositionPath = $rank['composition_path']; ?>
        <tr<?= $isGroupStart ? ' class="rank-group-start"' : '' ?>>
            <td data-label="№"><span class="directory-rank-number"><?= (int) $rank['sort_order'] ?></span></td>
            <td data-label="Состав"><span class="directory-composition-path"><?= e($rank['composition_path']) ?></span></td>
            <td data-label="Войсковое звание"><strong><?= e($rank['troop_name']) ?></strong></td>
            <td data-label="Корабельное звание"><?php if ($rank['naval_name'] !== null): ?><strong><?= e($rank['naval_name']) ?></strong><?php else: ?><span class="directory-muted-value">—</span><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</section>
</div></main>
</body>
</html>
