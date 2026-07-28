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

$PhpScript = Join-Path $PSScriptRoot 'backup-database.php'
if (-not (Test-Path -LiteralPath $PhpScript -PathType Leaf)) {
    throw "Не найден PHP backup tool: $PhpScript"
}

$CommandArguments = @(
    $PhpScript,
    "--deploy-root=$DeployRoot"
)

if (-not [string]::IsNullOrWhiteSpace($BackupDirectory)) {
    $CommandArguments += "--backup-directory=$BackupDirectory"
}
if (-not [string]::IsNullOrWhiteSpace($MySqlDumpExecutable)) {
    $CommandArguments += "--mysqldump=$MySqlDumpExecutable"
}

& $PhpExecutable @CommandArguments
$ExitCode = $LASTEXITCODE
if ($ExitCode -ne 0) {
    throw "Резервное копирование завершилось с кодом $ExitCode."
}

exit 0
