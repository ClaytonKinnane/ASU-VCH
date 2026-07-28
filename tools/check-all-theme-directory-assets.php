<?php

declare(strict_types=1);

$themeCheckRoot = dirname(__DIR__);
require_once $themeCheckRoot . '/app/Theme/ThemeRegistry.php';

$themeConfig = require $themeCheckRoot . '/config/themes.php';
$registeredThemes = $themeConfig['themes'] ?? null;
if (!is_array($registeredThemes) || $registeredThemes === []) {
    throw new RuntimeException('Реестр тем пуст или недоступен.');
}

$themeRegistry = new ThemeRegistry($themeCheckRoot, $themeCheckRoot . '/config/themes.php');
$themeAssetRoot = is_dir($themeCheckRoot . '/public/themes')
    ? $themeCheckRoot . '/public/themes'
    : $themeCheckRoot . '/themes';
foreach (array_keys($registeredThemes) as $themeSlug) {
    $requiredAssets = $registeredThemes[$themeSlug]['required_assets'] ?? [];
    if (!is_array($requiredAssets) || !in_array('css/directories.css', $requiredAssets, true)) {
        throw new RuntimeException("Тема {$themeSlug} не регистрирует css/directories.css.");
    }
    if (!is_file($themeAssetRoot . '/' . $themeSlug . '/assets/css/directories.css')) {
        throw new RuntimeException("Не найден CSS справочников для темы {$themeSlug}.");
    }
    if (
        $themeRegistry->assetUrl($themeSlug, 'css/directories.css')
        !== '/themes/' . $themeSlug . '/assets/css/directories.css'
    ) {
        throw new RuntimeException("Опубликованный CSS справочников недоступен для темы {$themeSlug}.");
    }
}

echo 'OK registered theme directory assets: ' . count($registeredThemes) . PHP_EOL;
