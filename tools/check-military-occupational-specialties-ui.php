<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

function mos_ui_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "OK {$message}\n";
}

try {
    $pagePath = $root . '/public/admin/directories/military-occupational-specialties.php';
    $page = file_get_contents($pagePath);
    mos_ui_check(is_string($page), 'VUS page readable');
    mos_ui_check(
        str_contains($page, "theme_asset('css/military-occupational-specialties.css')"),
        'VUS page-specific theme asset connected'
    );
    mos_ui_check(str_contains($page, 'function mos_source_role_name'), 'source roles localized');
    mos_ui_check(str_contains($page, 'function mos_evidence_name'), 'evidence labels localized');
    mos_ui_check(str_contains($page, 'function mos_status_name'), 'status labels localized');
    mos_ui_check(!str_contains($page, '>Evidence<'), 'English Evidence label absent');
    mos_ui_check(!str_contains($page, 'Evidence fingerprint относится'), 'technical fingerprint explanation absent');
    mos_ui_check(!str_contains($page, "item['evidence_fingerprint']"), 'fingerprint hash absent from user interface');
    mos_ui_check(!str_contains($page, '<?= e((string) $item[\'source_role\']) ?>'), 'raw source role absent from user interface');
    mos_ui_check(str_contains($page, 'directory-linked-card'), 'linked card interaction class present');
    mos_ui_check(str_contains($page, 'directory-info-card'), 'static information card class present');
    mos_ui_check(!str_contains($page, 'dashboard-tile module-tile'), 'static cards do not use dashboard hover class');
    mos_ui_check(str_contains($page, 'mos-records-table'), 'VUS-specific table layout class present');
    mos_ui_check(str_contains($page, 'directory-boundary-note'), 'compact boundary note class present');

    $config = require $root . '/config/themes.php';
    $themes = $config['themes'] ?? [];
    mos_ui_check(is_array($themes) && count($themes) === 3, 'three registered themes available');

    $sourceThemeRoot = $root . '/themes';
    $deployedThemeRoot = $root . '/public/themes';
    $themeRoot = is_dir($sourceThemeRoot) ? $sourceThemeRoot : $deployedThemeRoot;
    mos_ui_check(is_dir($themeRoot), 'theme asset root available');

    foreach (array_keys($themes) as $slug) {
        $asset = $themeRoot . "/{$slug}/assets/css/military-occupational-specialties.css";
        $css = file_get_contents($asset);
        mos_ui_check(is_string($css) && $css !== '', "{$slug} VUS stylesheet readable");
        mos_ui_check(
            in_array('css/military-occupational-specialties.css', $themes[$slug]['required_assets'] ?? [], true),
            "{$slug} VUS stylesheet registered"
        );
        mos_ui_check(str_contains($css, '.directory-linked-card:hover'), "{$slug} linked cards have hover elevation");
        mos_ui_check(str_contains($css, '.directory-info-card'), "{$slug} static card style present");
        mos_ui_check(str_contains($css, 'th:nth-child(1) { width: 26%; }'), "{$slug} source column rebalanced");
        mos_ui_check(str_contains($css, '.directory-boundary-note'), "{$slug} compact bottom note style present");
    }

    echo "MILITARY_OCCUPATIONAL_SPECIALTIES_UI_CHECK=PASS\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
