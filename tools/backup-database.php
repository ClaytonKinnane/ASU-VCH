<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function fail(string $message, int $code = 1): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function normalizePath(string $path): string
{
    $resolved = realpath($path);
    if ($resolved === false) {
        fail("Путь не найден: {$path}");
    }

    return rtrim($resolved, "\\/");
}

function isPathInside(string $candidate, string $parent): bool
{
    $candidate = strtolower(str_replace('/', '\\', rtrim($candidate, "\\/")));
    $parent = strtolower(str_replace('/', '\\', rtrim($parent, "\\/")));

    return $candidate === $parent || str_starts_with($candidate, $parent . '\\');
}

function findExecutableInPath(string $name): ?string
{
    $path = getenv('PATH');
    if (!is_string($path) || $path === '') {
        return null;
    }

    foreach (explode(PATH_SEPARATOR, $path) as $directory) {
        $directory = trim($directory, " \t\n\r\0\x0B\"");
        if ($directory === '') {
            continue;
        }

        $candidate = rtrim($directory, "\\/") . DIRECTORY_SEPARATOR . $name;
        if (is_file($candidate)) {
            return realpath($candidate) ?: $candidate;
        }
    }

    return null;
}

function locateMySqlDump(string $openServerRoot, string $moduleName, ?string $explicit): string
{
    if ($explicit !== null && $explicit !== '') {
        if (!is_file($explicit)) {
            fail("mysqldump не найден: {$explicit}");
        }

        return realpath($explicit) ?: $explicit;
    }

    foreach (['mysqldump.exe', 'mysqldump'] as $name) {
        $fromPath = findExecutableInPath($name);
        if ($fromPath !== null) {
            return $fromPath;
        }
    }

    $expected = $openServerRoot . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
        . $moduleName . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
    if (is_file($expected)) {
        return realpath($expected) ?: $expected;
    }

    $pattern = $openServerRoot . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
        . '*' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
    $candidates = glob($pattern) ?: [];
    sort($candidates, SORT_NATURAL | SORT_FLAG_CASE);

    $matching = array_values(array_filter(
        $candidates,
        static fn (string $path): bool => stripos($path, DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR) !== false
    ));

    if (count($matching) === 1) {
        return realpath($matching[0]) ?: $matching[0];
    }
    if (count($candidates) === 1) {
        return realpath($candidates[0]) ?: $candidates[0];
    }
    if (count($candidates) > 1) {
        fail("Найдено несколько mysqldump.exe. Укажите --mysqldump явно:\n" . implode("\n", $candidates));
    }

    fail('mysqldump.exe не найден. Укажите путь параметром --mysqldump.');
}

function optionValue(string $value): string
{
    if (str_contains($value, "\0") || str_contains($value, "\r") || str_contains($value, "\n")) {
        fail('Параметр подключения содержит перевод строки или NUL.');
    }

    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}

$options = getopt('', [
    'deploy-root::',
    'backup-directory::',
    'mysqldump::',
]);

$deployRootInput = isset($options['deploy-root']) && is_string($options['deploy-root'])
    ? $options['deploy-root']
    : 'C:\\OSPanel\\home\\asu-vch.local';
$deployRoot = normalizePath($deployRootInput);
$configPath = $deployRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.php';
if (!is_file($configPath)) {
    fail("Не найден deploy-конфиг: {$configPath}");
}

$config = require $configPath;
$database = is_array($config) ? ($config['database'] ?? null) : null;
if (!is_array($database)) {
    fail('Раздел database отсутствует в config/local.php.');
}

foreach (['host', 'port', 'name', 'username', 'password', 'charset'] as $key) {
    if (!array_key_exists($key, $database)) {
        fail("Отсутствует database.{$key}.");
    }
}

$port = filter_var($database['port'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 65535],
]);
if ($port === false) {
    fail('database.port должен находиться в диапазоне 1–65535.');
}

$databaseName = trim((string) $database['name']);
if ($databaseName === '') {
    fail('Имя базы данных не задано.');
}

$openServerRoot = dirname(dirname($deployRoot));
$backupDirectoryInput = isset($options['backup-directory']) && is_string($options['backup-directory'])
    ? $options['backup-directory']
    : $openServerRoot . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'asu-vch';
if (!is_dir($backupDirectoryInput) && !mkdir($backupDirectoryInput, 0700, true) && !is_dir($backupDirectoryInput)) {
    fail("Не удалось создать каталог резервных копий: {$backupDirectoryInput}");
}
$backupDirectory = normalizePath($backupDirectoryInput);
if (isPathInside($backupDirectory, $deployRoot)) {
    fail('Каталог резервных копий не должен находиться внутри web/deploy-каталога.');
}

$explicitDump = isset($options['mysqldump']) && is_string($options['mysqldump'])
    ? $options['mysqldump']
    : null;
$dumpExecutable = locateMySqlDump($openServerRoot, (string) $database['host'], $explicitDump);

$safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $databaseName) ?: 'database';
$timestamp = (new DateTimeImmutable())->format('Ymd-His');
$backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $safeName . '-' . $timestamp . '.sql';
if (is_file($backupPath)) {
    $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $safeName . '-' . $timestamp . '-'
        . substr(bin2hex(random_bytes(4)), 0, 8) . '.sql';
}

$defaultsPath = tempnam(sys_get_temp_dir(), 'asu-vch-mysql-');
if ($defaultsPath === false) {
    fail('Не удалось создать временный MySQL defaults-файл.');
}
$errorPath = tempnam(sys_get_temp_dir(), 'asu-vch-dump-error-');
if ($errorPath === false) {
    @unlink($defaultsPath);
    fail('Не удалось создать временный файл ошибок mysqldump.');
}

$defaults = implode("\r\n", [
    '[client]',
    'host=' . optionValue((string) $database['host']),
    'port=' . $port,
    'user=' . optionValue((string) $database['username']),
    'password=' . optionValue((string) $database['password']),
    'default-character-set=' . optionValue((string) $database['charset']),
]) . "\r\n";

try {
    if (file_put_contents($defaultsPath, $defaults, LOCK_EX) === false) {
        fail('Не удалось записать временный MySQL defaults-файл.');
    }

    $command = [
        $dumpExecutable,
        '--defaults-extra-file=' . $defaultsPath,
        '--single-transaction',
        '--quick',
        '--routines',
        '--triggers',
        '--events',
        '--hex-blob',
        '--no-tablespaces',
        '--set-gtid-purged=OFF',
        '--result-file=' . $backupPath,
        '--databases',
        $databaseName,
    ];
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['file', $errorPath, 'a'],
    ];
    $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        fail('Не удалось запустить mysqldump.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $exitCode = proc_close($process);
    $stderr = is_file($errorPath) ? trim((string) file_get_contents($errorPath)) : '';

    if ($exitCode !== 0) {
        @unlink($backupPath);
        fail("mysqldump завершился с ошибкой. ExitCode={$exitCode}. {$stderr}");
    }
    if (is_string($stdout) && trim($stdout) !== '') {
        fwrite(STDOUT, trim($stdout) . PHP_EOL);
    }
    if ($stderr !== '') {
        fwrite(STDERR, "WARNING: {$stderr}" . PHP_EOL);
    }
} finally {
    @unlink($defaultsPath);
    @unlink($errorPath);
}

if (!is_file($backupPath)) {
    fail("Файл резервной копии не создан: {$backupPath}");
}
$size = filesize($backupPath);
if (!is_int($size) || $size <= 0) {
    @unlink($backupPath);
    fail('Создан пустой файл резервной копии.');
}
$hash = hash_file('sha256', $backupPath);
if (!is_string($hash) || $hash === '') {
    fail('Не удалось вычислить SHA-256 резервной копии.');
}

$versionCommand = [$dumpExecutable, '--version'];
$versionProcess = proc_open(
    $versionCommand,
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $versionPipes,
    null,
    null,
    ['bypass_shell' => true]
);
$version = '';
if (is_resource($versionProcess)) {
    fclose($versionPipes[0]);
    $version = trim((string) stream_get_contents($versionPipes[1]));
    fclose($versionPipes[1]);
    $versionError = trim((string) stream_get_contents($versionPipes[2]));
    fclose($versionPipes[2]);
    proc_close($versionProcess);
    if ($version === '') {
        $version = $versionError;
    }
}

fwrite(STDOUT, "BACKUP_STATUS=PASS\n");
fwrite(STDOUT, "DATABASE_NAME={$databaseName}\n");
fwrite(STDOUT, "MYSQLDUMP_EXECUTABLE={$dumpExecutable}\n");
fwrite(STDOUT, "MYSQLDUMP_VERSION={$version}\n");
fwrite(STDOUT, "BACKUP_FILE={$backupPath}\n");
fwrite(STDOUT, "BACKUP_SIZE_BYTES={$size}\n");
fwrite(STDOUT, 'BACKUP_SHA256=' . strtoupper($hash) . "\n");
