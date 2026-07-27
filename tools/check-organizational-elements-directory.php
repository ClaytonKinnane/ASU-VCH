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

require_once $root . '/app/Directory/OrganizationalElementCatalogRepository.php';
require_once $root . '/app/Theme/ThemeRegistry.php';

$local = require $localFile;
$config = array_replace_recursive($app, $local);
$db = $config['database'];

function organizational_elements_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $bootstrapContents = file_get_contents($root . '/app/bootstrap.php');
    organizational_elements_check(
        is_string($bootstrapContents),
        'Не удалось прочитать app/bootstrap.php.'
    );
    organizational_elements_check(
        str_contains(
            $bootstrapContents,
            "require_once __DIR__ . '/Directory/OrganizationalElementCatalogRepository.php';"
        ),
        'Bootstrap не подключает OrganizationalElementCatalogRepository.'
    );
    organizational_elements_check(
        str_contains(
            $bootstrapContents,
            'function organizational_element_catalog_repository(): OrganizationalElementCatalogRepository'
        ),
        'Bootstrap factory организационного справочника не найдена.'
    );

    $pageContents = file_get_contents(
        $root . '/public/admin/directories/organizational-elements.php'
    );
    organizational_elements_check(
        is_string($pageContents),
        'Не удалось прочитать страницу организационного справочника.'
    );
    organizational_elements_check(
        str_contains(
            $pageContents,
            '$repository = organizational_element_catalog_repository();'
        ),
        'Страница не использует bootstrap factory.'
    );
    organizational_elements_check(
        !str_contains(
            $pageContents,
            'new OrganizationalElementCatalogRepository'
        ),
        'На странице найден прямой конструктор репозитория.'
    );
    organizational_elements_check(
        !str_contains(
            $pageContents,
            "/app/Directory/OrganizationalElementCatalogRepository.php"
        ),
        'На странице найдено прямое подключение репозитория.'
    );
    echo "OK bootstrap factory\n";

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
    $migration->execute(['migration' => '008_organizational_element_types_directory.sql']);
    organizational_elements_check((int) $migration->fetchColumn() === 1, 'Миграция 008 не зарегистрирована.');
    echo "OK migration 008\n";

    $tables = [
        'organizational_element_catalog_versions',
        'organizational_element_catalog_version_sources',
        'organizational_element_classes',
        'organizational_element_types',
        'organizational_element_type_classes',
        'organizational_element_type_aliases',
        'organizational_element_type_sources',
    ];
    $tableStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name'
    );
    foreach ($tables as $tableName) {
        $tableStmt->execute(['schema_name' => $db['name'], 'table_name' => $tableName]);
        organizational_elements_check((int) $tableStmt->fetchColumn() === 1, "Не найдена таблица {$tableName}.");
    }
    echo 'OK tables: ' . count($tables) . "\n";

    $requiredColumns = [
        'organizational_element_catalog_versions' => ['current_guard'],
        'organizational_element_type_classes' => ['primary_guard'],
        'organizational_element_types' => ['short_name', 'applicability_note'],
    ];
    $columnStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns '
        . 'WHERE table_schema = :schema_name AND table_name = :table_name AND column_name = :column_name'
    );
    foreach ($requiredColumns as $tableName => $columns) {
        foreach ($columns as $columnName) {
            $columnStmt->execute([
                'schema_name' => $db['name'],
                'table_name' => $tableName,
                'column_name' => $columnName,
            ]);
            organizational_elements_check(
                (int) $columnStmt->fetchColumn() === 1,
                "Не найдена колонка {$tableName}.{$columnName}."
            );
        }
    }

    $constraintNames = [
        'uq_org_element_catalog_current_guard',
        'uq_org_element_type_primary_guard',
        'fk_org_element_type_source_legal_version',
        'fk_org_element_alias_source_version',
        'chk_org_element_type_source_role',
        'chk_org_element_alias_type',
    ];
    $constraintStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.table_constraints '
        . 'WHERE constraint_schema = :schema_name AND constraint_name = :constraint_name'
    );
    foreach ($constraintNames as $constraintName) {
        $constraintStmt->execute(['schema_name' => $db['name'], 'constraint_name' => $constraintName]);
        organizational_elements_check(
            (int) $constraintStmt->fetchColumn() === 1,
            "Не найдено ограничение {$constraintName}."
        );
    }
    echo "OK schema constraints\n";

    $versionRows = $pdo->query(
        'SELECT id, code, valid_to, verified_at FROM organizational_element_catalog_versions '
        . 'WHERE is_current = 1 ORDER BY id'
    )->fetchAll();
    organizational_elements_check(count($versionRows) === 1, 'Ожидалась ровно одна текущая версия каталога.');
    organizational_elements_check(
        $versionRows[0]['code'] === 'rf-organizational-elements-2026-07-27',
        'Код текущей версии не совпадает.'
    );
    organizational_elements_check($versionRows[0]['valid_to'] === null, 'Текущая версия должна иметь valid_to = NULL.');
    organizational_elements_check($versionRows[0]['verified_at'] === '2026-07-27', 'Дата проверки версии не совпадает.');
    $versionId = (int) $versionRows[0]['id'];
    echo "OK current catalog version\n";

    $sourceStmt = $pdo->prepare(
        'SELECT s.code, vs.source_role, vs.sort_order '
        . 'FROM organizational_element_catalog_version_sources vs '
        . 'JOIN legal_sources s ON s.id = vs.legal_source_id '
        . 'WHERE vs.catalog_version_id = :version_id ORDER BY vs.sort_order'
    );
    $sourceStmt->execute(['version_id' => $versionId]);
    $sourceRows = $sourceStmt->fetchAll();
    $expectedSources = [
        ['federal-law-61-fz-article-11', 'general-composition', 1],
        ['presidential-decree-1237-article-11', 'classification', 2],
        ['presidential-decree-1495-internal-service-charter', 'internal-service', 3],
        ['presidential-decree-511-ship-charter', 'naval-organization', 4],
    ];
    organizational_elements_check(count($sourceRows) === 4, 'Ожидалось четыре источника версии.');
    foreach ($expectedSources as $index => [$code, $role, $order]) {
        $actual = $sourceRows[$index] ?? null;
        organizational_elements_check(
            is_array($actual)
            && $actual['code'] === $code
            && $actual['source_role'] === $role
            && (int) $actual['sort_order'] === $order,
            'Источники версии или их порядок не совпадают.'
        );
    }
    echo "OK legal sources: 4\n";

    $expectedClasses = [
        1 => ['military-command-body', 'Орган военного управления'],
        2 => ['association', 'Объединение'],
        3 => ['formation', 'Соединение'],
        4 => ['military-unit', 'Воинская часть'],
        5 => ['organization', 'Организация'],
        6 => ['subdivision', 'Подразделение'],
    ];
    $classStmt = $pdo->prepare(
        'SELECT code, name, sort_order FROM organizational_element_classes '
        . 'WHERE catalog_version_id = :version_id ORDER BY sort_order'
    );
    $classStmt->execute(['version_id' => $versionId]);
    $classRows = $classStmt->fetchAll();
    organizational_elements_check(count($classRows) === 6, 'Ожидалось шесть организационных классов.');
    foreach ($classRows as $index => $row) {
        $order = $index + 1;
        organizational_elements_check(
            (int) $row['sort_order'] === $order
            && $row['code'] === $expectedClasses[$order][0]
            && $row['name'] === $expectedClasses[$order][1],
            "Не совпадает организационный класс {$order}."
        );
    }
    echo "OK organizational classes: 6\n";

    $expectedTypes = [
        1 => ['administration', 'управление', null],
        2 => ['headquarters', 'штаб', null],
        3 => ['service', 'служба', null],
        4 => ['direction', 'направление', null],
        5 => ['department', 'отдел', null],
        6 => ['army', 'армия', null],
        7 => ['corps', 'корпус', null],
        8 => ['division', 'дивизия', null],
        9 => ['brigade', 'бригада', null],
        10 => ['regiment', 'полк', null],
        11 => ['arsenal', 'арсенал', null],
        12 => ['test-center', 'испытательный центр', null],
        13 => ['storage-supply-base', 'база хранения и снабжения', null],
        14 => ['enterprise', 'предприятие', null],
        15 => ['institution', 'учреждение', null],
        16 => ['military-educational-organization', 'военная образовательная организация', null],
        17 => ['battalion', 'батальон', null],
        18 => ['divizion', 'дивизион', null],
        19 => ['company', 'рота', null],
        20 => ['battery', 'батарея', null],
        21 => ['platoon', 'взвод', null],
        22 => ['group', 'группа', null],
        23 => ['section', 'отделение', null],
        24 => ['team', 'команда', null],
        25 => ['raschet', 'расчёт', null],
        26 => ['crew', 'экипаж', null],
        27 => ['ship', 'корабль', null],
        28 => ['combat-unit', 'боевая часть', 'БЧ'],
    ];
    $typeStmt = $pdo->prepare(
        'SELECT code, name, short_name, sort_order FROM organizational_element_types '
        . 'WHERE catalog_version_id = :version_id ORDER BY sort_order'
    );
    $typeStmt->execute(['version_id' => $versionId]);
    $typeRows = $typeStmt->fetchAll();
    organizational_elements_check(count($typeRows) === 28, 'Ожидалось двадцать восемь типов.');
    foreach ($typeRows as $index => $row) {
        $order = $index + 1;
        organizational_elements_check(
            (int) $row['sort_order'] === $order
            && $row['code'] === $expectedTypes[$order][0]
            && $row['name'] === $expectedTypes[$order][1]
            && $row['short_name'] === $expectedTypes[$order][2],
            "Не совпадает тип организационного элемента {$order}."
        );
    }
    $forbiddenTypes = $pdo->prepare(
        'SELECT COUNT(*) FROM organizational_element_types '
        . 'WHERE catalog_version_id = :version_id AND code IN (\'combat-post\', \'shift\')'
    );
    $forbiddenTypes->execute(['version_id' => $versionId]);
    organizational_elements_check((int) $forbiddenTypes->fetchColumn() === 0, 'В каталог попали исключённые типы.');
    echo "OK organizational element types: 28\n";

    $linkCount = $pdo->prepare(
        'SELECT COUNT(*) FROM organizational_element_type_classes WHERE catalog_version_id = :version_id'
    );
    $linkCount->execute(['version_id' => $versionId]);
    organizational_elements_check((int) $linkCount->fetchColumn() === 32, 'Ожидалось 32 связи тип–класс.');

    $primaryCheck = $pdo->prepare(
        'SELECT t.id, SUM(tc.is_primary = 1) AS primary_count, COUNT(tc.class_id) AS class_count '
        . 'FROM organizational_element_types t '
        . 'LEFT JOIN organizational_element_type_classes tc '
        . 'ON tc.type_id = t.id AND tc.catalog_version_id = t.catalog_version_id '
        . 'WHERE t.catalog_version_id = :version_id GROUP BY t.id'
    );
    $primaryCheck->execute(['version_id' => $versionId]);
    foreach ($primaryCheck->fetchAll() as $row) {
        organizational_elements_check((int) $row['class_count'] >= 1, 'У типа отсутствует организационный класс.');
        organizational_elements_check((int) $row['primary_count'] === 1, 'У типа должен быть ровно один основной класс.');
    }

    $sourceCoverage = $pdo->prepare(
        'SELECT t.id, COUNT(ts.legal_source_id) AS source_count '
        . 'FROM organizational_element_types t '
        . 'LEFT JOIN organizational_element_type_sources ts '
        . 'ON ts.type_id = t.id AND ts.catalog_version_id = t.catalog_version_id '
        . 'WHERE t.catalog_version_id = :version_id GROUP BY t.id'
    );
    $sourceCoverage->execute(['version_id' => $versionId]);
    foreach ($sourceCoverage->fetchAll() as $row) {
        organizational_elements_check((int) $row['source_count'] >= 1, 'У типа отсутствует официальный источник.');
    }
    echo "OK type-class links: 32\n";

    $classCounts = [
        'military-command-body' => 2,
        'association' => 1,
        'formation' => 3,
        'military-unit' => 7,
        'organization' => 3,
        'subdivision' => 16,
    ];
    $classCountStmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT tc.type_id) '
        . 'FROM organizational_element_type_classes tc '
        . 'JOIN organizational_element_classes c '
        . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
        . 'WHERE tc.catalog_version_id = :version_id AND c.code = :class_code'
    );
    foreach ($classCounts as $code => $count) {
        $classCountStmt->execute(['version_id' => $versionId, 'class_code' => $code]);
        organizational_elements_check(
            (int) $classCountStmt->fetchColumn() === $count,
            "Фильтр класса {$code} должен возвращать {$count} типов."
        );
    }
    echo "OK class distribution\n";

    $scopeRows = $pdo->prepare(
        'SELECT SUM(has_subdivision = 0) AS non_subdivision_only, '
        . 'SUM(has_subdivision = 1 AND has_other = 0) AS subdivision_only, '
        . 'SUM(has_subdivision = 1 AND has_other = 1) AS mixed '
        . 'FROM ('
        . 'SELECT t.id, '
        . 'MAX(c.code = \'subdivision\') AS has_subdivision, '
        . 'MAX(c.code <> \'subdivision\') AS has_other '
        . 'FROM organizational_element_types t '
        . 'JOIN organizational_element_type_classes tc '
        . 'ON tc.type_id = t.id AND tc.catalog_version_id = t.catalog_version_id '
        . 'JOIN organizational_element_classes c '
        . 'ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
        . 'WHERE t.catalog_version_id = :version_id GROUP BY t.id'
        . ') scope_counts'
    );
    $scopeRows->execute(['version_id' => $versionId]);
    $scopeCounts = $scopeRows->fetch();
    organizational_elements_check((int) $scopeCounts['non_subdivision_only'] === 12, 'Ожидалось 12 типов, не являющихся подразделениями.');
    organizational_elements_check((int) $scopeCounts['subdivision_only'] === 12, 'Ожидалось 12 типов только-подразделений.');
    organizational_elements_check((int) $scopeCounts['mixed'] === 4, 'Ожидалось 4 контекстно-зависимых типа.');
    echo "OK organizational scopes: 12/12/4\n";

    $repository = new OrganizationalElementCatalogRepository($pdo);
    $repoVersion = $repository->currentVersion();
    organizational_elements_check($repoVersion['id'] === $versionId, 'Repository вернул другую текущую версию.');
    organizational_elements_check(count($repository->versionSources($versionId)) === 4, 'Repository не вернул четыре источника.');
    organizational_elements_check(count($repository->classes($versionId)) === 6, 'Repository не вернул шесть классов.');
    organizational_elements_check($repository->searchTypes($versionId)['total'] === 28, 'Repository не вернул 28 типов.');
    organizational_elements_check($repository->searchTypes($versionId, 'батальон')['total'] === 1, 'Поиск батальона работает неверно.');
    $combatUnit = $repository->searchTypes($versionId, 'БЧ');
    organizational_elements_check(
        $combatUnit['total'] === 1 && $combatUnit['items'][0]['code'] === 'combat-unit',
        'Поиск по официальному сокращению БЧ работает неверно.'
    );
    organizational_elements_check($repository->searchTypes($versionId, 'боев')['total'] === 1, 'Поиск по фрагменту «боев» работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', 'association')['total'] === 1, 'Фильтр association работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', 'formation')['total'] === 3, 'Фильтр formation работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', 'military-unit')['total'] === 7, 'Фильтр military-unit работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', 'organization')['total'] === 3, 'Фильтр organization работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', 'subdivision')['total'] === 16, 'Фильтр subdivision работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', '', 'non_subdivision_only')['total'] === 12, 'Фильтр non_subdivision_only работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', '', 'subdivision_only')['total'] === 12, 'Фильтр subdivision_only работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, '', '', 'mixed')['total'] === 4, 'Фильтр mixed работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, 'батальон', 'military-unit')['total'] === 1, 'Комбинация батальон + military-unit работает неверно.');
    organizational_elements_check($repository->searchTypes($versionId, 'батальон', 'organization')['total'] === 0, 'Комбинация батальон + organization работает неверно.');

    $all = $repository->searchTypes($versionId);
    $ids = array_map(static fn(array $item): int => (int) $item['id'], $all['items']);
    organizational_elements_check(count($repository->classesForTypes($versionId, $ids)) === 28, 'Repository не агрегировал классы всех типов.');
    organizational_elements_check(count($repository->sourcesForTypes($versionId, $ids)) === 28, 'Repository не агрегировал источники всех типов.');
    organizational_elements_check($repository->aliasesForTypes($versionId, $ids) === [], 'Первоначальный каталог aliases должен быть пустым.');
    echo "OK repository search and filters\n";

    $permissionCount = (int) $pdo->query('SELECT COUNT(*) FROM permissions WHERE is_system = 1')->fetchColumn();
    organizational_elements_check($permissionCount === 19, "Ожидалось 19 системных разрешений, найдено {$permissionCount}.");
    echo "OK system permissions: 19\n";

    $themes = require $root . '/config/themes.php';
    $themeRegistry = new ThemeRegistry($root, $root . '/config/themes.php');
    foreach (['asu-blue', 'asu-light-blue'] as $themeSlug) {
        $requiredAssets = $themes['themes'][$themeSlug]['required_assets'] ?? [];
        organizational_elements_check(
            in_array('css/directories.css', $requiredAssets, true),
            "Тема {$themeSlug} не регистрирует css/directories.css."
        );
        organizational_elements_check(
            is_file($root . '/themes/' . $themeSlug . '/assets/css/directories.css'),
            "Не найден исходный CSS справочников для темы {$themeSlug}."
        );
        organizational_elements_check(
            $themeRegistry->assetUrl($themeSlug, 'css/directories.css')
                === '/themes/' . $themeSlug . '/assets/css/directories.css',
            "Опубликованный CSS справочников недоступен для темы {$themeSlug}."
        );
    }
    echo "OK theme assets: 2\n";

    $migrationFile = $root . '/database/migrations/008_organizational_element_types_directory.sql';
    $migrationSql = file_get_contents($migrationFile);
    organizational_elements_check(is_string($migrationSql), 'Не удалось прочитать migration 008.');
    organizational_elements_check(
        !str_contains($migrationSql, '@org_element_version_version_id'),
        'Migration 008 содержит неизвестную переменную версии.'
    );

    echo "ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK PASSED\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'ORGANIZATIONAL ELEMENT TYPES DIRECTORY CHECK FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
