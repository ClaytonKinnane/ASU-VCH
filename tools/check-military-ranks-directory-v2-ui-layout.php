<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

function military_rank_ui_layout_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo 'OK ' . $message . PHP_EOL;
}

try {
    $pagePath = $root . '/public/admin/directories/military-ranks.php';
    $page = file_get_contents($pagePath);
    military_rank_ui_layout_check(is_string($page), 'military ranks page readable');

    $hierarchyClassNeedle = <<<'PHP'
directory-composition-card<?= $composition['parent_id'] !== null ? ' is-child' : '' ?>
PHP;
    $hierarchyPathNeedle = <<<'PHP'
<strong><?= e($composition['path']) ?></strong>
PHP;

    military_rank_ui_layout_check(
        str_contains($page, $hierarchyClassNeedle),
        'composition hierarchy class is data-driven'
    );
    military_rank_ui_layout_check(
        str_contains($page, $hierarchyPathNeedle),
        'composition hierarchy path remains explicit'
    );

    foreach (['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'] as $theme) {
        $relativePath = "themes/{$theme}/assets/css/military-ranks-v2.css";
        $css = file_get_contents($root . '/' . $relativePath);
        military_rank_ui_layout_check(is_string($css), "{$theme} UI stylesheet readable");
        military_rank_ui_layout_check(
            str_contains($css, '.directory-composition-grid { display: grid; grid-template-columns: minmax(0,1fr); gap: 10px; align-items: start; }'),
            "{$theme} composition list uses one-column start-aligned hierarchy"
        );
        military_rank_ui_layout_check(
            !str_contains($css, '.directory-composition-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr));'),
            "{$theme} legacy two-column stretching removed"
        );
        military_rank_ui_layout_check(
            str_contains($css, '.directory-composition-card { position: relative; min-width: 0; align-self: start;'),
            "{$theme} composition cards do not stretch"
        );
        military_rank_ui_layout_check(
            str_contains($css, '.directory-composition-card:not(.is-child)'),
            "{$theme} parent composition styling exists"
        );
        military_rank_ui_layout_check(
            str_contains($css, '.directory-composition-card.is-child { margin-left: 30px;'),
            "{$theme} child composition indentation exists"
        );
        military_rank_ui_layout_check(
            str_contains($css, '.directory-composition-card.is-child::before'),
            "{$theme} child hierarchy connector exists"
        );
        military_rank_ui_layout_check(
            str_contains($css, '.directory-composition-card.is-child::before { display: none; }'),
            "{$theme} narrow-layout connector fallback exists"
        );
    }

    echo "MILITARY_RANKS_DIRECTORY_V2_UI_LAYOUT_CHECK=PASS\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'MILITARY_RANKS_DIRECTORY_V2_UI_LAYOUT_CHECK=FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
