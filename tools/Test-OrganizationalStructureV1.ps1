#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$DeployRoot = 'C:\OSPanel\home\asu-vch.local',
    [string]$ExpectedBranch = 'feature/organizational-structure-v1',
    [switch]$AllowInvalidCertificate
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Step {
    param([Parameter(Mandatory = $true)][string]$Message)
    Write-Host "`n=== $Message ===" -ForegroundColor Yellow
}

function Invoke-External {
    param(
        [Parameter(Mandatory = $true)][string]$Executable,
        [Parameter()][string[]]$Arguments = @()
    )

    Write-Host "> $Executable $($Arguments -join ' ')" -ForegroundColor Cyan
    & $Executable @Arguments
    $ExitCode = $LASTEXITCODE
    if ($ExitCode -ne 0) {
        throw "Команда завершилась с кодом $ExitCode`: $Executable"
    }
}

$RepositoryRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$DeployConfigPath = Join-Path $DeployRoot 'config\local.php'

Write-Step 'Синхронизация GitHub feature-ветки'
Set-Location $RepositoryRoot

$InitialChanges = @(git status --porcelain)
if ($InitialChanges.Count -ne 0) {
    throw 'Рабочее дерево репозитория не является чистым.'
}

Invoke-External 'git' @('fetch', 'origin')
Invoke-External 'git' @('switch', $ExpectedBranch)
Invoke-External 'git' @('pull', '--ff-only')

$CurrentBranch = ((git branch --show-current) | Out-String).Trim()
$CurrentHead = ((git rev-parse HEAD) | Out-String).Trim()
$Divergence = ((git rev-list --left-right --count "origin/$ExpectedBranch...HEAD") | Out-String).Trim()
$CurrentChanges = @(git status --porcelain)

Write-Host "CURRENT_BRANCH=$CurrentBranch"
Write-Host "CURRENT_HEAD=$CurrentHead"
Write-Host "ORIGIN_DIVERGENCE=$Divergence"
Write-Host "WORKING_TREE_CLEAN=$($CurrentChanges.Count -eq 0)"

if ($CurrentBranch -ne $ExpectedBranch) {
    throw "Ожидалась ветка $ExpectedBranch, фактически: $CurrentBranch"
}
if ($Divergence -notmatch '^0\s+0$') {
    throw "Локальная ветка расходится с origin: $Divergence"
}
if ($CurrentChanges.Count -ne 0) {
    throw 'Рабочее дерево изменилось после синхронизации.'
}

Write-Step 'Проверка deploy-конфигурации'
if (-not (Test-Path -LiteralPath $DeployConfigPath -PathType Leaf)) {
    throw "Не найден deploy-конфиг: $DeployConfigPath"
}
$ConfigHashBefore = (Get-FileHash -LiteralPath $DeployConfigPath -Algorithm SHA256).Hash
Write-Host "DEPLOY_CONFIG_LOCAL_SHA256_BEFORE=$ConfigHashBefore"

Write-Step 'Резервное копирование БД'
Invoke-External 'powershell' @(
    '-NoProfile',
    '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $RepositoryRoot 'tools\Backup-Database.ps1'),
    '-DeployRoot', $DeployRoot
)

Write-Step 'Утвержденный deploy в Open Server Panel'
Invoke-External 'powershell' @(
    '-NoProfile',
    '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $RepositoryRoot 'deploy\Deploy-Local.ps1')
)

$DeployTools = Join-Path $DeployRoot 'tools'
if (Test-Path -LiteralPath $DeployTools -PathType Container) {
    Remove-Item -LiteralPath $DeployTools -Recurse -Force
}
Copy-Item -LiteralPath (Join-Path $RepositoryRoot 'tools') -Destination $DeployTools -Recurse -Force
Write-Host "TEST_TOOLS_PUBLISHED=$DeployTools"

if (-not (Test-Path -LiteralPath $DeployConfigPath -PathType Leaf)) {
    throw 'Deploy не восстановил config/local.php.'
}
$ConfigHashAfterDeploy = (Get-FileHash -LiteralPath $DeployConfigPath -Algorithm SHA256).Hash
Write-Host "DEPLOY_CONFIG_LOCAL_SHA256_AFTER_DEPLOY=$ConfigHashAfterDeploy"
Write-Host "DEPLOY_CONFIG_PRESERVED=$($ConfigHashAfterDeploy -eq $ConfigHashBefore)"
if ($ConfigHashAfterDeploy -ne $ConfigHashBefore) {
    throw 'Deploy изменил config/local.php.'
}

Write-Step 'PHP lint deploy-копии'
Invoke-External 'powershell' @(
    '-NoProfile',
    '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $DeployRoot 'tools\Test-PhpSyntax.ps1'),
    '-PhpExecutable', 'php'
)

Write-Step 'Migration 009 and idempotency'
Set-Location $DeployRoot
Invoke-External 'php' @('.\database\install.php')
Invoke-External 'php' @('.\database\install.php')

Write-Step 'Интеграционный checker организационной структуры'
Invoke-External 'php' @('.\tools\check-organizational-structure.php')

Write-Step 'Security regression'
Invoke-External 'php' @('.\database\check-security-rbac.php')
Invoke-External 'php' @('.\database\check-security-user-approval.php')
Invoke-External 'php' @('.\database\check-security-required-password-change.php')
Invoke-External 'php' @('.\database\check-security-user-rejection.php')
Invoke-External 'php' @('.\database\check-security-user-archive-restore.php')

Write-Step 'Themes and directories regression'
Invoke-External 'php' @('.\database\check-theme-management.php')
Invoke-External 'php' @('.\database\check-theme-asset-failure.php')
Invoke-External 'php' @('.\tools\check-all-theme-directory-assets.php')
Invoke-External 'php' @('.\tools\check-military-ranks-directory.php')
Invoke-External 'php' @('.\tools\check-organizational-elements-directory.php')

Write-Step 'HTTP smoke'
$SmokeArguments = @(
    '-NoProfile',
    '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $DeployRoot 'tools\Test-LocalSmoke.ps1')
)
if ($AllowInvalidCertificate) {
    $SmokeArguments += '-AllowInvalidCertificate'
}
Invoke-External 'powershell' $SmokeArguments

Write-Step 'Post-test integrity'
$ConfigHashFinal = (Get-FileHash -LiteralPath $DeployConfigPath -Algorithm SHA256).Hash
Write-Host "DEPLOY_CONFIG_LOCAL_SHA256_FINAL=$ConfigHashFinal"
Write-Host "DEPLOY_CONFIG_FINAL_PRESERVED=$($ConfigHashFinal -eq $ConfigHashBefore)"
if ($ConfigHashFinal -ne $ConfigHashBefore) {
    throw 'Итоговый SHA-256 config/local.php изменился.'
}

Set-Location $RepositoryRoot
$FinalHead = ((git rev-parse HEAD) | Out-String).Trim()
$FinalChanges = @(git status --porcelain)
$FinalDivergence = ((git rev-list --left-right --count "origin/$ExpectedBranch...HEAD") | Out-String).Trim()

Write-Host "FINAL_HEAD=$FinalHead"
Write-Host "FINAL_WORKING_TREE_CLEAN=$($FinalChanges.Count -eq 0)"
Write-Host "FINAL_ORIGIN_DIVERGENCE=$FinalDivergence"

if ($FinalHead -ne $CurrentHead) {
    throw 'HEAD репозитория изменился во время тестирования.'
}
if ($FinalChanges.Count -ne 0) {
    throw 'Рабочее дерево репозитория изменилось во время тестирования.'
}
if ($FinalDivergence -notmatch '^0\s+0$') {
    throw "Итоговое расхождение с origin: $FinalDivergence"
}

Write-Host "`nAUTOMATED_TESTING_STATUS=PASS" -ForegroundColor Green
exit 0
