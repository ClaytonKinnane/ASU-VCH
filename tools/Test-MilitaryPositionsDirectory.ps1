#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$DeployRoot = 'C:\OSPanel\home\asu-vch.local',
    [string]$ExpectedBranch = 'feature/military-positions-directory',
    [string]$ExpectedBaseSha = '8cc604eec7e973c2917ea0b1f9b08b976b673f41',
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

function Assert-PathSet {
    param(
        [Parameter(Mandatory = $true)][string[]]$Actual,
        [Parameter(Mandatory = $true)][string[]]$Expected
    )

    $ActualSorted = @($Actual | Where-Object { $_ -ne '' } | Sort-Object -Unique)
    $ExpectedSorted = @($Expected | Sort-Object -Unique)
    $Difference = @(Compare-Object -ReferenceObject $ExpectedSorted -DifferenceObject $ActualSorted)
    if ($Difference.Count -ne 0) {
        Write-Host 'Ожидаемые пути:' -ForegroundColor DarkYellow
        $ExpectedSorted | ForEach-Object { Write-Host "  $_" }
        Write-Host 'Фактические пути:' -ForegroundColor DarkYellow
        $ActualSorted | ForEach-Object { Write-Host "  $_" }
        throw 'Набор implementation-файлов не совпадает с утверждённым scope.'
    }
}

function Assert-SourceDeployParity {
    param(
        [Parameter(Mandatory = $true)][string]$RepositoryRoot,
        [Parameter(Mandatory = $true)][string]$DeployRoot,
        [Parameter(Mandatory = $true)][string[]]$RelativePaths
    )

    foreach ($RelativePath in $RelativePaths) {
        $Source = Join-Path $RepositoryRoot ($RelativePath -replace '/', '\')
        $Target = Join-Path $DeployRoot ($RelativePath -replace '/', '\')
        if (-not (Test-Path -LiteralPath $Source -PathType Leaf)) {
            throw "Source-файл не найден: $Source"
        }
        if (-not (Test-Path -LiteralPath $Target -PathType Leaf)) {
            throw "Deploy-файл не найден: $Target"
        }
        $SourceHash = (Get-FileHash -LiteralPath $Source -Algorithm SHA256).Hash
        $TargetHash = (Get-FileHash -LiteralPath $Target -Algorithm SHA256).Hash
        Write-Host "PARITY $RelativePath source=$SourceHash deploy=$TargetHash"
        if ($SourceHash -ne $TargetHash) {
            throw "Source/deploy parity нарушен: $RelativePath"
        }
    }
}

$RepositoryRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$DeployConfigPath = Join-Path $DeployRoot 'config\local.php'
$ExpectedPaths = @(
    'app/Directory/MilitaryPositionCatalogRepository.php',
    'app/bootstrap.php',
    'database/MilitaryPositionMigrationCompatibility.php',
    'database/OrganizationalStructureMigrationCompatibility.php',
    'database/migrations/010_military_positions_directory.sql',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part00',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part01',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part02',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part03',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part04',
    'docs/architecture/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md',
    'docs/decisions/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION-APPROVAL.md',
    'docs/implementation/MILITARY-POSITIONS-DIRECTORY-V1-IMPLEMENTATION.md',
    'docs/review/MILITARY-POSITIONS-DIRECTORY-V1-FORMAL-REVIEW.md',
    'docs/specification/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md',
    'docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-LOCAL-RUNBOOK.md',
    'docs/testing/MILITARY-POSITIONS-DIRECTORY-V1-TEST-PLAN.md',
    'public/admin/directories.php',
    'public/admin/directories/military-positions.php',
    'tools/Test-MilitaryPositionsDirectory.ps1',
    'tools/check-military-positions-directory.php'
)
$RuntimeParityPaths = @(
    'app/Directory/MilitaryPositionCatalogRepository.php',
    'app/bootstrap.php',
    'database/MilitaryPositionMigrationCompatibility.php',
    'database/OrganizationalStructureMigrationCompatibility.php',
    'database/migrations/010_military_positions_directory.sql',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part00',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part01',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part02',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part03',
    'database/migrations/010_military_positions_directory.sql.gz.b64.part04',
    'public/admin/directories.php',
    'public/admin/directories/military-positions.php'
)

Write-Step 'Repository preflight and remote verification'
Set-Location -LiteralPath $RepositoryRoot

$InitialChanges = @(git status --porcelain)
if ($InitialChanges.Count -ne 0) {
    $InitialChanges | ForEach-Object { Write-Host $_ }
    throw 'Рабочее дерево должно быть чистым до Testing.'
}

Invoke-External 'git' @('fetch', '--prune', 'origin')

$Branch = ((git branch --show-current) | Out-String).Trim()
$Head = ((git rev-parse HEAD) | Out-String).Trim()
$OriginMain = ((git rev-parse 'origin/main') | Out-String).Trim()
$OriginFeature = ((git rev-parse "origin/$ExpectedBranch") | Out-String).Trim()
$MergeBase = ((git merge-base 'origin/main' 'HEAD') | Out-String).Trim()
$Divergence = ((git rev-list --left-right --count "origin/$ExpectedBranch...HEAD") | Out-String).Trim()
$ChangedPaths = @(git diff --name-only "$ExpectedBaseSha...HEAD" | Where-Object { $_ -ne '' })

Write-Host "CURRENT_BRANCH=$Branch"
Write-Host "CURRENT_HEAD=$Head"
Write-Host "ORIGIN_MAIN_HEAD=$OriginMain"
Write-Host "ORIGIN_FEATURE_HEAD=$OriginFeature"
Write-Host "FEATURE_MERGE_BASE=$MergeBase"
Write-Host "EXPECTED_BASE_SHA=$ExpectedBaseSha"
Write-Host "ORIGIN_FEATURE_DIVERGENCE=$Divergence"
Write-Host "IMPLEMENTATION_FILE_COUNT=$($ChangedPaths.Count)"

if ($Branch -ne $ExpectedBranch) {
    throw "Ожидалась ветка $ExpectedBranch, фактически: $Branch"
}
if ($Head -ne $OriginFeature) {
    throw 'Локальный HEAD не совпадает с origin feature-веткой. Повторите синхронизацию.'
}
if ($Divergence -notmatch '^0\s+0$') {
    throw "Локальная feature-ветка расходится с origin: $Divergence"
}
if ($MergeBase -ne $ExpectedBaseSha) {
    throw "Feature-ветка создана не от утверждённого baseline. Merge base: $MergeBase"
}
Assert-PathSet -Actual $ChangedPaths -Expected $ExpectedPaths

$DiffCheckOutput = @(git diff --check "$ExpectedBaseSha...HEAD")
if ($LASTEXITCODE -ne 0 -or $DiffCheckOutput.Count -ne 0) {
    $DiffCheckOutput | ForEach-Object { Write-Host $_ }
    throw 'git diff --check обнаружил ошибки.'
}
if (@(git status --porcelain).Count -ne 0) {
    throw 'После preflight рабочее дерево перестало быть чистым.'
}
Write-Host 'IMPLEMENTATION_SCOPE_STATUS=PASS'

Write-Step 'Deploy configuration integrity'
if (-not (Test-Path -LiteralPath $DeployConfigPath -PathType Leaf)) {
    throw "Не найден deploy config/local.php: $DeployConfigPath"
}
$ConfigHashBefore = (Get-FileHash -LiteralPath $DeployConfigPath -Algorithm SHA256).Hash
Write-Host "DEPLOY_CONFIG_LOCAL_SHA256_BEFORE=$ConfigHashBefore"

Write-Step 'Database backup'
Invoke-External 'powershell.exe' @(
    '-NoProfile', '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $RepositoryRoot 'tools\Backup-Database.ps1'),
    '-DeployRoot', $DeployRoot
)

Write-Step 'Deploy implementation to Open Server Panel'
Invoke-External 'powershell.exe' @(
    '-NoProfile', '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $RepositoryRoot 'deploy\Deploy-Local.ps1')
)

$DeployTools = Join-Path $DeployRoot 'tools'
if (Test-Path -LiteralPath $DeployTools -PathType Container) {
    Remove-Item -LiteralPath $DeployTools -Recurse -Force
}
Copy-Item -LiteralPath (Join-Path $RepositoryRoot 'tools') -Destination $DeployTools -Recurse -Force
Write-Host "TEST_TOOLS_PUBLISHED=$DeployTools"

$ConfigHashAfterDeploy = (Get-FileHash -LiteralPath $DeployConfigPath -Algorithm SHA256).Hash
Write-Host "DEPLOY_CONFIG_LOCAL_SHA256_AFTER_DEPLOY=$ConfigHashAfterDeploy"
if ($ConfigHashAfterDeploy -ne $ConfigHashBefore) {
    throw 'Deploy изменил config/local.php.'
}

Write-Step 'PHP lint of deploy copy'
Invoke-External 'powershell.exe' @(
    '-NoProfile', '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $DeployRoot 'tools\Test-PhpSyntax.ps1'),
    '-PhpExecutable', 'php'
)

Write-Step 'Migration 010 and repeated installer'
Set-Location -LiteralPath $DeployRoot
Invoke-External 'php' @('.\database\install.php')
Invoke-External 'php' @('.\database\install.php')

Write-Step 'Military positions integration checker'
Invoke-External 'php' @('.\tools\check-military-positions-directory.php')

Write-Step 'Directory regressions'
Invoke-External 'php' @('.\tools\check-all-theme-directory-assets.php')
Invoke-External 'php' @(
    '.\tools\run-permission-baseline-compatible-checker.php',
    '.\tools\check-military-ranks-directory-core.php'
)
Invoke-External 'php' @(
    '.\tools\run-permission-baseline-compatible-checker.php',
    '.\tools\check-organizational-elements-directory-core.php'
)

Write-Step 'Security and theme regressions'
Invoke-External 'php' @('.\database\check-security-rbac.php')
Invoke-External 'php' @('.\database\check-security-user-approval.php')
Invoke-External 'php' @('.\database\check-security-required-password-change.php')
Invoke-External 'php' @(
    '.\tools\run-permission-baseline-compatible-checker.php',
    '.\database\check-security-user-rejection.php'
)
Invoke-External 'php' @(
    '.\tools\run-permission-baseline-compatible-checker.php',
    '.\database\check-security-user-archive-restore.php'
)
Invoke-External 'php' @('.\database\check-theme-management.php')
Invoke-External 'php' @('.\database\check-theme-asset-failure.php')

Write-Step 'Organization regression'
Invoke-External 'php' @('.\tools\check-organizational-structure.php')

Write-Step 'Source/deploy parity'
Assert-SourceDeployParity -RepositoryRoot $RepositoryRoot -DeployRoot $DeployRoot -RelativePaths $RuntimeParityPaths
Write-Host 'SOURCE_DEPLOY_PARITY_STATUS=PASS'

Write-Step 'HTTP smoke'
$SmokeArguments = @(
    '-NoProfile', '-ExecutionPolicy', 'Bypass',
    '-File', (Join-Path $DeployRoot 'tools\Test-LocalSmoke.ps1')
)
if ($AllowInvalidCertificate) {
    $SmokeArguments += '-AllowInvalidCertificate'
}
Invoke-External 'powershell.exe' $SmokeArguments

Write-Step 'Post-test repository and configuration integrity'
$ConfigHashFinal = (Get-FileHash -LiteralPath $DeployConfigPath -Algorithm SHA256).Hash
Write-Host "DEPLOY_CONFIG_LOCAL_SHA256_FINAL=$ConfigHashFinal"
if ($ConfigHashFinal -ne $ConfigHashBefore) {
    throw 'Итоговый SHA-256 config/local.php изменился.'
}

Set-Location -LiteralPath $RepositoryRoot
$FinalHead = ((git rev-parse HEAD) | Out-String).Trim()
$FinalDivergence = ((git rev-list --left-right --count "origin/$ExpectedBranch...HEAD") | Out-String).Trim()
$FinalChanges = @(git status --porcelain)

Write-Host "FINAL_HEAD=$FinalHead"
Write-Host "FINAL_ORIGIN_FEATURE_DIVERGENCE=$FinalDivergence"
Write-Host "FINAL_WORKING_TREE_CLEAN=$($FinalChanges.Count -eq 0)"

if ($FinalHead -ne $Head) {
    throw 'HEAD изменился во время Testing.'
}
if ($FinalDivergence -notmatch '^0\s+0$') {
    throw "Итоговое расхождение с origin feature-веткой: $FinalDivergence"
}
if ($FinalChanges.Count -ne 0) {
    $FinalChanges | ForEach-Object { Write-Host $_ }
    throw 'Рабочее дерево изменилось во время Testing.'
}

Write-Host "`nAUTOMATED_TESTING_STATUS=PASS" -ForegroundColor Green
Write-Host 'MANUAL_DESKTOP_ACCEPTANCE_STATUS=NOT_RUN'
Write-Host 'MOBILE_TESTING_STATUS=OUT_OF_SCOPE_NOT_RUN'
Write-Host 'COMMIT_PUSH_PR_STATUS=IMPLEMENTATION_COMMIT_ALREADY_ON_GITHUB_PR_NOT_CREATED'
exit 0
