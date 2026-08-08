[CmdletBinding()]
param(
    [string]$RepositoryPath = 'C:\Project\ASU-VCH',
    [string]$DeployPath = 'C:\OSPanel\home\asu-vch.local',
    [switch]$RunInitialization,
    [switch]$RunRuntimeChecker,
    [switch]$RunHttpSmoke,
    [switch]$PreMigrationBackupConfirmed
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$ExpectedBranch = 'feature/personnel-core-card-v1'
$OriginalBase = 'dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8'
$MaxChangedPaths = 40
$AllowedPaths = @(
    'public/admin/content.php','docs/domains/README.md','docs/DATABASE.md','docs/DATABASE-CURRENT.md','docs/ACCESS.md',
    'docs/PROJECT-STATUS.md','docs/ROADMAP.md','docs/TRACEABILITY.md','docs/CHAT-HANDOFF.md',
    'database/migrations/015_personnel_core_card_v1.sql','app/Personnel/PersonnelRepository.php','app/Personnel/PersonnelService.php',
    'app/Personnel/PersonnelSupportTrait.php','app/Personnel/PersonnelCreateUpdateTrait.php','app/Personnel/PersonnelIdentifierTrait.php',
    'app/Personnel/PersonnelLifecycleTrait.php','app/Personnel/functions.php','public/admin/personnel/persons.php','public/admin/personnel/person.php',
    'public/admin/personnel/persons/create.php','public/admin/personnel/persons/update.php','public/admin/personnel/persons/archive.php',
    'public/admin/personnel/persons/restore.php','public/admin/personnel/identifiers/create.php','public/admin/personnel/identifiers/replace.php',
    'public/admin/personnel/identifiers/end.php','public/admin/personnel/history.php','public/admin/personnel/views/person-list.php',
    'public/admin/personnel/views/person-card.php','public/admin/personnel/views/person-form.php','public/admin/personnel/views/identifier-form.php',
    'public/admin/personnel/views/history-list.php','tools/check-personnel-core-card-v1.php','tools/Test-PersonnelCoreCardV1.ps1',
    'docs/domains/PERSONNEL.md','docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md','docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md',
    'docs/design/PERSONNEL-CORE-CARD-V1-SPECIFICATION.md','docs/design/PERSONNEL-CORE-CARD-V1-REVIEW.md','docs/design/PERSONNEL-CORE-CARD-V1-APPROVAL.md'
)

function Invoke-NativeChecked {
    param([Parameter(Mandatory=$true)][string]$FilePath,[string[]]$Arguments=@())
    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) { throw "Command failed ($LASTEXITCODE): $FilePath $($Arguments -join ' ')" }
}

if (-not (Test-Path -LiteralPath $RepositoryPath -PathType Container)) { throw "Repository path not found: $RepositoryPath" }
Set-Location -LiteralPath $RepositoryPath
$branch = (& git branch --show-current).Trim()
if ($LASTEXITCODE -ne 0 -or $branch -cne $ExpectedBranch) { throw "Unexpected branch. Expected=$ExpectedBranch Actual=$branch" }
$mergeBase = (& git merge-base $OriginalBase HEAD).Trim()
if ($LASTEXITCODE -ne 0 -or $mergeBase -cne $OriginalBase) { throw "Merge base mismatch. Expected=$OriginalBase Actual=$mergeBase" }
$changedPaths = @(& git diff --name-only "$OriginalBase...HEAD")
if ($LASTEXITCODE -ne 0) { throw 'Unable to read changed path inventory.' }
if ($changedPaths.Count -gt $MaxChangedPaths) { throw "Changed path count exceeds approval. Actual=$($changedPaths.Count) Max=$MaxChangedPaths" }
$unexpected = @($changedPaths | Where-Object { $AllowedPaths -cnotcontains $_ })
if ($unexpected.Count -gt 0) { throw "Unexpected changed paths: $($unexpected -join ', ')" }
Invoke-NativeChecked -FilePath 'git' -Arguments @('diff','--check',"$OriginalBase...HEAD")

$phpCommand = Get-Command php -ErrorAction Stop
$phpFiles = @(& git ls-files -- '*.php')
if ($LASTEXITCODE -ne 0 -or $phpFiles.Count -eq 0) { throw 'Unable to enumerate tracked PHP files.' }
foreach ($relativePath in $phpFiles) { Invoke-NativeChecked -FilePath $phpCommand.Source -Arguments @('-l',(Join-Path $RepositoryPath $relativePath)) }
Invoke-NativeChecked -FilePath $phpCommand.Source -Arguments @((Join-Path $RepositoryPath 'tools\check-personnel-core-card-v1.php'))

if ($RunInitialization) {
    if (-not $PreMigrationBackupConfirmed) { throw 'RunInitialization requires -PreMigrationBackupConfirmed after an external backup of the current local database.' }
    $initializer = Join-Path $RepositoryPath 'tools\Initialize-Local.ps1'
    if (-not (Test-Path -LiteralPath $initializer -PathType Leaf)) { throw "Initializer not found: $initializer" }
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $initializer
    if ($LASTEXITCODE -ne 0) { throw 'Initialize-Local.ps1 failed.' }
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $initializer -SkipDeploy
    if ($LASTEXITCODE -ne 0) { throw 'Repeat initializer failed.' }
}
if ($RunRuntimeChecker) {
    if (-not $RunInitialization) { throw 'RunRuntimeChecker requires -RunInitialization.' }
    $runtimeBootstrap = Join-Path $DeployPath 'app\bootstrap.php'
    if (-not (Test-Path -LiteralPath $runtimeBootstrap -PathType Leaf)) { throw "Deployed runtime bootstrap not found: $runtimeBootstrap" }
    $runtimeLocalConfig = Join-Path $DeployPath 'config\local.php'
    if (-not (Test-Path -LiteralPath $runtimeLocalConfig -PathType Leaf)) { throw "Deployed runtime config not found: $runtimeLocalConfig" }
    Invoke-NativeChecked -FilePath $phpCommand.Source -Arguments @(
        (Join-Path $RepositoryPath 'tools\check-personnel-core-card-v1.php'),
        '--runtime',
        ('--runtime-bootstrap=' + $runtimeBootstrap)
    )
}
if ($RunHttpSmoke) {
    $smoke = Join-Path $RepositoryPath 'tools\Test-LocalSmoke.ps1'
    if (-not (Test-Path -LiteralPath $smoke -PathType Leaf)) { throw "HTTP smoke script not found: $smoke" }
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $smoke -AllowInvalidCertificate
    if ($LASTEXITCODE -ne 0) { throw 'HTTP smoke failed.' }
}
if ($RunInitialization -and -not (Test-Path -LiteralPath $DeployPath -PathType Container)) { throw "Deploy path not found after initialization: $DeployPath" }
$status = @(& git status --porcelain=v1 --untracked-files=all)
if ($LASTEXITCODE -ne 0) { throw 'Unable to read repository status.' }
if ($status.Count -gt 0) { throw "Repository worktree is not clean after validation: $($status -join '; ')" }

Write-Output 'PERSONNEL_CORE_CARD_V1_BRANCH=PASS'
Write-Output "PERSONNEL_CORE_CARD_V1_MERGE_BASE=$mergeBase"
Write-Output "PERSONNEL_CORE_CARD_V1_CHANGED_PATHS=$($changedPaths.Count)"
Write-Output "PERSONNEL_CORE_CARD_V1_MAX_CHANGED_PATHS=$MaxChangedPaths"
Write-Output 'PERSONNEL_CORE_CARD_V1_ALLOWLIST=PASS'
Write-Output "PERSONNEL_CORE_CARD_V1_PHP_LINT_COUNT=$($phpFiles.Count)"
Write-Output 'PERSONNEL_CORE_CARD_V1_PHP_LINT=PASS'
Write-Output 'PERSONNEL_CORE_CARD_V1_STATIC_CHECKER=PASS'
Write-Output ('PRE_MIGRATION_BACKUP=' + $(if ($PreMigrationBackupConfirmed) { 'CONFIRMED' } else { 'NOT_CONFIRMED' }))
Write-Output ('PERSONNEL_CORE_CARD_V1_INITIALIZATION=' + $(if ($RunInitialization) { 'RUN' } else { 'NOT_RUN' }))
Write-Output ('PERSONNEL_CORE_CARD_V1_RUNTIME_CHECKER=' + $(if ($RunRuntimeChecker) { 'RUN' } else { 'NOT_RUN' }))
Write-Output ('PERSONNEL_CORE_CARD_V1_HTTP_SMOKE=' + $(if ($RunHttpSmoke) { 'RUN' } else { 'NOT_RUN' }))
Write-Output 'MOBILE_ACCEPTANCE=NOT_RUN'
Write-Output 'PRODUCTION_DEPLOYMENT=NOT_PERFORMED'
