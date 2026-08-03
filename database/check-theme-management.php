<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/app/Theme/ThemeRegistry.php';
require_once $root . '/app/Theme/ThemeSettingsRepository.php';
require_once $root . '/app/Theme/ThemeActivationService.php';

function theme_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'OK ' . $message . PHP_EOL;
}

try {
    $registry = new ThemeRegistry($root, $root . '/config/themes.php');
    theme_check($registry->defaultSlug() === 'asu-blue', 'default theme is asu-blue');

    $themes = $registry->themesWithAvailability();
    $expectedSlugs = ['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'];
    theme_check(array_keys($themes) === $expectedSlugs, 'registered themes: 3');
    foreach ($expectedSlugs as $slug) {
        theme_check(($themes[$slug]['available'] ?? false) === true, $slug . ' assets complete');
    }

    $expectedAssets = [
        'css/theme.css', 'css/auth.css', 'css/account.css', 'css/users.css',
        'css/theme-management.css', 'css/directories.css', 'css/military-ranks-v2.css',
        'css/military-occupational-specialties.css', 'css/organization.css',
        'css/operation-result-modal.css', 'img/hearts-pattern.svg', 'img/balloons.svg',
        'img/teddy-bear.svg', 'img/plush-bunny.svg',
    ];
    $evgeniya = $themes['asu-evgeniya-rostova'];
    theme_check($evgeniya['name'] === 'Евгения Ростова', 'Evgeniya Rostova display name');
    theme_check($evgeniya['appearance'] === 'light', 'Evgeniya Rostova appearance is light');
    theme_check($evgeniya['preview_colors'] === ['#fff7fb', '#c12a70', '#9a6bc4'], 'Evgeniya Rostova preview palette');
    theme_check($evgeniya['required_assets'] === $expectedAssets, 'Evgeniya Rostova required assets registered');
    foreach ($expectedAssets as $asset) {
        theme_check(
            $registry->assetUrl('asu-evgeniya-rostova', $asset)
                === '/themes/asu-evgeniya-rostova/assets/' . $asset,
            'Evgeniya Rostova asset URL: ' . $asset
        );
    }

    $assetRoot = is_dir($root . '/public/themes/asu-evgeniya-rostova/assets')
        ? $root . '/public/themes/asu-evgeniya-rostova/assets'
        : $root . '/themes/asu-evgeniya-rostova/assets';
    foreach (['hearts-pattern.svg', 'balloons.svg', 'teddy-bear.svg', 'plush-bunny.svg'] as $image) {
        $content = file_get_contents($assetRoot . '/img/' . $image);
        theme_check(is_string($content) && str_contains($content, '<svg'), $image . ' is SVG');
        foreach (['<script', 'foreignObject', '<image', 'javascript:', 'onload=', 'onclick=', 'xlink:href', 'href="http://', 'href="https://', "href='http://", "href='https://"] as $forbidden) {
            theme_check(!str_contains((string) $content, $forbidden), $image . ' excludes ' . $forbidden);
        }
    }
    $themeRoot = dirname($assetRoot);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($themeRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $extension = strtolower($file->getExtension());
        theme_check(in_array($extension, ['css', 'svg'], true), 'Evgeniya Rostova contains only CSS/SVG: ' . $file->getFilename());
        if ($extension === 'css') {
            $content = file_get_contents($file->getPathname());
            foreach (['http://', 'https://', 'data:', '@import', '/themes/asu-blue/', '/themes/asu-light-blue/'] as $forbidden) {
                theme_check(is_string($content) && !str_contains($content, $forbidden), $file->getFilename() . ' excludes ' . $forbidden);
            }
        }
    }
    theme_check(!is_dir($assetRoot . '/js'), 'Evgeniya Rostova has no JavaScript directory');

    foreach (['', '../theme.css', '..\\theme.css', "css/\0theme.css", 'https://example.test/theme.css', '/css/theme.css', '//theme.css'] as $invalidPath) {
        try {
            $registry->assetUrl('asu-blue', $invalidPath);
            throw new RuntimeException('invalid asset path accepted: ' . $invalidPath);
        } catch (InvalidArgumentException) {
        }
    }
    echo "OK invalid asset paths rejected\n";

    $hardcoded = [];
    foreach ([$root . '/app', $root . '/public'] as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $content = file_get_contents($file->getPathname());
                if (is_string($content) && str_contains($content, '/themes/asu-blue/assets/')) {
                    $hardcoded[] = $file->getPathname();
                }
            }
        }
    }
    theme_check($hardcoded === [], 'no hardcoded asu-blue asset URLs in executable PHP');
    theme_check(is_file($root . '/public/assets/js/operation-result-modal.js'), 'shared operation modal JavaScript exists');

    $app = require $root . '/config/app.php';
    $localFile = $root . '/config/local.php';
    theme_check(is_file($localFile), 'local config exists');
    $config = array_replace_recursive($app, require $localFile);
    $db = $config['database'];
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']),
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $stmt->execute(['migration' => '006_theme_management.sql']);
    theme_check((int) $stmt->fetchColumn() === 1, 'migration 006 applied');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND column_name = :column');
    $stmt->execute(['schema' => $db['name'], 'table' => 'system_settings', 'column' => 'updated_by']);
    theme_check((int) $stmt->fetchColumn() === 1, 'system_settings.updated_by exists');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = :schema AND constraint_name = :name AND delete_rule = :rule');
    $stmt->execute(['schema' => $db['name'], 'name' => 'fk_system_settings_updated_by', 'rule' => 'SET NULL']);
    theme_check((int) $stmt->fetchColumn() === 1, 'theme setting actor foreign key');

    $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :key');
    $stmt->execute(['key' => 'ui.active_theme']);
    $storedTheme = $stmt->fetchColumn();
    theme_check(is_string($storedTheme) && $registry->isRegistered($storedTheme), 'stored active theme is registered');
    $ownerId = $pdo->query("SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE r.code = 'system_owner' AND u.is_active = 1 AND u.approval_status = 'approved' AND u.deleted_at IS NULL ORDER BY u.id LIMIT 1")->fetchColumn();
    theme_check($ownerId !== false, 'active system owner available for repository transaction test');

    $repository = new ThemeSettingsRepository($pdo);
    $testTheme = $storedTheme === 'asu-evgeniya-rostova' ? 'asu-blue' : 'asu-evgeniya-rostova';
    $pdo->beginTransaction();
    try {
        $repository->lockActiveTheme();
        $repository->saveActiveTheme($testTheme, (int) $ownerId, new DateTimeImmutable());
        theme_check($repository->activeTheme() === $testTheme, 'theme setting repository write/read');
        $pdo->rollBack();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $service = new ThemeActivationService($pdo, $registry, $repository);
    try {
        $service->activate('../invalid', (int) $ownerId);
        throw new RuntimeException('invalid theme slug accepted');
    } catch (InvalidArgumentException) {
        echo "OK invalid theme activation rejected\n";
    }

    echo "OK theme management integration check completed\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
