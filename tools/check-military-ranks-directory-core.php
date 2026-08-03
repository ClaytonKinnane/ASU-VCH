<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$app = require $root . '/config/app.php';

$localCandidates = [];
$explicitLocalFile = getenv('ASU_VCH_LOCAL_CONFIG');
if (is_string($explicitLocalFile) && trim($explicitLocalFile) !== '') {
    $localCandidates[] = trim($explicitLocalFile);
}
$localCandidates[] = $root . '/config/local.php';
$localCandidates[] = 'C:/OSPanel/home/asu-vch.local/config/local.php';

$localFile = null;
foreach (array_unique($localCandidates) as $candidate) {
    if (is_file($candidate)) {
        $localFile = $candidate;
        break;
    }
}

if (!is_string($localFile)) {
    fwrite(
        STDERR,
        "Не найден config/local.php. Проверены:\n- "
        . implode("\n- ", array_unique($localCandidates))
        . "\n"
    );
    exit(1);
}

$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];

require_once $root . '/app/Directory/MilitaryRankCatalogRepository.php';
require_once $root . '/app/Theme/ThemeRegistry.php';

function military_ranks_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $migration = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $migration->execute(['migration' => '012_military_ranks_directory_v2.sql']);
    military_ranks_check((int) $migration->fetchColumn() === 1, 'Миграция 012 не зарегистрирована.');

    $versions = $pdo->query(
        "SELECT id, code, lifecycle_status, is_current, valid_from, valid_to, verified_at, published_at "
        . "FROM military_rank_catalog_versions ORDER BY valid_from, id"
    )->fetchAll();
    military_ranks_check(count($versions) >= 2, 'Ожидались версии v1 и v2.');

    $byCode = [];
    foreach ($versions as $version) {
        $byCode[(string) $version['code']] = $version;
    }
    $v1 = $byCode['rf-military-ranks-2026-07-27'] ?? null;
    $v2 = $byCode['rf-military-ranks-staffing-scopes-v2'] ?? null;
    military_ranks_check(is_array($v1) && is_array($v2), 'Не найдены v1/v2.');
    military_ranks_check($v1['lifecycle_status'] === 'superseded' && (int) $v1['is_current'] === 0 && $v1['valid_to'] === '2026-08-02', 'Lifecycle v1 неверен.');
    military_ranks_check($v2['lifecycle_status'] === 'published' && (int) $v2['is_current'] === 1, 'Lifecycle v2 неверен.');
    military_ranks_check($v2['valid_from'] === '2026-08-03' && $v2['verified_at'] === '2026-08-02' && $v2['published_at'] !== null, 'Даты v2 неверны.');
    $v1Id = (int) $v1['id'];
    $v2Id = (int) $v2['id'];

    $count = static function (PDO $pdo, string $table, int $versionId): int {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE catalog_version_id = :version_id");
        $stmt->execute(['version_id' => $versionId]);
        return (int) $stmt->fetchColumn();
    };
    military_ranks_check($count($pdo, 'military_personnel_compositions', $v1Id) === 6, 'v1 должна содержать 6 составов.');
    military_ranks_check($count($pdo, 'military_rank_levels', $v1Id) === 20, 'v1 должна содержать 20 званий.');
    military_ranks_check($count($pdo, 'military_personnel_composition_semantics', $v1Id) === 0, 'v1 не должна иметь Staffing semantics.');
    military_ranks_check($count($pdo, 'military_personnel_compositions', $v2Id) === 8, 'v2 должна содержать 8 составов.');
    military_ranks_check($count($pdo, 'military_personnel_composition_semantics', $v2Id) === 8, 'v2 должна содержать 8 semantics.');
    military_ranks_check($count($pdo, 'military_rank_levels', $v2Id) === 20, 'v2 должна содержать 20 званий.');
    military_ranks_check($count($pdo, 'military_rank_catalog_version_sources', $v2Id) === 2, 'v2 должна содержать 2 version sources.');
    military_ranks_check($count($pdo, 'military_personnel_composition_sources', $v2Id) === 8, 'v2 должна содержать 8 composition sources.');

    $selectableStmt = $pdo->prepare(
        'SELECT c.code FROM military_personnel_composition_semantics s '
        . 'JOIN military_personnel_compositions c ON c.id = s.composition_id AND c.catalog_version_id = s.catalog_version_id '
        . 'WHERE s.catalog_version_id = :version_id AND s.is_staffing_selectable = 1 ORDER BY c.code'
    );
    $selectableStmt->execute(['version_id' => $v2Id]);
    military_ranks_check($selectableStmt->fetchAll(PDO::FETCH_COLUMN) === [
        'officers', 'sergeants-and-starshinas', 'soldiers-and-sailors', 'warrant-officers',
    ], 'Набор Staffing-selectable categories неверен.');

    $compositionIds = [];
    $stmt = $pdo->prepare('SELECT id, code FROM military_personnel_compositions WHERE catalog_version_id = :version_id');
    $stmt->execute(['version_id' => $v2Id]);
    foreach ($stmt->fetchAll() as $row) {
        $compositionIds[(string) $row['code']] = (int) $row['id'];
    }
    $rankIds = [];
    $stmt = $pdo->prepare('SELECT id, code FROM military_rank_levels WHERE catalog_version_id = :version_id');
    $stmt->execute(['version_id' => $v2Id]);
    foreach ($stmt->fetchAll() as $row) {
        $rankIds[(string) $row['code']] = (int) $row['id'];
    }

    $service = new MilitaryRankCompatibilityService($pdo);
    military_ranks_check($service->check($v2Id, $compositionIds['soldiers-and-sailors'], $rankIds['private']) === MilitaryRankCompatibilityService::COMPATIBLE, 'soldiers/private должны быть compatible.');
    military_ranks_check($service->check($v2Id, $compositionIds['soldiers-and-sailors'], $rankIds['sergeant']) === MilitaryRankCompatibilityService::INCOMPATIBLE, 'soldiers/sergeant должны быть incompatible.');
    military_ranks_check($service->check($v2Id, $compositionIds['officers'], $rankIds['colonel']) === MilitaryRankCompatibilityService::COMPATIBLE, 'officers/colonel должны быть compatible по ancestry.');
    $v1Composition = (int) $pdo->query("SELECT id FROM military_personnel_compositions WHERE catalog_version_id = {$v1Id} ORDER BY id LIMIT 1")->fetchColumn();
    $v1Rank = (int) $pdo->query("SELECT id FROM military_rank_levels WHERE catalog_version_id = {$v1Id} ORDER BY id LIMIT 1")->fetchColumn();
    military_ranks_check($service->check($v1Id, $v1Composition, $v1Rank) === MilitaryRankCompatibilityService::COMPOSITION_NOT_SELECTABLE, 'v1 не должна иметь Staffing eligibility.');

    $repository = new MilitaryRankCatalogRepository($pdo);
    military_ranks_check($repository->currentVersion()['id'] === $v2Id, 'Repository вернул не v2.');
    military_ranks_check(count($repository->visibleVersions()) >= 2, 'Repository не показывает историю.');
    military_ranks_check(count($repository->compositions($v2Id)) === 8, 'Repository не вернул 8 составов v2.');
    military_ranks_check($repository->search('', '', $v2Id)['total'] === 20, 'Repository не вернул 20 званий v2.');
    military_ranks_check($repository->search('', 'soldiers-and-sailors', $v2Id)['total'] === 2, 'Фильтр soldiers неверен.');
    military_ranks_check($repository->search('', 'sergeants-and-starshinas', $v2Id)['total'] === 4, 'Фильтр sergeants неверен.');

    $permissionCount = (int) $pdo->query('SELECT COUNT(*) FROM permissions WHERE is_system = 1')->fetchColumn();
    military_ranks_check($permissionCount === 25, "Ожидалось 25 системных разрешений, найдено {$permissionCount}.");

    $themes = require $root . '/config/themes.php';
    $registry = new ThemeRegistry($root, $root . '/config/themes.php');
    foreach (['asu-blue', 'asu-light-blue', 'asu-evgeniya-rostova'] as $theme) {
        military_ranks_check(in_array('css/military-ranks-v2.css', $themes['themes'][$theme]['required_assets'] ?? [], true), "Тема {$theme} не регистрирует CSS v2.");
        military_ranks_check($registry->assetUrl($theme, 'css/military-ranks-v2.css') === "/themes/{$theme}/assets/css/military-ranks-v2.css", "CSS v2 темы {$theme} недоступен.");
    }

    echo "MILITARY RANKS DIRECTORY CHECK PASSED\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'MILITARY RANKS DIRECTORY CHECK FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
