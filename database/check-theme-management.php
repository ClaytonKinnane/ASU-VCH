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
    foreach ($expectedSlugs as $themeSlug) {
        theme_check($themes[$themeSlug]['available'] === true, $themeSlug . ' assets complete');
    }

    $evgeniya = $themes['asu-evgeniya-rostova'];
    theme_check($evgeniya['name'] === 'Евгения Ростова', 'Evgeniya Rostova display name');
    theme_check($evgeniya['appearance'] === 'light', 'Evgeniya Rostova appearance is light');
    theme_check($evgeniya['preview_colors'] === ['#fff7fb', '#c12a70', '#9a6bc4'], 'Evgeniya Rostova preview palette');

    $evgeniyaRequiredAssets = [
        'css/theme.css',
        'css/auth.css',
        'css/account.css',
        'css/users.css',
        'css/theme-management.css',
        'css/directories.css',
        'css/operation-result-modal.css',
        'img/hearts-pattern.svg',
        'img/balloons.svg',
        'img/teddy-bear.svg',
        'img/plush-bunny.svg',
    ];
    theme_check($evgeniya['required_assets'] === $evgeniyaRequiredAssets, 'Evgeniya Rostova required assets registered');

    foreach ($evgeniyaRequiredAssets as $asset) {
        theme_check(
            $registry->assetUrl('asu-evgeniya-rostova', $asset)
                === '/themes/asu-evgeniya-rostova/assets/' . $asset,
            'Evgeniya Rostova asset URL: ' . $asset
        );
    }

    foreach (['hearts-pattern.svg', 'balloons.svg', 'teddy-bear.svg', 'plush-bunny.svg'] as $image) {
        $path = $root . '/themes/asu-evgeniya-rostova/assets/img/' . $image;
        $content = file_get_contents($path);
        theme_check(is_string($content) && str_contains($content, '<svg'), $image . ' is SVG');
        foreach (['<script', 'foreignObject', '<image', 'javascript:', 'onload=', 'onclick=', 'xlink:href', 'href="http://', 'href="https://', "href='http://", "href='https://"] as $forbidden) {
            theme_check(!str_contains((string) $content, $forbidden), $image . ' excludes ' . $forbidden);
        }
    }

    $evgeniyaThemeRoot = $root . '/themes/asu-evgeniya-rostova';
    $themeFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($evgeniyaThemeRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($themeFiles as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $extension = strtolower($file->getExtension());
        theme_check(in_array($extension, ['css', 'svg'], true), 'Evgeniya Rostova contains only CSS/SVG: ' . $file->getFilename());
        if ($extension === 'css') {
            $content = file_get_contents($file->getPathname());
            theme_check(is_string($content), 'read Evgeniya Rostova CSS: ' . $file->getFilename());
            foreach (['http://', 'https://', 'data:', '@import', '/themes/asu-blue/', '/themes/asu-light-blue/'] as $forbidden) {
                theme_check(!str_contains((string) $content, $forbidden), $file->getFilename() . ' excludes ' . $forbidden);
            }
        }
    }

    foreach (['', '../theme.css', '..\\theme.css', "css/\0theme.css", 'https://example.test/theme.css', '/css/theme.css', '//theme.css'] as $invalidPath) {
        try {
            $registry->assetUrl('asu-blue', $invalidPath);
            throw new RuntimeException('invalid asset path accepted: ' . $invalidPath);
        } catch (InvalidArgumentException) {
        }
    }
    echo "OK invalid asset paths rejected\n";

    $executableRoots = [$root . '/app', $root . '/public'];
    $hardcoded = [];
    foreach ($executableRoots as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (is_string($content) && str_contains($content, '/themes/asu-blue/assets/')) {
                $hardcoded[] = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }
    }
    theme_check($hardcoded === [], 'no hardcoded asu-blue asset URLs in executable PHP');

    theme_check(is_file($root . '/public/assets/js/operation-result-modal.js'), 'shared operation modal JavaScript exists');
    theme_check(!is_file($root . '/themes/asu-blue/assets/js/operation-result-modal.js'), 'theme-specific operation modal JavaScript removed');
    theme_check(!is_dir($evgeniyaThemeRoot . '/assets/js'), 'Evgeniya Rostova has no JavaScript directory');

    $app = require $root . '/config/app.php';
    $localFile = $root . '/config/local.php';
    theme_check(is_file($localFile), 'local config exists');
    $local = require $localFile;
    $config = array_replace_recursive($app, $local);
    $db = $config['database'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $migrationStmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $migrationStmt->execute(['migration' => '006_theme_management.sql']);
    theme_check((int) $migrationStmt->fetchColumn() === 1, 'migration 006 applied');

    $columnStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema_name AND table_name = :table_name AND column_name = :column_name'
    );
    $columnStmt->execute(['schema_name' => $db['name'], 'table_name' => 'system_settings', 'column_name' => 'updated_by']);
    theme_check((int) $columnStmt->fetchColumn() === 1, 'system_settings.updated_by exists');

    $fkStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = :schema_name AND constraint_name = :constraint_name AND delete_rule = :delete_rule'
    );
    $fkStmt->execute(['schema_name' => $db['name'], 'constraint_name' => 'fk_system_settings_updated_by', 'delete_rule' => 'SET NULL']);
    theme_check((int) $fkStmt->fetchColumn() === 1, 'theme setting actor foreign key');

    $settingStmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :setting_key');
    $settingStmt->execute(['setting_key' => 'ui.active_theme']);
    $storedTheme = $settingStmt->fetchColumn();
    theme_check(is_string($storedTheme) && $registry->isRegistered($storedTheme), 'stored active theme is registered');

    $ownerId = $pdo->query(
        "SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id "
        . "WHERE r.code = 'system_owner' AND u.is_active = 1 AND u.approval_status = 'approved' AND u.deleted_at IS NULL ORDER BY u.id LIMIT 1"
    )->fetchColumn();
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
