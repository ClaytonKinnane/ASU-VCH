<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/app/Theme/ThemeRegistry.php';

function asset_failure_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'OK ' . $message . PHP_EOL;
}

function asset_failure_copy_tree(string $source, string $destination): void
{
    if (!is_dir($source) || (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination))) {
        throw new RuntimeException('Не удалось подготовить временную копию темы.');
    }
    foreach (new DirectoryIterator($source) as $item) {
        if ($item->isDot()) {
            continue;
        }
        $target = $destination . DIRECTORY_SEPARATOR . $item->getFilename();
        if ($item->isDir()) {
            asset_failure_copy_tree($item->getPathname(), $target);
        } elseif (!copy($item->getPathname(), $target)) {
            throw new RuntimeException('Не удалось скопировать временный asset темы.');
        }
    }
}

function asset_failure_remove_tree(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($directory);
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'asu-vch-theme-' . bin2hex(random_bytes(8));
try {
    $sourceThemeRoot = is_dir($root . '/public/themes') ? $root . '/public/themes' : $root . '/themes';
    if (!mkdir($temporaryRoot . '/config', 0777, true) || !mkdir($temporaryRoot . '/themes', 0777, true)) {
        throw new RuntimeException('Не удалось создать временный каталог проверки темы.');
    }
    if (!copy($root . '/config/themes.php', $temporaryRoot . '/config/themes.php')) {
        throw new RuntimeException('Не удалось скопировать временный реестр тем.');
    }
    foreach (['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'] as $slug) {
        asset_failure_copy_tree($sourceThemeRoot . '/' . $slug, $temporaryRoot . '/themes/' . $slug);
    }

    $missingAsset = $temporaryRoot . '/themes/asu-evgeniya-rostova/assets/img/plush-bunny.svg';
    asset_failure_check(is_file($missingAsset) && unlink($missingAsset), 'missing asset sandbox prepared');

    $registry = new ThemeRegistry($temporaryRoot, $temporaryRoot . '/config/themes.php');
    asset_failure_check(!$registry->isAvailable('asu-evgeniya-rostova'), 'missing asset makes Evgeniya Rostova unavailable');
    asset_failure_check(
        $registry->missingAssets('asu-evgeniya-rostova') === ['img/plush-bunny.svg'],
        'missing asset is reported exactly'
    );

    echo "OK theme missing-asset check completed\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL ' . $exception->getMessage() . PHP_EOL);
    exit(1);
} finally {
    asset_failure_remove_tree($temporaryRoot);
}
