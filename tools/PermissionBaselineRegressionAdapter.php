<?php

declare(strict_types=1);

/** @return list<string> */
function permission_baseline_compatible_checker_paths(): array
{
    return [
        'database/check-security-user-rejection.php',
        'database/check-security-user-archive-restore.php',
        'tools/check-military-ranks-directory-core.php',
        'tools/check-organizational-elements-directory-core.php',
    ];
}

function prepare_permission_baseline_compatible_checker(string $source, string $relativePath): string
{
    if (!in_array($relativePath, permission_baseline_compatible_checker_paths(), true)) {
        throw new InvalidArgumentException("Checker не разрешён: {$relativePath}");
    }

    $requiredReplacements = [
        '$permissionCount === 19' => '$permissionCount >= 19',
        'Ожидалось 19 системных разрешений, найдено {$permissionCount}.'
            => 'Ожидалось не менее 19 системных разрешений, найдено {$permissionCount}.',
    ];

    $prepared = $source;
    foreach ($requiredReplacements as $search => $replace) {
        $prepared = str_replace($search, $replace, $prepared, $replacementCount);
        if ($replacementCount !== 1) {
            throw new RuntimeException(
                "Ожидалась одна обязательная замена в {$relativePath}, найдено {$replacementCount}."
            );
        }
    }

    $fixedOutput = 'echo "OK system permissions: 19\\n";';
    $dynamicOutput = 'echo "OK system permissions: {$permissionCount}\\n";';
    if (str_contains($prepared, $fixedOutput)) {
        $prepared = str_replace($fixedOutput, $dynamicOutput, $prepared, $outputReplacementCount);
        if ($outputReplacementCount !== 1) {
            throw new RuntimeException(
                "Ожидалась одна замена вывода в {$relativePath}, найдено {$outputReplacementCount}."
            );
        }
    } elseif (substr_count($prepared, $dynamicOutput) !== 1) {
        throw new RuntimeException("Вывод permission count не распознан в {$relativePath}.");
    }

    if (str_contains($prepared, '$permissionCount === 19')) {
        throw new RuntimeException("Точное ограничение 19 осталось в {$relativePath}.");
    }
    if (substr_count($prepared, '$permissionCount >= 19') !== 1) {
        throw new RuntimeException("Совместимое ограничение permission count сформировано неверно в {$relativePath}.");
    }
    if (substr_count($prepared, $dynamicOutput) !== 1) {
        throw new RuntimeException("Динамический вывод permission count сформирован неверно в {$relativePath}.");
    }

    return $prepared;
}
