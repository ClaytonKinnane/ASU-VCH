<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$runtimeRoot = $root;
foreach (array_slice($argv ?? [], 1) as $argument) {
    if (is_string($argument) && str_starts_with($argument, '--runtime-root=')) {
        $candidate = substr($argument, strlen('--runtime-root='));
        if ($candidate !== '' && is_dir($candidate)) {
            $runtimeRoot = rtrim($candidate, '/\\');
        }
    }
}
$failures = [];
$passes = 0;

function mpv1_check(bool $condition, string $label): void
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

function mpv1_contents(string $root, string $path): string
{
    $value = file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    return is_string($value) ? $value : '';
}

$allowlist = [
    'app/bootstrap.php',
    'app/Directory/MilitaryPositionCatalogRepository.php',
    'app/Directory/MilitaryPositionCatalogService.php',
    'app/Directory/MilitaryPositionCatalogFunctions.php',
    'app/Staffing/StaffingRepository.php',
    'public/admin/content.php',
    'public/admin/directories.php',
    'public/admin/directories/military-positions.php',
    'public/admin/directories/military-positions/version.php',
    'public/admin/directories/military-positions/history.php',
    'public/admin/directories/military-positions/versions/create.php',
    'public/admin/directories/military-positions/versions/publish.php',
    'public/admin/directories/military-positions/versions/cancel.php',
    'public/admin/directories/military-positions/entries/create.php',
    'public/admin/directories/military-positions/entries/update.php',
    'public/admin/directories/military-positions/entries/archive.php',
    'public/admin/directories/military-positions/entries/restore.php',
    'public/admin/directories/military-positions/views/version-card.php',
    'public/admin/directories/military-positions/views/entry-card.php',
    'public/admin/directories/military-positions/views/entry-form.php',
    'database/migrations/014_military_positions_directory_v1.sql',
    'themes/asu-blue/assets/css/directories.css',
    'themes/asu-light-blue/assets/css/directories.css',
    'themes/asu-evgeniya-rostova/assets/css/directories.css',
    'tools/Test-MilitaryPositionsDirectoryV1.ps1',
    'tools/check-military-positions-directory-v1.php',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md',
    'docs/domains/REFERENCE.md',
    'docs/domains/STAFFING.md',
    'docs/ACCESS.md',
    'docs/DATABASE-CURRENT.md',
    'docs/migrations/README.md',
    'docs/PROJECT-STATUS.md',
    'docs/ROADMAP.md',
    'docs/TRACEABILITY.md',
    'docs/CHAT-HANDOFF.md',
];
mpv1_check(count($allowlist) === 38 && count(array_unique($allowlist)) === 38, 'approved allowlist contains exactly 38 unique paths');
foreach ($allowlist as $path) {
    mpv1_check(is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path)), "approved path exists: {$path}");
}

$correctiveAllowlist = [
    'public/admin/directories/military-positions.php',
    'public/admin/directories/military-positions/version.php',
    'public/admin/directories/military-positions/views/version-card.php',
    'public/admin/directories/military-positions/views/entry-card.php',
    'themes/asu-blue/assets/css/directories.css',
    'themes/asu-light-blue/assets/css/directories.css',
    'themes/asu-evgeniya-rostova/assets/css/directories.css',
    'tools/check-military-positions-directory-v1.php',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md',
    'docs/CHAT-HANDOFF.md',
];
mpv1_check(count($correctiveAllowlist) === 12 && count(array_unique($correctiveAllowlist)) === 12, 'corrective allowlist contains exactly 12 unique paths');
foreach ($correctiveAllowlist as $path) {
    mpv1_check(in_array($path, $allowlist, true), "corrective path is inside approved allowlist: {$path}");
}

$protectedHashes = [
    'database/migrations/010_military_positions_directory.sql' => 'b42c68de5961b005634fa136ebae8ef5a0984ea671e5101a71354289274c0a1f',
    'database/MilitaryPositionMigrationCompatibility.php' => '5249006019bff7f2679e1ebc9e41c75cc65ece6656d66dd13b74baf0e5e64707',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part00' => '5949f0964d36d9e4a6d1fd9d516b262eebcd6350a05b562233dcabb15d354010',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part01' => 'fb33a628312b558398a1b5e07f32d16a5ee77809812738b40651ad19f733fb25',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part02' => '0fd8dfcc662006fe08db0a6d5acaf56ef6935ec8ef194e6441216d9238de6fab',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part03' => '269ee00630683e1c9e1c9cb74d157bec79fbabb417c342b5fe5d522bf6946dd0',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part04' => '79b3c7fd1e91b34d3498fffaa0ff00d230bb516bd049c2c28b0482c6202f18c2',
];
foreach ($protectedHashes as $path => $expectedHash) {
    $contents = file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    $normalizedContents = is_string($contents)
        ? str_replace(["\r\n", "\r"], "\n", $contents)
        : null;
    $actualHash = is_string($normalizedContents)
        ? hash('sha256', $normalizedContents)
        : false;
    mpv1_check(is_string($actualHash) && hash_equals($expectedHash, $actualHash), "migration 010 protected hash: {$path}");
}

$migration = mpv1_contents($root, 'database/migrations/014_military_positions_directory_v1.sql');
mpv1_check(str_contains($migration, 'MP14_PARTIAL_SCHEMA_DETECTED'), 'migration partial state fails closed');
mpv1_check(str_contains($migration, "status IN ('draft','published','superseded','cancelled')"), 'catalog lifecycle constraint');
mpv1_check(str_contains($migration, 'uq_mp_catalog_versions_draft_guard'), 'single draft guard');
mpv1_check(str_contains($migration, 'uq_mp_types_stable_key_v1'), 'stable identity uniqueness');
mpv1_check(str_contains($migration, 'uq_mp_types_normalized_name_v1'), 'normalized name uniqueness');
mpv1_check(str_contains($migration, 'MP_EVENT_APPEND_ONLY'), 'append-only history triggers');
mpv1_check(str_contains($migration, "CREATE TRIGGER trg_staffing_slots_insert BEFORE INSERT")
    && str_contains($migration, "v.catalog_kind='legacy' OR t.status='active'"), 'new Staffing slots reject archived canonical entries');
mpv1_check(str_contains($migration, 'NEW.position_type_id <=> OLD.position_type_id')
    && str_contains($migration, 'NEW.position_variant_id <=> OLD.position_variant_id'), 'existing Staffing pins remain editable without remap');
mpv1_check(!preg_match('/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?military_position/iu', $migration), 'no destructive legacy table drop');
mpv1_check(!str_contains($migration, 'INSERT INTO role_permissions'), 'no automatic non-owner permission grants');

$permissions = [
    'directories.military_positions.view',
    'directories.military_positions.manage',
    'directories.military_positions.publish',
    'directories.military_positions.history',
];
foreach ($permissions as $permission) {
    mpv1_check(substr_count($migration, "'{$permission}'") === 2, "permission defined idempotently: {$permission}");
}

$seedPattern = "/@mp14_canonical_version_id,'canonical-position-[0-9]{3}','canonical-[0-9]{3}','([^']+)','[^']+',NULL,NULL,([01]),'local'/u";
$seedMatches = [];
mpv1_check(preg_match_all($seedPattern, $migration, $seedMatches) === 24, 'exactly 24 canonical seed rows');
$expectedNames = [
    'Командир роты','Заместитель командира роты по военно-политической работе','Старшина','Санитарный инструктор',
    'Командир взвода','Начальник аппаратной-техник','Техник','Оператор','Командир отделения','Старший механик',
    'Механик','Начальник радиостанции','Механик-радиотелефонист','Радиотелеграфист','Водитель-электрик',
    'Радиотелефонист','Водитель-радиотелефонист','Заместитель командира взвода-командир отделения',
    'Регулировщик','Регулировщик-наводчик','Регулировщик-радиотелефонист','Водитель-регулировщик','Водитель','Водитель-гранатометчик',
];
mpv1_check(($seedMatches[1] ?? []) === $expectedNames, 'seed names match approved ordered set');
$combinedNames = [];
foreach (($seedMatches[1] ?? []) as $index => $name) {
    if (($seedMatches[2][$index] ?? '0') === '1') {
        $combinedNames[] = $name;
    }
}
$expectedCombined = [
    'Начальник аппаратной-техник','Механик-радиотелефонист','Водитель-электрик','Водитель-радиотелефонист',
    'Заместитель командира взвода-командир отделения','Регулировщик-наводчик','Регулировщик-радиотелефонист',
    'Водитель-регулировщик','Водитель-гранатометчик',
];
mpv1_check($combinedNames === $expectedCombined, 'exact nine explicit combined flags');

$bootstrap = mpv1_contents($root, 'app/bootstrap.php');
mpv1_check(str_contains($bootstrap, 'MilitaryPositionCatalogService.php'), 'bootstrap loads service');
mpv1_check(str_contains($bootstrap, 'function military_position_catalog_service()'), 'bootstrap exposes service factory');

$service = mpv1_contents($root, 'app/Directory/MilitaryPositionCatalogService.php');
foreach (['createDraft','createEntry','updateEntry','archiveEntry','restoreEntry','publish','cancel'] as $method) {
    mpv1_check(str_contains($service, "function {$method}("), "service method exists: {$method}");
}
mpv1_check(str_contains($service, 'expectedCatalogRevision'), 'optimistic catalog revisions enforced');
mpv1_check(str_contains($service, 'normalized_name'), 'normalized name persisted');

$entryActions = mpv1_contents($root, 'public/admin/directories/military-positions/entries/create.php')
    . mpv1_contents($root, 'public/admin/directories/military-positions/entries/update.php');
foreach (['vus','rank','unit','person','equipment','occupied','vacant'] as $forbidden) {
    mpv1_check(!preg_match('/[\'\"]' . preg_quote($forbidden, '/') . '[^\'\"]*[\'\"]\s*=>/i', $entryActions), "forbidden entry request field absent: {$forbidden}");
}

$routes = [
    'versions/create.php' => 'directories.military_positions.manage',
    'versions/publish.php' => 'directories.military_positions.publish',
    'versions/cancel.php' => 'directories.military_positions.publish',
    'entries/create.php' => 'directories.military_positions.manage',
    'entries/update.php' => 'directories.military_positions.manage',
    'entries/archive.php' => 'directories.military_positions.manage',
    'entries/restore.php' => 'directories.military_positions.manage',
];
foreach ($routes as $suffix => $permission) {
    $path = 'public/admin/directories/military-positions/' . $suffix;
    $contents = mpv1_contents($root, $path);
    mpv1_check(str_contains($contents, "military_positions_require_action('{$permission}')"), "route permission: {$suffix}");
    mpv1_check(str_contains($contents, 'military_positions_handle_action('), "route CSRF/PRG handler: {$suffix}");
}

$contentPage = mpv1_contents($root, 'public/admin/content.php');
$directoryPage = mpv1_contents($root, 'public/admin/directories.php');
mpv1_check(str_contains($contentPage, "has_permission('directories.military_positions.view')"), 'content navigation is permission-aware');
mpv1_check(str_contains($directoryPage, "has_permission('directories.military_positions.view')"), 'directory tile list is permission-aware');

$versionListPage = mpv1_contents($root, 'public/admin/directories/military-positions.php');
$versionDetailPage = mpv1_contents($root, 'public/admin/directories/military-positions/version.php');
$versionCardView = mpv1_contents($root, 'public/admin/directories/military-positions/views/version-card.php');
$entryCardView = mpv1_contents($root, 'public/admin/directories/military-positions/views/entry-card.php');
mpv1_check(
    str_contains($versionListPage, "\$versionCardMode = 'list';")
    && str_contains($versionDetailPage, "\$versionCardMode = 'detail';"),
    'version card presentation modes are explicit'
);
mpv1_check(
    str_contains($versionCardView, "'military-position-version-'")
    && str_contains($versionCardView, '>Открыть</a>')
    && str_contains($versionCardView, '>Закрыть</a>'),
    'version cards expose anchored open and close navigation'
);
mpv1_check(
    str_contains($versionCardView, 'История этой версии')
    && !str_contains($versionDetailPage, 'military-position-history-link'),
    'version history action is in the contextual card header'
);
mpv1_check(
    str_contains($entryCardView, 'military-position-entry-action military-position-state-action')
    && str_contains($entryCardView, '$stateReasonLabel')
    && strpos($entryCardView, 'name="change_reason"') > strpos($entryCardView, 'military-position-state-action'),
    'entry lifecycle reason is contained by its disclosure panel'
);
$staffingRepository = mpv1_contents($root, 'app/Staffing/StaffingRepository.php');
mpv1_check(str_contains($staffingRepository, "v.catalog_kind='legacy' OR t.status='active'"), 'Staffing excludes archived canonical entries from selectors');

$historyPage = mpv1_contents($root, 'public/admin/directories/military-positions/history.php');
mpv1_check(str_contains($historyPage, 'military_positions_history_state'), 'history decodes states for readable presentation');
mpv1_check(!str_contains($historyPage, 'json_encode('), 'history page does not render raw JSON');
$functions = mpv1_contents($root, 'app/Directory/MilitaryPositionCatalogFunctions.php');
mpv1_check(str_contains($functions, "!str_starts_with(\$value, '/')")
    && str_contains($functions, "str_starts_with(\$value, '//')"), 'PRG return path is restricted to local module routes');

$themePaths = [
    'themes/asu-blue/assets/css/directories.css',
    'themes/asu-light-blue/assets/css/directories.css',
    'themes/asu-evgeniya-rostova/assets/css/directories.css',
];
$featureCss = [];
foreach ($themePaths as $path) {
    $css = mpv1_contents($root, $path);
    $marker = strpos($css, '/* Military Positions Directory v1 */');
    mpv1_check($marker !== false, "managed directory styles exist: {$path}");
    $featureCss[] = $marker === false ? '' : substr($css, $marker);
}
mpv1_check(count(array_unique($featureCss)) === 1, 'managed directory CSS is symmetric across three themes');
mpv1_check(!preg_match('/#[0-9a-f]{3,8}|rgba?\(/i', $featureCss[0] ?? ''), 'managed styles use theme variables without hardcoded colors');

$gitDirectory = $root . DIRECTORY_SEPARATOR . '.git';
if (is_dir($gitDirectory) && function_exists('exec')) {
    $command = 'git -C ' . escapeshellarg($root)
        . ' diff --name-only 9ae05b9928903cc483ce415d7378b546e419264c...HEAD 2>&1';
    $output = [];
    $code = 0;
    exec($command, $output, $code);
    mpv1_check($code === 0, 'git changed path inventory succeeds');
    if ($code === 0 && $output !== []) {
        mpv1_check(count($output) <= 38, 'changed path count does not exceed 38');
        $unexpected = array_values(array_diff($output, $allowlist));
        mpv1_check($unexpected === [], 'all committed changes are inside approved allowlist');
        if ($unexpected !== []) {
            echo 'UNEXPECTED_PATHS=' . implode(',', $unexpected) . "\n";
        }
    }

    $correctiveCommand = 'git -C ' . escapeshellarg($root)
        . ' diff-tree --no-commit-id --name-only -r HEAD 2>&1';
    $correctiveOutput = [];
    $correctiveCode = 0;
    exec($correctiveCommand, $correctiveOutput, $correctiveCode);
    mpv1_check($correctiveCode === 0, 'corrective commit path inventory succeeds');
    if ($correctiveCode === 0) {
        $actualCorrectivePaths = array_values(array_filter($correctiveOutput, static fn(string $path): bool => $path !== ''));
        $expectedCorrectivePaths = $correctiveAllowlist;
        sort($actualCorrectivePaths);
        sort($expectedCorrectivePaths);
        mpv1_check(count($actualCorrectivePaths) === 12, 'corrective commit changes exactly 12 paths');
        mpv1_check($actualCorrectivePaths === $expectedCorrectivePaths, 'corrective commit matches exact 12-path allowlist');
        $unexpectedCorrectivePaths = array_values(array_diff($actualCorrectivePaths, $expectedCorrectivePaths));
        if ($unexpectedCorrectivePaths !== []) {
            echo 'UNEXPECTED_CORRECTIVE_PATHS=' . implode(',', $unexpectedCorrectivePaths) . "\n";
        }
    }
}

$localFile = $runtimeRoot . '/config/local.php';
if (is_file($localFile)) {
    $app = require $runtimeRoot . '/config/app.php';
    $local = require $localFile;
    $config = array_replace_recursive($app, $local);
    $db = $config['database'];
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']),
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $migrationStmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration=:migration');
    $migrationStmt->execute(['migration' => '014_military_positions_directory_v1.sql']);
    mpv1_check((int) $migrationStmt->fetchColumn() === 1, 'migration 014 registered');
    mpv1_check((int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE code LIKE 'directories.military_positions.%'")->fetchColumn() === 4, 'four directory permissions installed');
    mpv1_check((int) $pdo->query("SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE p.code LIKE 'directories.military_positions.%'")->fetchColumn() === 0, 'no non-owner permission grants installed');
    mpv1_check((int) $pdo->query("SELECT COUNT(*) FROM military_position_catalog_versions WHERE status='published'")->fetchColumn() === 1, 'one current published version');
    $draftId = (int) $pdo->query("SELECT id FROM military_position_catalog_versions WHERE status='draft' AND catalog_kind='canonical' LIMIT 1")->fetchColumn();
    mpv1_check($draftId > 0, 'initial canonical draft exists');
    if ($draftId > 0) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM military_position_types WHERE catalog_version_id=:id');
        $countStmt->execute(['id' => $draftId]);
        mpv1_check((int) $countStmt->fetchColumn() === 24, 'database canonical draft has 24 entries');
        $combinedStmt = $pdo->prepare('SELECT COUNT(*) FROM military_position_types WHERE catalog_version_id=:id AND is_combined=1');
        $combinedStmt->execute(['id' => $draftId]);
        mpv1_check((int) $combinedStmt->fetchColumn() === 9, 'database canonical draft has nine combined entries');
    }
    mpv1_check((int) $pdo->query(
        'SELECT COUNT(*) FROM staffing_slots s JOIN staffing_versions v ON v.id=s.staffing_version_id '
        . 'JOIN military_position_types t ON t.id=s.position_type_id '
        . 'WHERE s.position_catalog_version_id<>t.catalog_version_id OR s.position_catalog_version_id<>v.position_catalog_version_id'
    )->fetchColumn() === 0, 'Staffing catalog pins remain consistent');
} else {
    echo "DATABASE_CHECKS=SKIP (config/local.php отсутствует)\n";
}

if ($failures !== []) {
    fwrite(STDERR, sprintf("MILITARY_POSITIONS_DIRECTORY_V1_STATIC=FAIL (%d failures, %d passes)\n", count($failures), $passes));
    exit(1);
}
echo "MILITARY_POSITIONS_DIRECTORY_V1_STATIC=PASS\n";
echo "PASS_COUNT={$passes}\n";
