<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$app = require $root . '/config/app.php';
$localFile = $root . '/config/local.php';
if (!is_file($localFile)) {
    fwrite(STDERR, "Не найден config/local.php.\n");
    exit(1);
}

require_once $root . '/app/Directory/MilitaryRankCatalogRepository.php';

$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];

function military_ranks_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $migration = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
    $migration->execute(['migration' => '007_military_ranks_directory.sql']);
    military_ranks_check((int) $migration->fetchColumn() === 1, 'Миграция 007 не зарегистрирована.');
    echo "OK migration 007\n";

    $tableStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name'
    );
    $tables = [
        'legal_sources',
        'military_rank_catalog_versions',
        'military_rank_catalog_version_sources',
        'military_personnel_compositions',
        'military_rank_levels',
    ];
    foreach ($tables as $tableName) {
        $tableStmt->execute(['schema_name' => $db['name'], 'table_name' => $tableName]);
        military_ranks_check((int) $tableStmt->fetchColumn() === 1, "Не найдена таблица {$tableName}.");
    }
    echo 'OK tables: ' . count($tables) . "\n";

    $versionRows = $pdo->query(
        "SELECT id, code, verified_at FROM military_rank_catalog_versions WHERE is_current = 1 ORDER BY id"
    )->fetchAll();
    military_ranks_check(count($versionRows) === 1, 'Ожидалась ровно одна текущая версия каталога.');
    military_ranks_check(
        $versionRows[0]['code'] === 'rf-military-ranks-2026-07-27',
        'Код текущей версии не совпадает.'
    );
    military_ranks_check($versionRows[0]['verified_at'] === '2026-07-27', 'Дата проверки версии не совпадает.');
    $versionId = (int) $versionRows[0]['id'];
    echo "OK current catalog version\n";

    $sourceCount = $pdo->prepare(
        'SELECT COUNT(*) FROM military_rank_catalog_version_sources WHERE catalog_version_id = :version_id'
    );
    $sourceCount->execute(['version_id' => $versionId]);
    military_ranks_check((int) $sourceCount->fetchColumn() === 2, 'Ожидалось два нормативных источника.');

    $sourceCodes = $pdo->prepare(
        'SELECT s.code, vs.source_role, vs.sort_order '
        . 'FROM military_rank_catalog_version_sources vs '
        . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
        . 'WHERE vs.catalog_version_id = :version_id ORDER BY vs.sort_order'
    );
    $sourceCodes->execute(['version_id' => $versionId]);
    military_ranks_check($sourceCodes->fetchAll() === [
        ['code' => 'federal-law-53-fz-article-46', 'source_role' => 'primary-list', 'sort_order' => 1],
        ['code' => 'presidential-decree-1237-article-20', 'source_role' => 'equivalence-and-order', 'sort_order' => 2],
    ], 'Нормативные источники или их порядок не совпадают.');
    echo "OK legal sources: 2\n";

    $compositionCount = $pdo->prepare(
        'SELECT COUNT(*) FROM military_personnel_compositions WHERE catalog_version_id = :version_id'
    );
    $compositionCount->execute(['version_id' => $versionId]);
    military_ranks_check((int) $compositionCount->fetchColumn() === 6, 'Ожидалось шесть составов.');
    echo "OK compositions: 6\n";

    $expected = [
        1 => ['рядовой', 'матрос'],
        2 => ['ефрейтор', 'старший матрос'],
        3 => ['младший сержант', 'старшина 2 статьи'],
        4 => ['сержант', 'старшина 1 статьи'],
        5 => ['старший сержант', 'главный старшина'],
        6 => ['старшина', 'главный корабельный старшина'],
        7 => ['прапорщик', 'мичман'],
        8 => ['старший прапорщик', 'старший мичман'],
        9 => ['младший лейтенант', 'младший лейтенант'],
        10 => ['лейтенант', 'лейтенант'],
        11 => ['старший лейтенант', 'старший лейтенант'],
        12 => ['капитан', 'капитан-лейтенант'],
        13 => ['майор', 'капитан 3 ранга'],
        14 => ['подполковник', 'капитан 2 ранга'],
        15 => ['полковник', 'капитан 1 ранга'],
        16 => ['генерал-майор', 'контр-адмирал'],
        17 => ['генерал-лейтенант', 'вице-адмирал'],
        18 => ['генерал-полковник', 'адмирал'],
        19 => ['генерал армии', 'адмирал флота'],
        20 => ['Маршал Российской Федерации', null],
    ];

    $rankStmt = $pdo->prepare(
        'SELECT sort_order, troop_name, naval_name FROM military_rank_levels '
        . 'WHERE catalog_version_id = :version_id ORDER BY sort_order'
    );
    $rankStmt->execute(['version_id' => $versionId]);
    $rankRows = $rankStmt->fetchAll();
    military_ranks_check(count($rankRows) === 20, 'Ожидалось двадцать уровней воинских званий.');
    foreach ($rankRows as $index => $row) {
        $order = $index + 1;
        military_ranks_check((int) $row['sort_order'] === $order, "Нарушен порядок в строке {$order}.");
        military_ranks_check(
            $row['troop_name'] === $expected[$order][0] && $row['naval_name'] === $expected[$order][1],
            "Не совпадает нормативная пара в строке {$order}."
        );
    }
    echo "OK normative rank pairs: 20\n";

    $repository = new MilitaryRankCatalogRepository($pdo);
    $version = $repository->currentVersion();
    military_ranks_check($version['id'] === $versionId, 'Repository вернул другую текущую версию.');
    military_ranks_check(count($repository->sources()) === 2, 'Repository не вернул два источника.');
    military_ranks_check(count($repository->compositions()) === 6, 'Repository не вернул шесть составов.');
    military_ranks_check($repository->search()['total'] === 20, 'Repository не вернул двадцать уровней.');
    military_ranks_check($repository->search('', 'enlisted')['total'] === 6, 'Фильтр enlisted не вернул 6 строк.');
    military_ranks_check($repository->search('', 'warrant-officers')['total'] === 2, 'Фильтр warrant-officers не вернул 2 строки.');
    military_ranks_check($repository->search('', 'officers')['total'] === 12, 'Родительский фильтр officers не вернул 12 строк.');
    military_ranks_check($repository->search('', 'junior-officers')['total'] === 4, 'Фильтр junior-officers не вернул 4 строки.');
    military_ranks_check($repository->search('', 'senior-officers')['total'] === 3, 'Фильтр senior-officers не вернул 3 строки.');
    military_ranks_check($repository->search('', 'higher-officers')['total'] === 5, 'Фильтр higher-officers не вернул 5 строк.');

    $sergeant = $repository->search('старшина 1 статьи');
    military_ranks_check(
        $sergeant['total'] === 1 && $sergeant['items'][0]['troop_name'] === 'сержант',
        'Поиск нормативной пары сержанта работает неверно.'
    );
    military_ranks_check($repository->search('адмирал')['total'] === 4, 'Поиск по корабельным званиям работает неверно.');
    echo "OK repository search and filters\n";

    $permissionCount = (int) $pdo->query('SELECT COUNT(*) FROM permissions WHERE is_system = 1')->fetchColumn();
    military_ranks_check($permissionCount === 19, "Ожидалось 19 системных разрешений, найдено {$permissionCount}.");
    echo "OK system permissions: 19\n";

    $themes = require $root . '/config/themes.php';
    foreach (['asu-blue', 'asu-light-blue'] as $themeSlug) {
        $requiredAssets = $themes['themes'][$themeSlug]['required_assets'] ?? [];
        military_ranks_check(
            in_array('css/directories.css', $requiredAssets, true),
            "Тема {$themeSlug} не регистрирует css/directories.css."
        );
        military_ranks_check(
            is_file($root . '/themes/' . $themeSlug . '/assets/css/directories.css'),
            "Не найден CSS справочников для темы {$themeSlug}."
        );
    }
    echo "OK theme assets: 2\n";

    echo "MILITARY RANKS DIRECTORY CHECK PASSED\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'MILITARY RANKS DIRECTORY CHECK FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
