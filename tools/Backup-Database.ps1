#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$DeployRoot = 'C:\OSPanel\home\asu-vch.local',
    [string]$BackupDirectory = '',
    [string]$PhpExecutable = 'php',
    [string]$MySqlDumpExecutable = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Resolve-ExecutablePath {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Value,
        [Parameter(Mandatory = $true)]
        [string]$Label
    )

    if ([System.IO.Path]::IsPathRooted($Value)) {
        if (-not (Test-Path -LiteralPath $Value -PathType Leaf)) {
            throw "$Label не найден: $Value"
        }

        return (Resolve-Path -LiteralPath $Value).Path
    }

    $Command = Get-Command $Value -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($null -eq $Command) {
        throw "$Label не найден в PATH: $Value"
    }

    return $Command.Source
}

function ConvertTo-MySqlOptionValue {
    param(
        [AllowEmptyString()]
        [string]$Value
    )

    if ($Value.IndexOf("`0") -ge 0 -or $Value.Contains("`r") -or $Value.Contains("`n")) {
        throw 'Параметр подключения содержит недопустимый перевод строки или NUL.'
    }

    $Escaped = $Value.Replace('\', '\\').Replace('"', '\"')
    return '"' + $Escaped + '"'
}

$DeployRootPath = (Resolve-Path -LiteralPath $DeployRoot).Path
$ConfigPath = Join-Path $DeployRootPath 'config\local.php'
if (-not (Test-Path -LiteralPath $ConfigPath -PathType Leaf)) {
    throw "Не найден deploy-конфиг: $ConfigPath"
}

$OpenServerRoot = Split-Path -Parent (Split-Path -Parent $DeployRootPath)
if ([string]::IsNullOrWhiteSpace($BackupDirectory)) {
    $BackupDirectory = Join-Path $OpenServerRoot 'backups\asu-vch'
}

$null = New-Item -ItemType Directory -Path $BackupDirectory -Force
$BackupDirectoryPath = (Resolve-Path -LiteralPath $BackupDirectory).Path
$DeployPrefix = $DeployRootPath.TrimEnd('\') + '\'
if (
    $BackupDirectoryPath -ieq $DeployRootPath -or
    $BackupDirectoryPath.StartsWith($DeployPrefix, [System.StringComparison]::OrdinalIgnoreCase)
) {
    throw 'Каталог резервных копий не должен находиться внутри web/deploy-каталога.'
}

$PhpPath = Resolve-ExecutablePath -Value $PhpExecutable -Label 'PHP executable'
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$TemporaryId = [Guid]::NewGuid().ToString('N')
$ConfigReaderPath = Join-Path ([System.IO.Path]::GetTempPath()) "asu-vch-db-config-$TemporaryId.php"
$ConfigErrorPath = Join-Path ([System.IO.Path]::GetTempPath()) "asu-vch-db-config-$TemporaryId.err"

$ConfigReader = @'
<?php

declare(strict_types=1);

$config = require $argv[1];
$database = $config['database'] ?? null;
if (!is_array($database)) {
    fwrite(STDERR, "Раздел database отсутствует в config/local.php.\n");
    exit(2);
}

$required = ['host', 'port', 'name', 'username', 'password', 'charset'];
foreach ($required as $key) {
    if (!array_key_exists($key, $database)) {
        fwrite(STDERR, "Отсутствует database.{$key}.\n");
        exit(3);
    }
}

if (!is_int($database['port']) && !ctype_digit((string) $database['port'])) {
    fwrite(STDERR, "database.port должен быть целым числом.\n");
    exit(4);
}

$result = [
    'host' => (string) $database['host'],
    'port' => (int) $database['port'],
    'name' => (string) $database['name'],
    'username' => (string) $database['username'],
    'password' => (string) $database['password'],
    'charset' => (string) $database['charset'],
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
'@

try {
    [System.IO.File]::WriteAllText($ConfigReaderPath, $ConfigReader, $Utf8NoBom)
    $ConfigJsonOutput = & $PhpPath $ConfigReaderPath $ConfigPath 2> $ConfigErrorPath
    $ConfigExitCode = $LASTEXITCODE
    if ($ConfigExitCode -ne 0) {
        $ConfigError = if (Test-Path -LiteralPath $ConfigErrorPath) {
            (Get-Content -LiteralPath $ConfigErrorPath -Raw).Trim()
        }
        else {
            ''
        }
        throw "Не удалось прочитать параметры БД из deploy-конфига. ExitCode=$ConfigExitCode. $ConfigError"
    }

    $Database = (($ConfigJsonOutput -join [Environment]::NewLine) | ConvertFrom-Json)
}
finally {
    Remove-Item -LiteralPath $ConfigReaderPath -Force -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $ConfigErrorPath -Force -ErrorAction SilentlyContinue
}

if ([string]::IsNullOrWhiteSpace([string] $Database.name)) {
    throw 'Имя базы данных не задано.'
}
if ([int] $Database.port -lt 1 -or [int] $Database.port -gt 65535) {
    throw 'Порт базы данных находится вне диапазона 1–65535.'
}

$DumpPath = $null
if (-not [string]::IsNullOrWhiteSpace($MySqlDumpExecutable)) {
    $DumpPath = Resolve-ExecutablePath -Value $MySqlDumpExecutable -Label 'mysqldump executable'
}
else {
    $DumpCommand = Get-Command 'mysqldump.exe' -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($null -eq $DumpCommand) {
        $DumpCommand = Get-Command 'mysqldump' -ErrorAction SilentlyContinue | Select-Object -First 1
    }

    if ($null -ne $DumpCommand) {
        $DumpPath = $DumpCommand.Source
    }
    else {
        $ExpectedDumpPath = Join-Path $OpenServerRoot ("modules\database\{0}\bin\mysqldump.exe" -f [string] $Database.host)
        if (Test-Path -LiteralPath $ExpectedDumpPath -PathType Leaf) {
            $DumpPath = (Resolve-Path -LiteralPath $ExpectedDumpPath).Path
        }
        else {
            $DumpCandidates = @(
                Get-ChildItem -LiteralPath (Join-Path $OpenServerRoot 'modules') -Filter 'mysqldump.exe' -File -Recurse -ErrorAction SilentlyContinue |
                    Sort-Object FullName
            )
            $MatchingCandidates = @(
                $DumpCandidates | Where-Object {
                    $_.FullName -like ("*\{0}\*" -f [string] $Database.host)
                }
            )

            if ($MatchingCandidates.Count -ge 1) {
                $DumpPath = $MatchingCandidates[0].FullName
            }
            elseif ($DumpCandidates.Count -eq 1) {
                $DumpPath = $DumpCandidates[0].FullName
            }
            elseif ($DumpCandidates.Count -gt 1) {
                $CandidateList = ($DumpCandidates.FullName -join [Environment]::NewLine)
                throw "Найдено несколько mysqldump.exe. Укажите -MySqlDumpExecutable явно:`n$CandidateList"
            }
        }
    }
}

if ([string]::IsNullOrWhiteSpace([string] $DumpPath) -or -not (Test-Path -LiteralPath $DumpPath -PathType Leaf)) {
    throw 'mysqldump.exe не найден. Укажите путь параметром -MySqlDumpExecutable.'
}

$SafeDatabaseName = [regex]::Replace([string] $Database.name, '[^A-Za-z0-9_.-]', '_')
$Timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$BackupPath = Join-Path $BackupDirectoryPath "$SafeDatabaseName-$Timestamp.sql"
if (Test-Path -LiteralPath $BackupPath) {
    $BackupPath = Join-Path $BackupDirectoryPath ("{0}-{1}-{2}.sql" -f $SafeDatabaseName, $Timestamp, [Guid]::NewGuid().ToString('N').Substring(0, 8))
}

$DumpTemporaryId = [Guid]::NewGuid().ToString('N')
$DefaultsPath = Join-Path ([System.IO.Path]::GetTempPath()) "asu-vch-mysql-$DumpTemporaryId.cnf"
$DumpErrorPath = Join-Path ([System.IO.Path]::GetTempPath()) "asu-vch-mysqldump-$DumpTemporaryId.err"
$DefaultsContent = @(
    '[client]'
    'host=' + (ConvertTo-MySqlOptionValue ([string] $Database.host))
    'port=' + ([int] $Database.port)
    'user=' + (ConvertTo-MySqlOptionValue ([string] $Database.username))
    'password=' + (ConvertTo-MySqlOptionValue ([string] $Database.password))
    'default-character-set=' + (ConvertTo-MySqlOptionValue ([string] $Database.charset))
) -join "`r`n"

$DumpArguments = @(
    "--defaults-extra-file=$DefaultsPath"
    '--single-transaction'
    '--quick'
    '--routines'
    '--triggers'
    '--events'
    '--hex-blob'
    '--no-tablespaces'
    '--set-gtid-purged=OFF'
    "--result-file=$BackupPath"
    '--databases'
    [string] $Database.name
)

try {
    [System.IO.File]::WriteAllText($DefaultsPath, $DefaultsContent + "`r`n", $Utf8NoBom)
    $DumpStandardOutput = & $DumpPath @DumpArguments 2> $DumpErrorPath
    $DumpExitCode = $LASTEXITCODE
    $DumpError = if (Test-Path -LiteralPath $DumpErrorPath) {
        (Get-Content -LiteralPath $DumpErrorPath -Raw).Trim()
    }
    else {
        ''
    }

    if ($DumpExitCode -ne 0) {
        Remove-Item -LiteralPath $BackupPath -Force -ErrorAction SilentlyContinue
        throw "mysqldump завершился с ошибкой. ExitCode=$DumpExitCode. $DumpError"
    }

    if ($null -ne $DumpStandardOutput -and ($DumpStandardOutput | Out-String).Trim() -ne '') {
        Write-Verbose (($DumpStandardOutput | Out-String).Trim())
    }
    if ($DumpError -ne '') {
        Write-Warning $DumpError
    }
}
finally {
    Remove-Item -LiteralPath $DefaultsPath -Force -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $DumpErrorPath -Force -ErrorAction SilentlyContinue
}

if (-not (Test-Path -LiteralPath $BackupPath -PathType Leaf)) {
    throw "Файл резервной копии не создан: $BackupPath"
}

$BackupFile = Get-Item -LiteralPath $BackupPath
if ($BackupFile.Length -le 0) {
    Remove-Item -LiteralPath $BackupPath -Force -ErrorAction SilentlyContinue
    throw 'Создан пустой файл резервной копии.'
}

$BackupHash = Get-FileHash -LiteralPath $BackupPath -Algorithm SHA256
$DumpVersion = ((& $DumpPath --version 2>&1) | Out-String).Trim()

Write-Host 'BACKUP_STATUS=PASS' -ForegroundColor Green
Write-Host "DATABASE_NAME=$($Database.name)"
Write-Host "MYSQLDUMP_EXECUTABLE=$DumpPath"
Write-Host "MYSQLDUMP_VERSION=$DumpVersion"
Write-Host "BACKUP_FILE=$($BackupFile.FullName)"
Write-Host "BACKUP_SIZE_BYTES=$($BackupFile.Length)"
Write-Host "BACKUP_SHA256=$($BackupHash.Hash)"
