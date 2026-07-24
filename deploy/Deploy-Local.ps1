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

$PublicSource = Join-Path $RepositoryRoot 'public'
$ThemesSource = Join-Path $RepositoryRoot 'themes'
$ProjectConfigSource = Join-Path $RepositoryRoot 'deploy\ospanel\.osp\project.ini'

$TargetPublic = Join-Path $TargetRoot 'public'
$TargetThemes = Join-Path $TargetPublic 'themes'
$TargetConfigDirectory = Join-Path $TargetRoot '.osp'
$TargetProjectConfig = Join-Path $TargetConfigDirectory 'project.ini'

try {
    Write-Step 'Проверка репозитория'

    Assert-Directory -Path (Join-Path $RepositoryRoot '.git') -Description 'Каталог .git полного клона'
    Assert-Directory -Path $PublicSource -Description 'Публичный каталог проекта'
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

    $RequiredModules = @('Apache', 'PHP-8.5', 'MySQL-8.4')

    foreach ($ModuleName in $RequiredModules) {
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

    Write-Step 'Подготовка каталога назначения'

    if (-not (Test-Path -LiteralPath $TargetRoot -PathType Container)) {
        New-Item -ItemType Directory -Path $TargetRoot -Force | Out-Null
        Write-Host "Создан каталог: $TargetRoot"
    }
    else {
        Get-ChildItem -LiteralPath $TargetRoot -Force | Remove-Item -Recurse -Force
        Write-Host "Очищено содержимое: $TargetRoot"
    }

    New-Item -ItemType Directory -Path $TargetPublic -Force | Out-Null
    New-Item -ItemType Directory -Path $TargetConfigDirectory -Force | Out-Null

    Write-Step 'Копирование файлов приложения'

    Copy-DirectoryContents -Source $PublicSource -Destination $TargetPublic
    Write-Host "Публичные файлы: $PublicSource -> $TargetPublic"

    New-Item -ItemType Directory -Path $TargetThemes -Force | Out-Null
    Copy-DirectoryContents -Source $ThemesSource -Destination $TargetThemes
    Write-Host "Темы: $ThemesSource -> $TargetThemes"

    Copy-Item -LiteralPath $ProjectConfigSource -Destination $TargetProjectConfig -Force
    Write-Host "Конфигурация: $ProjectConfigSource -> $TargetProjectConfig"

    $CopiedFiles = @(Get-ChildItem -LiteralPath $TargetRoot -File -Recurse -Force)

    Write-Step 'Развертывание завершено'

    Write-Host "Каталог сайта: $TargetRoot"
    Write-Host "Публичный каталог: $TargetPublic"
    Write-Host "Скопировано файлов: $($CopiedFiles.Count)"
    Write-Host 'Домен: https://asu-vch.local'
    Write-Host ''
    Write-Host 'Следующее действие:'
    Write-Host '1. В Open Server Panel перечитайте конфигурацию проектов или перезапустите панель.'
    Write-Host '2. Убедитесь, что Apache и PHP-8.5 запущены.'
    Write-Host '3. Откройте https://asu-vch.local в браузере.'
    Write-Host ''
    Write-Host 'Примечание: существующий проект C:\OSPanel\home\asu.local не изменялся.'
}
catch {
    Write-Host ''
    Write-Host 'РАЗВЕРТЫВАНИЕ НЕ ВЫПОЛНЕНО.' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
