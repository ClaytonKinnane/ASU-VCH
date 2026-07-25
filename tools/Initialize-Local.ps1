#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$PhpExecutable = '',
    [switch]$SeedLocalOwner,
    [switch]$SkipDeploy
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Step {
    param([Parameter(Mandatory = $true)][string]$Message)
    Write-Host "`n=== $Message ===" -ForegroundColor Cyan
}

function Resolve-PhpExecutable {
    param([string]$RequestedPath)

    if ($RequestedPath -ne '') {
        if (-not (Test-Path -LiteralPath $RequestedPath -PathType Leaf)) {
            throw "PHP не найден по указанному пути: $RequestedPath"
        }
        return (Resolve-Path -LiteralPath $RequestedPath).Path
    }

    $Command = Get-Command php -ErrorAction SilentlyContinue
    if ($null -ne $Command) {
        return $Command.Source
    }

    $Candidates = @(
        'C:\OSPanel\modules\PHP-8.5\PHP\php.exe',
        'C:\OSPanel\modules\PHP-8.5\php.exe'
    )

    foreach ($Candidate in $Candidates) {
        if (Test-Path -LiteralPath $Candidate -PathType Leaf) {
            return $Candidate
        }
    }

    $Discovered = Get-ChildItem -LiteralPath 'C:\OSPanel\modules\PHP-8.5' -Filter 'php.exe' -File -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($null -ne $Discovered) {
        return $Discovered.FullName
    }

    throw 'Не удалось найти php.exe. Укажите путь через параметр -PhpExecutable.'
}

$RepositoryRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$SyntaxScript = Join-Path $PSScriptRoot 'Test-PhpSyntax.ps1'
$DeployScript = Join-Path $RepositoryRoot 'deploy\Deploy-Local.ps1'
$TargetRoot = 'C:\OSPanel\home\asu-vch.local'
$TargetConfig = Join-Path $TargetRoot 'config\local.php'
$TargetInstaller = Join-Path $TargetRoot 'database\install.php'
$TargetSeeder = Join-Path $TargetRoot 'database\seed-local-owner.php'

try {
    Write-Step 'Определение PHP'
    $Php = Resolve-PhpExecutable -RequestedPath $PhpExecutable
    $PhpVersion = & $Php -r "echo PHP_VERSION;"
    if ($LASTEXITCODE -ne 0) {
        throw 'Не удалось запустить PHP.'
    }
    Write-Host "PHP: $Php"
    Write-Host "Версия: $PhpVersion"

    Write-Step 'Проверка синтаксиса'
    & $SyntaxScript -PhpExecutable $Php
    if ($LASTEXITCODE -ne 0) {
        throw 'Проверка PHP-синтаксиса завершилась ошибкой.'
    }

    if (-not $SkipDeploy) {
        Write-Step 'Развертывание в Open Server Panel'
        & $DeployScript
        if ($LASTEXITCODE -ne 0) {
            throw 'Развертывание завершилось ошибкой.'
        }
    }
    else {
        Write-Step 'Развертывание пропущено'
    }

    if (-not (Test-Path -LiteralPath $TargetConfig -PathType Leaf)) {
        throw "Не найден локальный конфигурационный файл: $TargetConfig"
    }

    Write-Step 'Установка базы данных'
    & $Php $TargetInstaller
    if ($LASTEXITCODE -ne 0) {
        throw 'Установка базы данных завершилась ошибкой.'
    }

    if ($SeedLocalOwner) {
        Write-Step 'Создание локального владельца'
        & $Php $TargetSeeder
        if ($LASTEXITCODE -ne 0) {
            throw 'Создание локального владельца завершилось ошибкой.'
        }
    }

    Write-Step 'Локальная инициализация завершена'
    Write-Host 'Адрес приложения: https://asu-vch.local'
    Write-Host 'Проверка состояния: https://asu-vch.local/health.php'
    if ($SeedLocalOwner) {
        Write-Host 'Локальная учетная запись: Admin / 12315' -ForegroundColor Yellow
    }
    else {
        Write-Host 'Состояние пользователей и первичной регистрации указано в отчете установки базы данных выше.'
    }
    Write-Host 'Перед открытием сайта перечитайте конфигурацию проектов или перезапустите Open Server Panel.'
    exit 0
}
catch {
    Write-Host "`nЛОКАЛЬНАЯ ИНИЦИАЛИЗАЦИЯ НЕ ВЫПОЛНЕНА." -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
