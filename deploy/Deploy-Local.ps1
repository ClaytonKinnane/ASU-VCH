#requires -Version 5.1

[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Step {
    param([Parameter(Mandatory = $true)][string]$Message)
    Write-Host "`n=== $Message ===" -ForegroundColor Cyan
}

function Assert-Directory {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Description
    )

    if (-not (Test-Path -LiteralPath $Path -PathType Container)) {
        throw "$Description не найден: $Path"
    }
}

function Assert-File {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Description
    )

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "$Description не найден: $Path"
    }
}

function Copy-DirectoryContents {
    param(
        [Parameter(Mandatory = $true)][string]$Source,
        [Parameter(Mandatory = $true)][string]$Destination
    )

    New-Item -ItemType Directory -Path $Destination -Force | Out-Null
    Get-ChildItem -LiteralPath $Source -Force | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $Destination -Recurse -Force
    }
}

$RepositoryRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$OspRoot = 'C:\OSPanel'
$OspExecutable = Join-Path $OspRoot 'bin\ospanel.exe'
$OspHome = Join-Path $OspRoot 'home'
$TargetRoot = Join-Path $OspHome 'asu-vch.local'
$ExpectedTargetRoot = 'C:\OSPanel\home\asu-vch.local'

$SourceDirectories = [ordered]@{
    'app' = (Join-Path $RepositoryRoot 'app')
    'config' = (Join-Path $RepositoryRoot 'config')
    'database' = (Join-Path $RepositoryRoot 'database')
    'public' = (Join-Path $RepositoryRoot 'public')
}
$ThemesSource = Join-Path $RepositoryRoot 'themes'
$ProjectConfigSource = Join-Path $RepositoryRoot 'deploy\ospanel\.osp\project.ini'
$TargetProjectConfigDirectory = Join-Path $TargetRoot '.osp'
$TargetProjectConfig = Join-Path $TargetProjectConfigDirectory 'project.ini'
$TargetLocalConfig = Join-Path $TargetRoot 'config\local.php'
$LocalConfigExample = Join-Path $TargetRoot 'config\local.example.php'
$LocalConfigBackup = $null

try {
    Write-Step 'Проверка репозитория'

    Assert-Directory -Path (Join-Path $RepositoryRoot '.git') -Description 'Каталог .git полного клона'
    foreach ($Entry in $SourceDirectories.GetEnumerator()) {
        Assert-Directory -Path $Entry.Value -Description "Каталог $($Entry.Key)"
    }
    Assert-Directory -Path $ThemesSource -Description 'Каталог тем проекта'
    Assert-File -Path $ProjectConfigSource -Description 'Конфигурация Open Server Panel'

    Write-Host "Корень репозитория: $RepositoryRoot"
    Write-Host 'Полный клон подтвержден.'

    Write-Step 'Проверка Open Server Panel'

    Assert-Directory -Path $OspRoot -Description 'Каталог Open Server Panel'
    Assert-File -Path $OspExecutable -Description 'Исполняемый файл Open Server Panel'
    Assert-Directory -Path $OspHome -Description 'Каталог проектов Open Server Panel'

    $VersionInfo = (Get-Item -LiteralPath $OspExecutable).VersionInfo
    $DetectedVersion = $VersionInfo.FileVersion
    Write-Host "Open Server Panel: $OspExecutable"
    Write-Host "Обнаруженная версия: $DetectedVersion"

    if ($DetectedVersion -ne '6.5.1.0') {
        Write-Warning "Сценарий проверен для Open Server Panel 6.5.1.0, обнаружена версия $DetectedVersion."
    }

    foreach ($ModuleName in @('Apache', 'PHP-8.5', 'MySQL-8.4')) {
        $ModulePath = Join-Path (Join-Path $OspRoot 'modules') $ModuleName
        Assert-Directory -Path $ModulePath -Description "Модуль $ModuleName"
        Write-Host "Модуль найден: $ModuleName"
    }

    Write-Step 'Проверка безопасного пути назначения'

    $NormalizedTargetRoot = [System.IO.Path]::GetFullPath($TargetRoot).TrimEnd('\')
    $NormalizedExpectedTargetRoot = [System.IO.Path]::GetFullPath($ExpectedTargetRoot).TrimEnd('\')
    $NormalizedOspHome = [System.IO.Path]::GetFullPath($OspHome).TrimEnd('\')

    if ($NormalizedTargetRoot -ne $NormalizedExpectedTargetRoot) {
        throw "Отказ от очистки: путь назначения не совпадает с утвержденным путем. Получено: $NormalizedTargetRoot"
    }
    if (-not $NormalizedTargetRoot.StartsWith($NormalizedOspHome + '\', [System.StringComparison]::OrdinalIgnoreCase)) {
        throw 'Отказ от очистки: путь назначения находится вне каталога Open Server Panel home.'
    }

    Write-Host "Путь назначения подтвержден: $NormalizedTargetRoot"
    Write-Host 'Другие проекты Open Server Panel затронуты не будут.'

    Write-Step 'Сохранение локальной конфигурации'

    if (Test-Path -LiteralPath $TargetLocalConfig -PathType Leaf) {
        $LocalConfigBackup = Join-Path ([System.IO.Path]::GetTempPath()) ("asu-vch-local-{0}.php" -f [guid]::NewGuid())
        Copy-Item -LiteralPath $TargetLocalConfig -Destination $LocalConfigBackup -Force
        Write-Host 'Существующий config/local.php временно сохранен.'
    }
    else {
        Write-Host 'Существующий config/local.php не найден.'
    }

    Write-Step 'Подготовка каталога назначения'

    if (-not (Test-Path -LiteralPath $TargetRoot -PathType Container)) {
        New-Item -ItemType Directory -Path $TargetRoot -Force | Out-Null
        Write-Host "Создан каталог: $TargetRoot"
    }
    else {
        Get-ChildItem -LiteralPath $TargetRoot -Force | Remove-Item -Recurse -Force
        Write-Host "Очищено содержимое: $TargetRoot"
    }

    Write-Step 'Копирование файлов приложения'

    foreach ($Entry in $SourceDirectories.GetEnumerator()) {
        $Destination = Join-Path $TargetRoot $Entry.Key
        Copy-DirectoryContents -Source $Entry.Value -Destination $Destination
        Write-Host "$($Entry.Key): $($Entry.Value) -> $Destination"
    }

    $TargetThemes = Join-Path $TargetRoot 'public\themes'
    Copy-DirectoryContents -Source $ThemesSource -Destination $TargetThemes
    Write-Host "themes: $ThemesSource -> $TargetThemes"

    New-Item -ItemType Directory -Path $TargetProjectConfigDirectory -Force | Out-Null
    Copy-Item -LiteralPath $ProjectConfigSource -Destination $TargetProjectConfig -Force
    Write-Host "Конфигурация OSP: $ProjectConfigSource -> $TargetProjectConfig"

    Write-Step 'Восстановление локальной конфигурации'

    if ($LocalConfigBackup -and (Test-Path -LiteralPath $LocalConfigBackup -PathType Leaf)) {
        Copy-Item -LiteralPath $LocalConfigBackup -Destination $TargetLocalConfig -Force
        Remove-Item -LiteralPath $LocalConfigBackup -Force
        $LocalConfigBackup = $null
        Write-Host 'Существующий config/local.php восстановлен.'
    }
    elseif (-not (Test-Path -LiteralPath $TargetLocalConfig -PathType Leaf)) {
        Assert-File -Path $LocalConfigExample -Description 'Пример локальной конфигурации'
        Copy-Item -LiteralPath $LocalConfigExample -Destination $TargetLocalConfig -Force
        Write-Warning 'Создан config/local.php из example-файла. Перед установкой БД проверьте учетные данные.'
    }

    $CopiedFiles = @(Get-ChildItem -LiteralPath $TargetRoot -File -Recurse -Force)

    Write-Step 'Развертывание завершено'

    Write-Host "Каталог сайта: $TargetRoot"
    Write-Host "Публичный каталог: $(Join-Path $TargetRoot 'public')"
    Write-Host "Скопировано файлов: $($CopiedFiles.Count)"
    Write-Host 'Домен: https://asu-vch.local'
    Write-Host ''
    Write-Host 'Следующие действия:'
    Write-Host '1. Проверьте C:\OSPanel\home\asu-vch.local\config\local.php.'
    Write-Host '2. Запустите: php C:\OSPanel\home\asu-vch.local\database\install.php'
    Write-Host '3. При необходимости создайте тестового владельца:'
    Write-Host '   php C:\OSPanel\home\asu-vch.local\database\seed-local-owner.php'
    Write-Host '4. Перезапустите конфигурацию проектов Open Server Panel.'
    Write-Host '5. Откройте https://asu-vch.local и https://asu-vch.local/health.php'
}
catch {
    if ($LocalConfigBackup -and (Test-Path -LiteralPath $LocalConfigBackup -PathType Leaf)) {
        Remove-Item -LiteralPath $LocalConfigBackup -Force -ErrorAction SilentlyContinue
    }
    Write-Host ''
    Write-Host 'РАЗВЕРТЫВАНИЕ НЕ ВЫПОЛНЕНО.' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
