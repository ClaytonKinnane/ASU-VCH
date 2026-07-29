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

    $tree = $views['tree'];
    $allViews = implode("\n", $views);

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
        substr_count($tree, 'organization-node-action-trigger') === 4
        && substr_count($tree, 'organization-disclosure--edit organization-node-action-trigger') === 1
        && substr_count($tree, 'organization-disclosure--add organization-node-action-trigger') === 1
        && substr_count($tree, 'organization-disclosure--danger organization-node-action-trigger') === 1
        && !str_contains($tree, '<details'),
        'дерево использует стабильные button triggers без details'
    );
    ui_polish_check(
        substr_count($allViews, 'organization-disclosure-icon') === 7
        && substr_count($allViews, 'organization-disclosure-icon" aria-hidden="true') === 7,
        'семь disclosure icon являются декоративными'
    );
    ui_polish_check(
        substr_count($tree, 'class="organization-node-action-bar"') === 1
        && substr_count($tree, 'class="organization-node-action-panels"') === 1
        && substr_count($tree, 'data-node-actions') === 1,
        'дерево разделяет стабильный action bar и область panels'
    );
    ui_polish_check(
        substr_count($tree, 'data-node-action-target=') === 4
        && substr_count($tree, 'aria-controls=') === 4
        && substr_count($tree, 'aria-expanded="false"') === 4
        && substr_count($tree, 'data-node-action-panel') === 4
        && substr_count($tree, 'data-node-action-panel hidden') === 4,
        'четыре node action trigger связаны с четырьмя hidden panels'
    );

    $nodePanelBindingsValid = true;
    foreach (['$movePanelId', '$editPanelId', '$addPanelId', '$deletePanelId'] as $panelVariable) {
        $nodePanelBindingsValid = $nodePanelBindingsValid
            && str_contains($tree, 'data-node-action-target="<?= e(' . $panelVariable . ') ?>"')
            && str_contains($tree, 'aria-controls="<?= e(' . $panelVariable . ') ?>"')
            && str_contains($tree, 'id="<?= e(' . $panelVariable . ') ?>"');
    }
    ui_polish_check($nodePanelBindingsValid, 'node action target, aria-controls и panel id согласованы');
    ui_polish_check(
        substr_count($tree, 'organization-direction-icon organization-direction-icon--up') === 1
        && substr_count($tree, 'organization-direction-icon organization-direction-icon--down') === 1
        && str_contains($tree, 'name="direction" value="up"')
        && str_contains($tree, 'name="direction" value="down"'),
        'кнопки Выше и Ниже сохраняют POST contract и имеют тематические стрелки'
    );
    ui_polish_check(
        substr_count($tree, '>Подтвердить удаление</button>') === 1
        && substr_count($tree, '>Удалить</span></button>') === 1
        && str_contains($tree, 'organization-node-action-panel--danger'),
        'удаление разделяет trigger и финальное подтверждение'
    );
    ui_polish_check(
        substr_count($tree, 'class="organization-tree-tool-buttons"') === 1
        && substr_count($tree, 'class="organization-tree-search"') === 1
        && substr_count($tree, 'data-tree-expand') === 1
        && substr_count($tree, 'data-tree-collapse') === 1
        && substr_count($tree, 'data-tree-search') === 1
        && str_contains($tree, 'maxlength="150"')
        && str_contains($tree, 'placeholder="Наименование, код или тип"'),
        'поиск дерева использует общий tools-контейнер с сохранённым contract'
    );
    ui_polish_check(
        substr_count($tree, 'class="tree-toggle" data-tree-toggle') === 1
        && substr_count($tree, 'aria-label="Свернуть или раскрыть ветвь"') === 1
        && str_contains($tree, ' ?>>▾</button>'),
        'кнопка уровня сохраняет native tree-toggle contract'
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
    ui_polish_check(
        substr_count($views['documents'], 'class="organization-date-picker-button"') === 2
        && substr_count($views['versions'], 'class="organization-date-picker-button"') === 1
        && substr_count($allViews, 'data-date-picker-target=') === 3,
        'три calendar icon являются функциональными button trigger'
    );

    $documentEditDateBindingValid = substr_count($views['documents'], '<?= e($documentDateId) ?>') === 3
        && str_contains($views['documents'], '<label for="<?= e($documentDateId) ?>">')
        && str_contains($views['documents'], 'id="<?= e($documentDateId) ?>" type="date"')
        && str_contains($views['documents'], 'data-date-picker-target="<?= e($documentDateId) ?>"');
    $documentCreateDateBindingValid = substr_count(
        $views['documents'],
        '<label for="organization-document-date-create">'
    ) === 1
        && substr_count($views['documents'], 'id="organization-document-date-create" type="date"') === 1
        && substr_count(
            $views['documents'],
            'data-date-picker-target="organization-document-date-create"'
        ) === 1;
    $effectiveFromDateBindingValid = substr_count(
        $views['versions'],
        '<label for="organization-effective-from">'
    ) === 1
        && substr_count($views['versions'], 'id="organization-effective-from" type="date"') === 1
        && substr_count(
            $views['versions'],
            'data-date-picker-target="organization-effective-from"'
        ) === 1;
    ui_polish_check(
        $documentEditDateBindingValid
        && $documentCreateDateBindingValid
        && $effectiveFromDateBindingValid,
        'calendar trigger связан с уникальным date input'
    );
    ui_polish_check(
        substr_count($allViews, 'class="organization-date-field"') === 3
        && substr_count($allViews, '<label for=') === 3,
        'date button не вложен в label и сохраняет явную связь label/input'
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

    $layoutEnd = ui_polish_read($root . '/public/admin/organization/views/layout-end.php');
    $uiControlsScript = ui_polish_read($root . '/public/assets/js/organization-ui-controls.js');
    $treeScript = ui_polish_read($root . '/public/assets/js/organization-tree.js');
    ui_polish_check(
        substr_count($layoutEnd, '/assets/js/organization-ui-controls.js') === 1
        && str_contains($layoutEnd, 'organization-ui-controls.js" defer'),
        'organization UI controls script подключён один раз с defer'
    );
    ui_polish_check(
        str_contains($uiControlsScript, '[data-date-picker-target]')
        && str_contains($uiControlsScript, "typeof input.showPicker === 'function'")
        && str_contains($uiControlsScript, 'input.showPicker()')
        && str_contains($uiControlsScript, 'input.click()'),
        'calendar script использует showPicker и fallback click'
    );
    ui_polish_check(
        str_contains($uiControlsScript, '[data-node-action-target]')
        && str_contains($uiControlsScript, '[data-node-action-panel]')
        && str_contains($uiControlsScript, 'closeNodeActionPanels(container)')
        && str_contains($uiControlsScript, 'panel.hidden = true')
        && str_contains($uiControlsScript, 'panel.hidden = false')
        && str_contains($uiControlsScript, "trigger.setAttribute('aria-expanded', 'true')")
        && str_contains($uiControlsScript, 'container.contains(panel)'),
        'node action script синхронизирует panels и aria-expanded внутри одного узла'
    );
    ui_polish_check(
        str_contains($uiControlsScript, 'input.focus({ preventScroll: true })')
        && str_contains($uiControlsScript, 'input.disabled || input.readOnly')
        && !str_contains($uiControlsScript, '.submit(')
        && !str_contains($uiControlsScript, 'requestSubmit'),
        'UI controls script сохраняет focus и не отправляет формы из toggle logic'
    );
    ui_polish_check(
        str_contains($treeScript, "toggle.textContent = isCollapsed ? '▸' : '▾';")
        && str_contains($treeScript, "toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');")
        && str_contains($treeScript, '[data-tree-search]'),
        'tree script сохраняет glyph, aria-expanded и search behavior'
    );

    $themeSlugs = ['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'];
    $themeRoot = is_dir($root . '/public/themes')
        ? $root . '/public/themes'
        : $root . '/themes';
    $themeCss = [];
    foreach ($themeSlugs as $slug) {
        $themeCss[$slug] = ui_polish_read($themeRoot . "/{$slug}/assets/css/organization.css");
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
        '.organization-disclosure--edit .organization-disclosure-icon::before',
        '.organization-disclosure--edit .organization-disclosure-icon::after',
        '.organization-disclosure--add',
        '.organization-disclosure--danger',
        'details[open] > .organization-disclosure',
        '.organization-date-field',
        '.organization-date-control',
        '.organization-date-picker-button',
        '.organization-date-icon',
        '.organization-node-action-bar',
        '.organization-node-action-trigger',
        '.organization-node-action-panels',
        '.organization-node-action-panel',
        '.organization-direction-icon',
        '.organization-direction-icon--down',
        '.organization-tree-tools',
        '.organization-tree-tool-buttons',
        '.organization-tree-search',
        '.tree-toggle',
        '.tree-toggle:not(:disabled):hover',
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
            str_contains($css, '--organization-action-height: 44px')
            && str_contains($css, 'height: var(--organization-action-height)')
            && str_contains($css, 'align-self: flex-end'),
            "{$slug}: общие action controls имеют единый height contract"
        );
        ui_polish_check(
            str_contains($css, '.organization-node-action-bar .secondary-button, .organization-node-action-trigger')
            && str_contains($css, '.organization-node-action-panel[hidden] { display: none; }')
            && str_contains($css, '.organization-node-actions { margin-top: 12px; display: grid; gap: 10px; }')
            && !str_contains($css, '.organization-node-actions details[open]'),
            "{$slug}: node triggers остаются в action bar, panels раскрываются ниже"
        );
        ui_polish_check(
            str_contains($css, 'clip-path: polygon(50% 0')
            && str_contains($css, '.organization-direction-icon--down { transform: rotate(180deg); }'),
            "{$slug}: стрелки Выше и Ниже имеют тематическую геометрию"
        );
        ui_polish_check(
            str_contains(
                $css,
                '.organization-node-action-trigger.organization-disclosure--edit .organization-disclosure-icon { transform: rotate(-45deg); }'
            )
            && str_contains(
                $css,
                '.organization-node-action-trigger.organization-disclosure--add .organization-disclosure-icon { transform: none; }'
            )
            && !str_contains(
                $css,
                '.organization-node-action-trigger.organization-disclosure--edit .organization-disclosure-icon, .organization-node-action-trigger.organization-disclosure--add .organization-disclosure-icon { transform: none; }'
            )
            && str_contains($css, 'border-left: 4px solid var(--focus-color)'),
            "{$slug}: node edit icon использует единый наклонный карандаш"
        );
        ui_polish_check(
            str_contains($css, '.organization-tree-tools { display: grid; grid-template-columns: max-content;')
            && str_contains(
                $css,
                '.organization-tree-tool-buttons { display: grid; grid-template-columns: repeat(2, max-content); gap: 8px; }'
            )
            && str_contains($css, '.organization-tree-search { width: 100%; min-width: 0; }'),
            "{$slug}: search input выровнен по внешним границам button group"
        );
        ui_polish_check(
            str_contains($css, 'border: 1px solid var(--focus-color)')
            && str_contains($css, 'color: var(--focus-color)')
            && str_contains($css, 'font-size: 18px')
            && str_contains($css, 'font-weight: 900')
            && str_contains($css, '.tree-toggle:not(:disabled):hover'),
            "{$slug}: tree toggle имеет заметный theme-aware indicator"
        );
        ui_polish_check(
            str_contains($css, 'pointer-events: none')
            && str_contains($css, '-webkit-text-fill-color: var(--text-primary)')
            && str_contains($css, '-webkit-mask:')
            && str_contains($css, '.organization-date-picker-button {'),
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
