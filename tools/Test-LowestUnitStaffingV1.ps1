[CmdletBinding()]
param(
    [Parameter()]
    [string]$RepositoryPath = 'C:\Project\ASU-VCH',

    [Parameter()]
    [string]$DeployPath = 'C:\OSPanel\home\asu-vch.local',

    [Parameter()]
    [switch]$RunInitialization,

    [Parameter()]
    [switch]$RunHttpSmoke
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$ExpectedBranch = 'feature/lowest-unit-staffing-v1'
$OriginalBase = 'd60db94e405979c8f29bdc3dcaae7950362fb13a'
$MaxChangedPaths = 47
$AllowedPaths = @(
    'app/bootstrap.php',
    'public/admin/content.php',
    'docs/domains/README.md',
    'docs/PROJECT-STATUS.md',
    'docs/ROADMAP.md',
    'docs/TRACEABILITY.md',
    'database/migrations/013_lowest_unit_staffing_v1.sql',
    'app/Staffing/StaffingRepository.php',
    'app/Staffing/StaffingCreateUpdateTrait.php',
    'app/Staffing/StaffingDocumentTrait.php',
    'app/Staffing/StaffingLifecycleTrait.php',
    'app/Staffing/StaffingSlotTrait.php',
    'app/Staffing/StaffingSupportTrait.php',
    'app/Staffing/StaffingService.php',
    'app/Staffing/functions.php',
    'public/admin/staffing/registers.php',
    'public/admin/staffing/register.php',
    'public/admin/staffing/registers/create.php',
    'public/admin/staffing/registers/update.php',
    'public/admin/staffing/registers/archive.php',
    'public/admin/staffing/registers/restore.php',
    'public/admin/staffing/versions/create.php',
    'public/admin/staffing/versions/approve.php',
    'public/admin/staffing/versions/activate.php',
    'public/admin/staffing/versions/cancel.php',
    'public/admin/staffing/documents/create.php',
    'public/admin/staffing/documents/update.php',
    'public/admin/staffing/documents/unlink.php',
    'public/admin/staffing/slots/create.php',
    'public/admin/staffing/slots/update.php',
    'public/admin/staffing/slots/remove.php',
    'public/admin/staffing/compare.php',
    'public/admin/staffing/history.php',
    'public/admin/staffing/views/register-list.php',
    'public/admin/staffing/views/register-card.php',
    'public/admin/staffing/views/version-card.php',
    'public/admin/staffing/views/slot-form.php',
    'public/admin/staffing/views/document-form.php',
    'themes/asu-blue/assets/css/organization.css',
    'themes/asu-light-blue/assets/css/organization.css',
    'themes/asu-evgeniya-rostova/assets/css/organization.css',
    'tools/Test-LowestUnitStaffingV1.ps1',
    'tools/check-lowest-unit-staffing-v1.php',
    'docs/domains/STAFFING.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-ARCHITECTURE.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-SPECIFICATION.md',
    'docs/design/LOWEST-UNIT-STAFFING-V1-REVIEW.md'
)

function Invoke-NativeChecked {
    param(
        [Parameter(Mandatory = $true)][string]$FilePath,
        [Parameter()][string[]]$Arguments = @()
    )
    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed ($LASTEXITCODE): $FilePath $($Arguments -join ' ')"
    }
}

if (-not (Test-Path -LiteralPath $RepositoryPath -PathType Container)) {
    throw "Repository path not found: $RepositoryPath"
}
Set-Location -LiteralPath $RepositoryPath

$branch = (& git branch --show-current).Trim()
if ($LASTEXITCODE -ne 0 -or $branch -cne $ExpectedBranch) {
    throw "Unexpected branch. Expected=$ExpectedBranch Actual=$branch"
}

$mergeBase = (& git merge-base $OriginalBase HEAD).Trim()
if ($LASTEXITCODE -ne 0 -or $mergeBase -cne $OriginalBase) {
    throw "Merge base mismatch. Expected=$OriginalBase Actual=$mergeBase"
}

$changedPaths = @(& git diff --name-only "$OriginalBase...HEAD")
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to read changed path inventory.'
}
if ($changedPaths.Count -gt $MaxChangedPaths) {
    throw "Changed path count exceeds approval. Actual=$($changedPaths.Count) Max=$MaxChangedPaths"
}
$unexpected = @($changedPaths | Where-Object { $AllowedPaths -cnotcontains $_ })
if ($unexpected.Count -gt 0) {
    throw "Unexpected changed paths: $($unexpected -join ', ')"
}
Invoke-NativeChecked -FilePath 'git' -Arguments @('diff', '--check', "$OriginalBase...HEAD")

$phpCommand = Get-Command php -ErrorAction Stop
$phpFiles = @($changedPaths | Where-Object { $_ -like '*.php' })
foreach ($relativePath in $phpFiles) {
    Invoke-NativeChecked -FilePath $phpCommand.Source -Arguments @('-l', (Join-Path $RepositoryPath $relativePath))
}
Invoke-NativeChecked -FilePath $phpCommand.Source -Arguments @((Join-Path $RepositoryPath 'tools\check-lowest-unit-staffing-v1.php'))

if ($RunInitialization) {
    $initializer = Join-Path $RepositoryPath 'tools\Initialize-Local.ps1'
    if (-not (Test-Path -LiteralPath $initializer -PathType Leaf)) {
        throw "Initializer not found: $initializer"
    }
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $initializer
    if ($LASTEXITCODE -ne 0) {
        throw 'Initialize-Local.ps1 failed.'
    }
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $initializer -SkipDeploy
    if ($LASTEXITCODE -ne 0) {
        throw 'Repeat initializer failed.'
    }
}

if ($RunHttpSmoke) {
    $smoke = Join-Path $RepositoryPath 'tools\Test-LocalSmoke.ps1'
    if (-not (Test-Path -LiteralPath $smoke -PathType Leaf)) {
        throw "HTTP smoke script not found: $smoke"
    }
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $smoke
    if ($LASTEXITCODE -ne 0) {
        throw 'HTTP smoke failed.'
    }
}

if ($RunInitialization -and -not (Test-Path -LiteralPath $DeployPath -PathType Container)) {
    throw "Deploy path not found after initialization: $DeployPath"
}

$status = @(& git status --porcelain=v1 --untracked-files=all)
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to read repository status.'
}
if ($status.Count -gt 0) {
    throw "Repository worktree is not clean after validation: $($status -join '; ')"
}

Write-Output 'LOWEST_UNIT_STAFFING_V1_BRANCH=PASS'
Write-Output "LOWEST_UNIT_STAFFING_V1_MERGE_BASE=$mergeBase"
Write-Output "LOWEST_UNIT_STAFFING_V1_CHANGED_PATHS=$($changedPaths.Count)"
Write-Output "LOWEST_UNIT_STAFFING_V1_MAX_CHANGED_PATHS=$MaxChangedPaths"
Write-Output 'LOWEST_UNIT_STAFFING_V1_ALLOWLIST=PASS'
Write-Output 'LOWEST_UNIT_STAFFING_V1_PHP_LINT=PASS'
Write-Output 'LOWEST_UNIT_STAFFING_V1_STATIC_CHECKER=PASS'
Write-Output ('LOWEST_UNIT_STAFFING_V1_INITIALIZATION=' + $(if ($RunInitialization) { 'RUN' } else { 'NOT_RUN' }))
Write-Output ('LOWEST_UNIT_STAFFING_V1_HTTP_SMOKE=' + $(if ($RunHttpSmoke) { 'RUN' } else { 'NOT_RUN' }))
Write-Output 'MOBILE_ACCEPTANCE=NOT_RUN'
Write-Output 'REAL_STAFFING_DATA=PROHIBITED'
