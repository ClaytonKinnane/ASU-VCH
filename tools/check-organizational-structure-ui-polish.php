<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$failed = 0;
$passed = 0;

function ui_polish_check(bool $condition, string $message): void
{
    global $failed, $passed;

    if ($condition) {
        echo "PASS: {$message}\n";
        $passed++;
        return;
    }

    fwrite(STDERR, "FAIL: {$message}\n");
    $failed++;
}

function ui_polish_read(string $path): string
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new RuntimeException("Не удалось прочитать {$path}.");
    }

    return $contents;
}

try {
    $viewPaths = [
        'summary' => $root . '/public/admin/organization/views/summary-navigation.php',
        'tree' => $root . '/public/admin/organization/views/tree.php',
        'documents' => $root . '/public/admin/organization/views/documents.php',
        'versions' => $root . '/public/admin/organization/views/versions.php',
    ];
    $views = [];
    foreach ($viewPaths as $key => $path) {
        $views[$key] = ui_polish_read($path);
    }

    ui_polish_check(
        substr_count($views['summary'], 'organization-disclosure organization-disclosure--edit') === 1,
        'карточка использует edit disclosure'
    );
    ui_polish_check(
        substr_count($views['documents'], 'organization-disclosure organization-disclosure--edit') === 1
        && substr_count($views['documents'], 'organization-disclosure organization-disclosure--add') === 1,
        'документы используют edit и add disclosure'
    );
    ui_polish_check(
        substr_count($views['tree'], '<summary class="organization-disclosure">') === 1
        && substr_count($views['tree'], 'organization-disclosure organization-disclosure--edit') === 1
        && substr_count($views['tree'], 'organization-disclosure organization-disclosure--add') === 1
        && substr_count($views['tree'], 'organization-disclosure organization-disclosure--danger') === 1,
        'дерево использует default, edit, add и danger disclosure'
    );

    $allViews = implode("\n", $views);
    ui_polish_check(
        substr_count($allViews, 'organization-disclosure-icon') === 7
        && substr_count($allViews, 'organization-disclosure-icon" aria-hidden="true') === 7,
        'семь disclosure icon являются декоративными'
    );

    ui_polish_check(
        substr_count($views['documents'], 'class="organization-date-control"') === 2
        && substr_count($views['documents'], 'class="organization-date-icon" aria-hidden="true"') === 2,
        'два date-поля документов используют theme-aware wrapper'
    );
    ui_polish_check(
        substr_count($views['versions'], 'class="organization-date-control"') === 1
        && substr_count($views['versions'], 'class="organization-date-icon" aria-hidden="true"') === 1,
        'date-поле версии использует theme-aware wrapper'
    );
    ui_polish_check(
        substr_count($views['documents'], 'type="date"') === 2
        && substr_count($views['versions'], 'type="date"') === 1,
        'сохранены три native date input'
    );

    $csrfCounts = ['summary' => 4, 'tree' => 5, 'documents' => 3, 'versions' => 3];
    foreach ($csrfCounts as $view => $expected) {
        ui_polish_check(
            substr_count($views[$view], 'name="csrf_token"') === $expected,
            "{$view}: сохранено CSRF-полей {$expected}"
        );
    }

    $revisionCounts = ['summary' => 0, 'tree' => 5, 'documents' => 3, 'versions' => 2];
    foreach ($revisionCounts as $view => $expected) {
        ui_polish_check(
            substr_count($views[$view], 'name="expected_revision"') === $expected,
            "{$view}: сохранено expected_revision {$expected}"
        );
    }

    $requiredEndpoints = [
        'summary' => [
            '/admin/organization/versions/create-draft.php',
            '/admin/organization/structures/update.php',
            '/admin/organization/structures/archive.php',
            '/admin/organization/structures/restore.php',
        ],
        'tree' => [
            '/admin/organization/nodes/reorder.php',
            '/admin/organization/nodes/move.php',
            '/admin/organization/nodes/update.php',
            '/admin/organization/nodes/create.php',
            '/admin/organization/nodes/delete.php',
        ],
        'documents' => [
            '/admin/organization/documents/update.php',
            '/admin/organization/documents/unlink.php',
            '/admin/organization/documents/create.php',
        ],
        'versions' => [
            '/admin/organization/versions/approve.php',
            '/admin/organization/versions/activate.php',
            '/admin/organization/versions/cancel.php',
        ],
    ];
    foreach ($requiredEndpoints as $view => $endpoints) {
        $endpointsPreserved = true;
        foreach ($endpoints as $endpoint) {
            $endpointsPreserved = $endpointsPreserved && substr_count($views[$view], $endpoint) === 1;
        }
        ui_polish_check($endpointsPreserved, "{$view}: POST endpoints сохранены");
    }

    ui_polish_check(
        preg_match('/\son[a-z]+\s*=/i', $allViews) !== 1,
        'inline event handlers отсутствуют'
    );

    $themeSlugs = ['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'];
    $themeCss = [];
    foreach ($themeSlugs as $slug) {
        $themeCss[$slug] = ui_polish_read($root . "/themes/{$slug}/assets/css/organization.css");
    }
    ui_polish_check(
        count(array_unique(array_values($themeCss))) === 1,
        'структурный organization.css идентичен во всех трёх темах'
    );

    $requiredSelectors = [
        '.organization-layout summary',
        '.organization-disclosure',
        '.organization-disclosure::marker',
        '.organization-disclosure::-webkit-details-marker',
        '.organization-disclosure-icon',
        '.organization-disclosure--edit',
        '.organization-disclosure--add',
        '.organization-disclosure--danger',
        'details[open] > .organization-disclosure',
        '.organization-date-control',
        '.organization-date-icon',
        'input:-webkit-autofill',
        'input[type="date"]::-webkit-calendar-picker-indicator',
    ];
    foreach ($themeCss as $slug => $css) {
        $selectorsPresent = true;
        foreach ($requiredSelectors as $selector) {
            $selectorsPresent = $selectorsPresent && str_contains($css, $selector);
        }
        ui_polish_check($selectorsPresent, "{$slug}: обязательные UI polish selectors существуют");
        ui_polish_check(
            str_contains($css, 'pointer-events: none')
            && str_contains($css, '-webkit-text-fill-color: var(--text-primary)')
            && str_contains($css, '-webkit-mask:'),
            "{$slug}: calendar, autofill и mask contracts существуют"
        );
        ui_polish_check(
            !str_contains($css, 'http://')
            && !str_contains($css, 'https://')
            && !str_contains($css, 'data:')
            && !str_contains($css, '@import'),
            "{$slug}: внешние CSS/SVG зависимости отсутствуют"
        );
    }

    $runner = ui_polish_read($root . '/tools/Test-OrganizationalStructureV1.ps1');
    ui_polish_check(
        substr_count($runner, 'check-organizational-structure-ui-polish.php') === 1,
        'полный runner вызывает UI polish checker'
    );
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo "\nPASS: {$passed}\n";
echo "FAIL: {$failed}\n";

if ($failed !== 0) {
    exit(1);
}

echo "ORGANIZATIONAL STRUCTURE UI POLISH CHECK PASSED\n";
exit(0);
