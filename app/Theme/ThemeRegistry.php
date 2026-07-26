<?php

declare(strict_types=1);

final class ThemeRegistry
{
    private string $assetRoot;
    private string $defaultSlug;

    /** @var array<string,array{name:string,description:string,appearance:string,preview_colors:list<string>,required_assets:list<string>}> */
    private array $themes;

    public function __construct(string $projectRoot, string $configFile)
    {
        $sourceThemeRoot = $projectRoot . '/themes';
        $deployedThemeRoot = $projectRoot . '/public/themes';
        $this->assetRoot = is_dir($deployedThemeRoot) ? $deployedThemeRoot : $sourceThemeRoot;

        if (!is_file($configFile)) {
            throw new RuntimeException('Реестр тем не найден.');
        }

        $config = require $configFile;
        if (!is_array($config)) {
            throw new RuntimeException('Реестр тем имеет неверный формат.');
        }

        $default = $config['default'] ?? null;
        $themes = $config['themes'] ?? null;
        if (!is_string($default) || !is_array($themes) || $themes === []) {
            throw new RuntimeException('Реестр тем не содержит обязательных данных.');
        }

        $validated = [];
        foreach ($themes as $slug => $metadata) {
            if (!is_string($slug) || preg_match('/\A[a-z0-9][a-z0-9-]{1,63}\z/D', $slug) !== 1) {
                throw new RuntimeException('Реестр тем содержит некорректный идентификатор.');
            }
            if (!is_array($metadata)) {
                throw new RuntimeException('Метаданные темы имеют неверный формат.');
            }

            $name = $metadata['name'] ?? null;
            $description = $metadata['description'] ?? null;
            $appearance = $metadata['appearance'] ?? null;
            $colors = $metadata['preview_colors'] ?? null;
            $assets = $metadata['required_assets'] ?? null;

            if (!is_string($name) || trim($name) === '' || !is_string($description) || trim($description) === '') {
                throw new RuntimeException('Тема должна иметь название и описание.');
            }
            if (!in_array($appearance, ['dark', 'light'], true)) {
                throw new RuntimeException('Тема содержит неизвестный тип оформления.');
            }
            if (!is_array($colors) || count($colors) !== 3) {
                throw new RuntimeException('Тема должна содержать три цвета предварительного просмотра.');
            }
            foreach ($colors as $color) {
                if (!is_string($color) || preg_match('/\A#[0-9a-fA-F]{6}\z/D', $color) !== 1) {
                    throw new RuntimeException('Тема содержит некорректный цвет предварительного просмотра.');
                }
            }
            if (!is_array($assets) || $assets === []) {
                throw new RuntimeException('Тема не содержит список обязательных ресурсов.');
            }
            $validatedAssets = [];
            foreach ($assets as $asset) {
                if (!is_string($asset)) {
                    throw new RuntimeException('Путь ресурса темы имеет неверный формат.');
                }
                $validatedAssets[] = $this->validateAssetPath($asset);
            }

            $validated[$slug] = [
                'name' => trim($name),
                'description' => trim($description),
                'appearance' => $appearance,
                'preview_colors' => array_values($colors),
                'required_assets' => array_values(array_unique($validatedAssets)),
            ];
        }

        if (!array_key_exists($default, $validated)) {
            throw new RuntimeException('Тема по умолчанию отсутствует в реестре.');
        }

        $this->defaultSlug = $default;
        $this->themes = $validated;
    }

    public function defaultSlug(): string
    {
        return $this->defaultSlug;
    }

    public function isRegistered(string $slug): bool
    {
        return array_key_exists($slug, $this->themes);
    }

    /** @return array{name:string,description:string,appearance:string,preview_colors:list<string>,required_assets:list<string>} */
    public function metadata(string $slug): array
    {
        if (!$this->isRegistered($slug)) {
            throw new InvalidArgumentException('Неизвестная тема оформления.');
        }
        return $this->themes[$slug];
    }

    /** @return list<string> */
    public function missingAssets(string $slug): array
    {
        $metadata = $this->metadata($slug);
        $missing = [];
        foreach ($metadata['required_assets'] as $asset) {
            if (!is_file($this->assetFile($slug, $asset))) {
                $missing[] = $asset;
            }
        }
        return $missing;
    }

    public function isAvailable(string $slug): bool
    {
        return $this->isRegistered($slug) && $this->missingAssets($slug) === [];
    }

    /** @return array<string,array{name:string,description:string,appearance:string,preview_colors:list<string>,required_assets:list<string>,available:bool,missing_assets:list<string>}> */
    public function themesWithAvailability(): array
    {
        $result = [];
        foreach ($this->themes as $slug => $metadata) {
            $missing = $this->missingAssets($slug);
            $result[$slug] = $metadata + [
                'available' => $missing === [],
                'missing_assets' => $missing,
            ];
        }
        return $result;
    }

    public function assetUrl(string $slug, string $asset): string
    {
        if (!$this->isRegistered($slug)) {
            throw new InvalidArgumentException('Неизвестная тема оформления.');
        }
        $asset = $this->validateAssetPath($asset);
        if (!is_file($this->assetFile($slug, $asset))) {
            throw new RuntimeException('Ресурс темы недоступен.');
        }
        return '/themes/' . rawurlencode($slug) . '/assets/' . implode('/', array_map('rawurlencode', explode('/', $asset)));
    }

    private function assetFile(string $slug, string $asset): string
    {
        return $this->assetRoot . '/' . $slug . '/assets/' . $asset;
    }

    private function validateAssetPath(string $asset): string
    {
        if ($asset === '' || str_contains($asset, "\0") || str_contains($asset, '..') || str_contains($asset, '\\') || str_contains($asset, '://') || str_starts_with($asset, '/') || str_contains($asset, '//')) {
            throw new InvalidArgumentException('Некорректный путь ресурса темы.');
        }
        $segments = explode('/', $asset);
        foreach ($segments as $segment) {
            if ($segment === '' || preg_match('/\A[A-Za-z0-9._-]+\z/D', $segment) !== 1) {
                throw new InvalidArgumentException('Некорректный путь ресурса темы.');
            }
        }
        return implode('/', $segments);
    }
}
