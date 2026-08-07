#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$RepositoryPath = 'C:\Project\ASU-VCH',
    [string]$DeployPath = 'C:\OSPanel\home\asu-vch.local',
    [string]$ExpectedBranch = 'feature/military-positions-directory-v1',
    [string]$ExpectedBaseSha = '9ae05b9928903cc483ce415d7378b546e419264c',
    [string]$ExpectedHead = '',
    [string]$PhpExecutable = 'php',
    [switch]$RunInitialization,
    [switch]$RunHttpSmoke,
    [switch]$AllowInvalidCertificate
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$MaxChangedPaths = 38
$AllowedPaths = @(
    'app/bootstrap.php',
    'app/Directory/MilitaryPositionCatalogRepository.php',
    'app/Directory/MilitaryPositionCatalogService.php',
    'app/Directory/MilitaryPositionCatalogFunctions.php',
    'app/Staffing/StaffingRepository.php',
    'public/admin/content.php',
    'public/admin/directories.php',
    'public/admin/directories/military-positions.php',
    'public/admin/directories/military-positions/version.php',
    'public/admin/directories/military-positions/history.php',
    'public/admin/directories/military-positions/versions/create.php',
    'public/admin/directories/military-positions/versions/publish.php',
    'public/admin/directories/military-positions/versions/cancel.php',
    'public/admin/directories/military-positions/entries/create.php',
    'public/admin/directories/military-positions/entries/update.php',
    'public/admin/directories/military-positions/entries/archive.php',
    'public/admin/directories/military-positions/entries/restore.php',
    'public/admin/directories/military-positions/views/version-card.php',
    'public/admin/directories/military-positions/views/entry-card.php',
    'public/admin/directories/military-positions/views/entry-form.php',
    'database/migrations/014_military_positions_directory_v1.sql',
    'themes/asu-blue/assets/css/directories.css',
    'themes/asu-light-blue/assets/css/directories.css',
    'themes/asu-evgeniya-rostova/assets/css/directories.css',
    'tools/Test-MilitaryPositionsDirectoryV1.ps1',
    'tools/check-military-positions-directory-v1.php',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-ARCHITECTURE.md',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-SPECIFICATION.md',
    'docs/design/MILITARY-POSITIONS-DIRECTORY-V1-REVIEW.md',
    'docs/domains/REFERENCE.md',
    'docs/domains/STAFFING.md',
    'docs/ACCESS.md',
    'docs/DATABASE-CURRENT.md',
    'docs/migrations/README.md',
    'docs/PROJECT-STATUS.md',
    'docs/ROADMAP.md',
    'docs/TRACEABILITY.md',
    'docs/CHAT-HANDOFF.md'
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

$Branch = (& git branch --show-current).Trim()
$Head = (& git rev-parse HEAD).Trim()
$MergeBase = (& git merge-base $ExpectedBaseSha HEAD).Trim()
if ($LASTEXITCODE -ne 0 -or $Branch -cne $ExpectedBranch) {
    throw "Unexpected branch. Expected=$ExpectedBranch Actual=$Branch"
}
if ($MergeBase -cne $ExpectedBaseSha) {
    throw "Merge base mismatch. Expected=$ExpectedBaseSha Actual=$MergeBase"
}
if ($ExpectedHead -ne '' -and $Head -cne $ExpectedHead) {
    throw "Implementation head mismatch. Expected=$ExpectedHead Actual=$Head"
}

$ChangedPaths = @(& git diff --name-only "$ExpectedBaseSha...HEAD")
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to read changed path inventory.'
}
if ($ChangedPaths.Count -gt $MaxChangedPaths) {
    throw "Changed path count exceeds approval. Actual=$($ChangedPaths.Count) Max=$MaxChangedPaths"
}
$Unexpected = @($ChangedPaths | Where-Object { $AllowedPaths -cnotcontains $_ })
if ($Unexpected.Count -gt 0) {
    throw "Unexpected changed paths: $($Unexpected -join ', ')"
}
Invoke-NativeChecked -FilePath 'git' -Arguments @('diff', '--check', "$ExpectedBaseSha...HEAD")

$Status = @(& git status --porcelain=v1 --untracked-files=all)
if ($LASTEXITCODE -ne 0 -or $Status.Count -gt 0) {
    throw "Repository worktree must be clean: $($Status -join '; ')"
}

$Php = (Get-Command $PhpExecutable -ErrorAction Stop).Source
$PhpFiles = @($ChangedPaths | Where-Object { $_ -like '*.php' })
foreach ($RelativePath in $PhpFiles) {
    Invoke-NativeChecked -FilePath $Php -Arguments @('-l', (Join-Path $RepositoryPath $RelativePath))
}
Invoke-NativeChecked -FilePath $Php -Arguments @((Join-Path $RepositoryPath 'tools\check-military-positions-directory-v1.php'))

if ($RunInitialization) {
    $DeployConfig = Join-Path $DeployPath 'config\local.php'
    if (-not (Test-Path -LiteralPath $DeployConfig -PathType Leaf)) {
        throw "Deploy config not found for mandatory pre-migration backup: $DeployConfig"
    }
    $ConfigHashBefore = (Get-FileHash -LiteralPath $DeployConfig -Algorithm SHA256).Hash
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $RepositoryPath 'tools\Backup-Database.ps1') -DeployRoot $DeployPath -PhpExecutable $Php
    if ($LASTEXITCODE -ne 0) { throw 'Pre-migration database backup failed.' }

    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $RepositoryPath 'tools\Initialize-Local.ps1') -PhpExecutable $Php
    if ($LASTEXITCODE -ne 0) { throw 'Initialize-Local.ps1 failed.' }
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $RepositoryPath 'tools\Initialize-Local.ps1') -PhpExecutable $Php -SkipDeploy
    if ($LASTEXITCODE -ne 0) { throw 'Repeat initializer failed.' }

    $ConfigHashAfter = (Get-FileHash -LiteralPath $DeployConfig -Algorithm SHA256).Hash
    if ($ConfigHashAfter -cne $ConfigHashBefore) {
        throw 'Initialization changed deploy config/local.php.'
    }
    Invoke-NativeChecked -FilePath $Php -Arguments @(
        (Join-Path $RepositoryPath 'tools\check-military-positions-directory-v1.php'),
        "--runtime-root=$DeployPath"
    )
}

if ($RunHttpSmoke) {
    $SmokeArguments = @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass',
        '-File', (Join-Path $RepositoryPath 'tools\Test-LocalSmoke.ps1')
    )
    if ($AllowInvalidCertificate) { $SmokeArguments += '-AllowInvalidCertificate' }
    & powershell.exe @SmokeArguments
    if ($LASTEXITCODE -ne 0) { throw 'HTTP smoke failed.' }
}

Write-Output 'MILITARY_POSITIONS_DIRECTORY_V1_BRANCH=PASS'
Write-Output "MILITARY_POSITIONS_DIRECTORY_V1_HEAD=$Head"
Write-Output "MILITARY_POSITIONS_DIRECTORY_V1_MERGE_BASE=$MergeBase"
Write-Output "MILITARY_POSITIONS_DIRECTORY_V1_CHANGED_PATHS=$($ChangedPaths.Count)"
Write-Output "MILITARY_POSITIONS_DIRECTORY_V1_MAX_CHANGED_PATHS=$MaxChangedPaths"
Write-Output 'MILITARY_POSITIONS_DIRECTORY_V1_ALLOWLIST=PASS'
Write-Output 'MILITARY_POSITIONS_DIRECTORY_V1_PHP_LINT=PASS'
Write-Output 'MILITARY_POSITIONS_DIRECTORY_V1_STATIC_CHECKER=PASS'
Write-Output ('MILITARY_POSITIONS_DIRECTORY_V1_INITIALIZATION=' + $(if ($RunInitialization) { 'RUN' } else { 'NOT_RUN' }))
Write-Output ('MILITARY_POSITIONS_DIRECTORY_V1_HTTP_SMOKE=' + $(if ($RunHttpSmoke) { 'RUN' } else { 'NOT_RUN' }))
Write-Output 'MOBILE_ACCEPTANCE=NOT_RUN'
Write-Output 'REAL_STAFFING_DATA=PROHIBITED'
