<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$passes = 0;

function check(bool $condition, string $label): void
{
    global $failures, $passes;
    if ($condition) {
        $passes++;
        echo "PASS: {$label}\n";
        return;
    }
    $failures[] = $label;
    echo "FAIL: {$label}\n";
}

function contents(string $root, string $path): string
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        return '';
    }
    $value = file_get_contents($file);
    return is_string($value) ? $value : '';
}

$allowlist = [
    'app/bootstrap.php',
    'public/admin/content.php',
    'docs/domains/README.md',
    'docs/PROJECT-STATUS.md',
    'docs/ROADMAP.md',
    'docs/TRACEABILITY.md',
    'database/migrations/013_lowest_unit_staffing_v1.sql',
    'app/Staffing/StaffingRepository.php',
    'app/Staffing/StaffingCreateUpdateTrait.php',
    'app/Staffing/StaffingDocumentTrait.php',
    'app/Staffing/StaffingLifecycleTrait.php',
    'app/Staffing/StaffingSlotTrait.php',
    'app/Staffing/StaffingSupportTrait.php',
    'app/Staffing/StaffingService.php',
    'app/Staffing/functions.php',
    'public/admin/staffing/registers.php',
    'public/admin/staffing/register.php',
    'public/admin/staffing/registers/create.php',
    'public/admin/staffing/registers/update.php',
    'public/admin/staffing/registers/archive.php',
    'public/admin/staffing/registers/restore.php',
    'public/admin/staffing/versions/create.php',
    'public/admin/staffing/versions/approve.php',
    'public/admin/staffing/versions/activate.php',
    'public/admin/staffing/versions/cancel.php',
    'public/admin/staffing/documents/create.php',
    'public/admin/staffing/documents/update.php',
    'public/admin/staffing/documents/unlink.php',
    'public/admin/staffing/slots/create.php',
    'public/admin/staffing/slots/update.php',
    'public/admin/staffing/slots/remove.php',
    'public/admin/staffing/compare.php',
    'public/admin/staffing/history.php',
    'public/admin/staffing/views/register-list.php',
    'public/admin/staffing/views/register-card.php',
    'public/admin/staffing/views/version-card.php',
    'public/admin/staffing/views/slot-form.php',
    'public/admin/staffing/views/document-form.php',
    'tools/Test-LowestUnitStaffingV1.ps1',
    'tools/check-lowest-unit-staffing-v1.php',
    'docs/domains/STAFFING.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-REVIEW.md',
];
check(count($allowlist) === 44 && count(array_unique($allowlist)) === 44, 'approved allowlist contains exactly 44 unique paths');

$existingApprovedDocuments = [
    'docs/domains/README.md',
    'docs/PROJECT-STATUS.md',
    'docs/ROADMAP.md',
    'docs/TRACEABILITY.md',
    'docs/domains/STAFFING.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-REVIEW.md',
];
$required = array_values(array_filter(
    $allowlist,
    static fn (string $path): bool => $path !== 'app/bootstrap.php'
        && !in_array($path, $existingApprovedDocuments, true)
));
foreach ($required as $path) {
    check(is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path)), "required implementation path exists: {$path}");
}
foreach ($existingApprovedDocuments as $path) {
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_dir($root . DIRECTORY_SEPARATOR . '.git')) {
        check(is_file($file), "approved existing document remains present: {$path}");
    }
}

$migration = contents($root, 'database/migrations/013_lowest_unit_staffing_v1.sql');
$tables = [
    'staffing_registers',
    'staffing_slot_identities',
    'staffing_versions',
    'staffing_documents',
    'staffing_version_documents',
    'staffing_slots',
    'staffing_slot_vus_requirements',
    'staffing_change_events',
];
foreach ($tables as $table) {
    check(str_contains($migration, "CREATE TABLE IF NOT EXISTS {$table}"), "migration creates {$table}");
}
$permissions = [
    'staffing.registers.view',
    'staffing.registers.create',
    'staffing.registers.update',
    'staffing.registers.publish',
    'staffing.registers.archive',
    'staffing.registers.history',
];
foreach ($permissions as $permission) {
    check(substr_count($migration, "'{$permission}'") === 2, "migration defines permission {$permission} idempotently");
}
check(!str_contains($migration, 'INSERT INTO role_permissions'), 'migration does not auto-grant non-owner permissions');
check(str_contains($migration, 'uq_staffing_versions_pending_guard'), 'single pending version guard exists');
check(str_contains($migration, 'uq_staffing_versions_active_guard'), 'single active version guard exists');
check(str_contains($migration, 'STAFFING_EVENT_APPEND_ONLY'), 'append-only history triggers exist');
check(str_contains($migration, 'STAFFING_PUBLISHED_DOCUMENT_IMMUTABLE'), 'published document immutability exists');
check(str_contains($migration, 'STAFFING_SLOT_UPDATE_DRAFT_ONLY'), 'slot mutations are draft-only');
check(str_contains($migration, 'STAFFING_SLOT_DELETE_DRAFT_ONLY'), 'published slots cannot be deleted');
check(str_contains($migration, 'STAFFING_SLOT_VUS_DELETE_DRAFT_ONLY'), 'published VUS requirements cannot be deleted');
check(str_contains($migration, 'STAFFING_VERSION_DOCUMENT_LINK_IDENTITY_IMMUTABLE'), 'document link identity is immutable while draft metadata remains editable');
check(str_contains($migration, 'STAFFING_VERSION_DOCUMENT_DRAFT_ONLY'), 'published document links are immutable');
check(str_contains($migration, 'STAFFING_SLOT_RANK_INVALID'), 'slot ranks must belong to the pinned rank catalog');
check(str_contains($migration, 'STAFFING_PUBLISHED_VERSION_CONTENT_IMMUTABLE'), 'published version content is immutable');
check(str_contains($migration, 'organizational_structure_element_id'), 'staffing binds to stable organizational element identity');
check(str_contains($migration, 'military_position_catalog_versions'), 'position catalog version is pinned');
check(str_contains($migration, 'military_rank_catalog_versions'), 'rank catalog version is pinned');
check(str_contains($migration, 'military_occupational_specialty_catalog_versions'), 'public VUS catalog version is pinned');

$service = contents($root, 'app/Staffing/StaffingService.php');
foreach (['StaffingCreateUpdateTrait', 'StaffingDocumentTrait', 'StaffingLifecycleTrait', 'StaffingSlotTrait', 'StaffingSupportTrait'] as $trait) {
    check(str_contains($service, "use {$trait};"), "service composes {$trait}");
}

$functions = contents($root, 'app/Staffing/functions.php');
check(str_contains($functions, "require_permission('staffing.registers.view')"), 'action authorization is view-gated');
check(str_contains($functions, 'require_csrf();'), 'mutation handler enforces CSRF');
check(str_contains($functions, "str_starts_with(\$resolvedPath, '/admin/staffing/')"), 'post-redirect path is module-scoped');

$routes = [
    'public/admin/staffing/registers/create.php' => 'staffing.registers.create',
    'public/admin/staffing/registers/update.php' => 'staffing.registers.update',
    'public/admin/staffing/registers/archive.php' => 'staffing.registers.archive',
    'public/admin/staffing/registers/restore.php' => 'staffing.registers.archive',
    'public/admin/staffing/versions/create.php' => 'staffing.registers.create',
    'public/admin/staffing/versions/approve.php' => 'staffing.registers.publish',
    'public/admin/staffing/versions/activate.php' => 'staffing.registers.publish',
    'public/admin/staffing/versions/cancel.php' => 'staffing.registers.publish',
    'public/admin/staffing/documents/create.php' => 'staffing.registers.update',
    'public/admin/staffing/documents/update.php' => 'staffing.registers.update',
    'public/admin/staffing/documents/unlink.php' => 'staffing.registers.update',
    'public/admin/staffing/slots/create.php' => 'staffing.registers.update',
    'public/admin/staffing/slots/update.php' => 'staffing.registers.update',
    'public/admin/staffing/slots/remove.php' => 'staffing.registers.update',
];
foreach ($routes as $path => $permission) {
    $route = contents($root, $path);
    check(str_contains($route, "staffing_require_action('{$permission}')"), "route permission: {$path}");
    check(str_contains($route, 'staffing_handle_action('), "route uses common POST/CSRF/PRG handler: {$path}");
}

$readPages = [
    'public/admin/staffing/registers.php' => 'staffing.registers.view',
    'public/admin/staffing/register.php' => 'staffing.registers.view',
    'public/admin/staffing/compare.php' => 'staffing.registers.view',
    'public/admin/staffing/history.php' => 'staffing.registers.history',
];
foreach ($readPages as $path => $permission) {
    check(str_contains(contents($root, $path), "require_permission('{$permission}')"), "read page permission: {$path}");
}

$contentPage = contents($root, 'public/admin/content.php');
check(str_contains($contentPage, "has_permission('staffing.registers.view')"), 'content navigation is staffing-permission-aware');
check(str_contains($contentPage, '/admin/staffing/registers.php'), 'content navigation links to staffing module');

$runtimePaths = array_values(array_filter(
    $required,
    static fn (string $path): bool => str_starts_with($path, 'app/')
        || str_starts_with($path, 'public/')
        || str_starts_with($path, 'database/migrations/')
));
$runtimeContent = '';
foreach ($runtimePaths as $path) {
    $runtimeContent .= "\n" . contents($root, $path);
}
check(!str_contains($runtimeContent, 'CitizenMilitaryAccounting'), 'excluded CitizenMilitaryAccounting contour is absent from runtime');
check(!preg_match('/\b(occupied|vacant)\b/i', $runtimeContent), 'no factual occupied/vacant state is modeled in runtime');
$operationalDocs = contents($root, 'docs/domains/STAFFING.md')
    . contents($root, 'docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md')
    . contents($root, 'docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md')
    . contents($root, 'tools/Test-LowestUnitStaffingV1.ps1');
check(str_contains($operationalDocs, 'NO_REAL_STAFFING_DATA_BEFORE_SECURITY_FOUNDATION')
    || str_contains($operationalDocs, 'REAL_STAFFING_DATA=PROHIBITED'), 'real-data operational condition is documented');
check(!preg_match('/\b(ФИО|паспорт|СНИЛС|дата рождения)\b/ui', $runtimeContent), 'personal-data fields are absent from runtime');

$gitDirectory = $root . DIRECTORY_SEPARATOR . '.git';
if (is_dir($gitDirectory) && function_exists('exec')) {
    $command = 'git -C ' . escapeshellarg($root)
        . ' diff --name-only d60db94e405979c8f29bdc3dcaae7950362fb13a...HEAD 2>&1';
    $output = [];
    $code = 0;
    exec($command, $output, $code);
    check($code === 0, 'git diff path inventory command succeeds');
    if ($code === 0) {
        $unexpected = array_values(array_diff($output, $allowlist));
        check(count($output) <= 44, 'changed path count does not exceed 44');
        check($unexpected === [], 'all changed paths are inside approved allowlist');
        if ($unexpected !== []) {
            echo 'UNEXPECTED_PATHS=' . implode(',', $unexpected) . "\n";
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, sprintf("LOWEST_UNIT_STAFFING_V1_STATIC=FAIL (%d failures, %d passes)\n", count($failures), $passes));
    exit(1);
}

echo "LOWEST_UNIT_STAFFING_V1_STATIC=PASS\n";
echo "PASS_COUNT={$passes}\n";
