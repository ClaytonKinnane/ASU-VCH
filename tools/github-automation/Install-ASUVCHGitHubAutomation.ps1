#requires -Version 5.1
<#
.SYNOPSIS
    One-command local Git/GitHub/Codex bootstrap for ASU-VCH.

.DESCRIPTION
    Verifies a synchronized main branch, installs or verifies Git, GitHub CLI
    and Codex, guides interactive sign-in, validates the repository manifest,
    and atomically deploys local helper files.

    This script does not create/delete branches, create/merge pull requests,
    or change branch protection, required checks, Actions settings, repository
    settings, application runtime, database, migrations, themes or deployment.
#>

[CmdletBinding(SupportsShouldProcess = $true)]
param(
    [ValidateSet('Install', 'Repair', 'Doctor')]
    [string]$Mode = 'Install',

    [string]$RepositoryPath = 'C:\Project\ASU-VCH',

    [string]$InstallPath = 'C:\Tools\ASU-VCH',

    [string]$RepositoryFullName = 'ClaytonKinnane/ASU-VCH',

    [string]$RemoteName = 'origin',

    [string]$MainBranch = 'main',

    [switch]$SkipCodex,

    [switch]$SkipGitHubLogin,

    [switch]$NoUpgrade,

    [switch]$AllowDirtyWorktree,

    [switch]$NonInteractivePackages
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$script:Pending = New-Object System.Collections.Generic.List[string]
$script:Capability = [ordered]@{
    POWERSHELL_5_1_READY = 'NO'
    WINGET_READY = 'NO'
    GIT_READY = 'NO'
    GITHUB_CLI_READY = 'NO'
    GITHUB_AUTH_READY = 'NO'
    GITHUB_REPOSITORY_WRITE_ACCESS = 'NO'
    CODEX_READY = 'NO'
    CODEX_CHATGPT_AUTH_READY = 'NO'
    ASU_VCH_REPOSITORY_READY = 'NO'
    ASU_VCH_LOCAL_HELPERS_READY = 'NO'
    LOCAL_CODEX_AGENT_READY = 'NO'
}

$logRoot = if ([string]::IsNullOrWhiteSpace($env:LOCALAPPDATA)) {
    $env:TEMP
}
else {
    $env:LOCALAPPDATA
}

$logDirectory = Join-Path $logRoot 'ASU-VCH\Logs'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$script:Log = Join-Path $logDirectory (
    'bootstrap-{0}.log' -f (Get-Date -Format 'yyyyMMdd-HHmmss')
)

function Write-Log {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Message,

        [ValidateSet('INFO', 'PASS', 'WARN', 'FAIL')]
        [string]$Level = 'INFO'
    )

    $safe = $Message `
        -replace '(?i)(authorization:\s*bearer\s+)[^\s]+', '$1[REDACTED]' `
        -replace '(?i)(token|api[_ -]?key|password|cookie|device[_ -]?code)\s*[:=]\s*[^\s;]+', '$1=[REDACTED]'

    $line = '{0} [{1}] {2}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Level, $safe
    Add-Content -LiteralPath $script:Log -Value $line -Encoding UTF8

    $color = switch ($Level) {
        'PASS' { 'Green' }
        'WARN' { 'Yellow' }
        'FAIL' { 'Red' }
        default { 'Gray' }
    }

    Write-Host $line -ForegroundColor $color
}

function Assert-True {
    param(
        [Parameter(Mandatory = $true)]
        [bool]$Condition,

        [Parameter(Mandatory = $true)]
        [string]$Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

function Get-CommandPath {
    param([Parameter(Mandatory = $true)][string]$Name)

    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($null -eq $command) {
        return $null
    }

    return $command.Source
}

function Get-FirstLine {
    param($Lines)

    $items = @($Lines)
    if ($items.Count -eq 0) {
        return $null
    }

    return ([string]$items[0]).Trim()
}

function Invoke-External {
    param(
        [Parameter(Mandatory = $true)]
        [string]$File,

        [string[]]$Arguments = @(),

        [int[]]$AllowedExitCodes = @(0),

        [switch]$Quiet
    )

    if ($Quiet) {
        & $File @Arguments *> $null
        $output = @()
    }
    else {
        $output = @(& $File @Arguments 2>&1 | ForEach-Object { [string]$_ })
    }

    $exitCode = $LASTEXITCODE
    if ($AllowedExitCodes -notcontains $exitCode) {
        throw ('Command failed ({0}): {1} {2}{3}{4}' -f
            $exitCode,
            $File,
            ($Arguments -join ' '),
            [Environment]::NewLine,
            ($output -join [Environment]::NewLine))
    }

    return $output
}

function Invoke-Interactive {
    param(
        [Parameter(Mandatory = $true)]
        [string]$File,

        [string[]]$Arguments = @()
    )

    & $File @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw ('Interactive command failed ({0}): {1} {2}' -f
            $LASTEXITCODE, $File, ($Arguments -join ' '))
    }
}

function Refresh-ProcessPath {
    $paths = @(
        [Environment]::GetEnvironmentVariable('Path', 'Machine'),
        [Environment]::GetEnvironmentVariable('Path', 'User')
    ) | Where-Object {
        -not [string]::IsNullOrWhiteSpace($_)
    }

    $env:Path = $paths -join ';'
}

function Get-NormalizedHash {
    param([Parameter(Mandatory = $true)][string]$Path)

    $utf8 = New-Object System.Text.UTF8Encoding($false, $true)
    $text = $utf8.GetString([IO.File]::ReadAllBytes($Path))
    $text = $text.Replace("`r`n", "`n").Replace("`r", "`n")

    $sha = [Security.Cryptography.SHA256]::Create()
    try {
        $bytes = $sha.ComputeHash($utf8.GetBytes($text))
    }
    finally {
        $sha.Dispose()
    }

    return (($bytes | ForEach-Object { $_.ToString('x2') }) -join '')
}

function Test-RemoteIdentity {
    param([Parameter(Mandatory = $true)][string]$Url)

    $repository = $RepositoryFullName.ToLowerInvariant()
    $actual = $Url.Trim().TrimEnd('/').ToLowerInvariant()

    return @(
        "https://github.com/$repository",
        "https://github.com/$repository.git",
        "git@github.com:$repository.git",
        "ssh://git@github.com/$repository.git"
    ) -contains $actual
}

function Test-GitHubAuth {
    if ($null -eq (Get-CommandPath -Name 'gh')) {
        return $false
    }

    & gh auth status --hostname github.com --active *> $null
    return ($LASTEXITCODE -eq 0)
}

function Test-CodexAuth {
    if ($null -eq (Get-CommandPath -Name 'codex')) {
        return $false
    }

    & codex login status *> $null
    return ($LASTEXITCODE -eq 0)
}

function Get-WinGet {
    Refresh-ProcessPath
    $winget = Get-CommandPath -Name 'winget'
    if ($null -eq $winget) {
        return $null
    }

    & $winget --info *> $null
    if ($LASTEXITCODE -ne 0) {
        return $null
    }

    $script:Capability.WINGET_READY = 'YES'
    return $winget
}

function Require-WinGet {
    $winget = Get-WinGet
    if ($null -ne $winget) {
        Write-Log -Level PASS -Message "WinGet=$winget"
        return $winget
    }

    Write-Log -Level WARN -Message 'WinGet is unavailable; Microsoft App Installer is required.'
    Write-Host 'Official page: https://apps.microsoft.com/detail/9NBLGGH4NNS1'

    try {
        if ($PSCmdlet.ShouldProcess('Microsoft App Installer page', 'Open')) {
            Start-Process 'https://apps.microsoft.com/detail/9NBLGGH4NNS1'
        }
    }
    catch {
        Write-Log -Level WARN -Message 'Could not open the App Installer page automatically.'
    }

    throw 'Install Microsoft App Installer and rerun the same command.'
}

function Ensure-WinGetPackage {
    param(
        [Parameter(Mandatory = $true)][string]$PackageId,
        [Parameter(Mandatory = $true)][string]$CommandName
    )

    $current = Get-CommandPath -Name $CommandName

    if ($Mode -eq 'Doctor') {
        Assert-True -Condition ($null -ne $current) `
            -Message "$CommandName is unavailable in Doctor mode."
        return $current
    }

    $operation = if ($null -eq $current) {
        'install'
    }
    elseif ($Mode -eq 'Repair' -and -not $NoUpgrade) {
        'upgrade'
    }
    else {
        $null
    }

    if ($null -ne $operation) {
        $arguments = @(
            $operation,
            '--id', $PackageId,
            '-e',
            '--source', 'winget',
            '--accept-source-agreements',
            '--accept-package-agreements'
        )

        if ($NonInteractivePackages) {
            $arguments += @('--silent', '--disable-interactivity')
        }

        if ($PSCmdlet.ShouldProcess($PackageId, "winget $operation")) {
            Invoke-External `
                -File (Require-WinGet) `
                -Arguments $arguments `
                -AllowedExitCodes @(0, -1978335189) | Out-Null
        }
    }

    Refresh-ProcessPath
    $resolved = Get-CommandPath -Name $CommandName
    Assert-True -Condition ($null -ne $resolved) `
        -Message "$CommandName is unavailable after the package operation."

    return $resolved
}

try {
    Write-Log -Message "Mode=$Mode Repository=$RepositoryFullName InstallPath=$InstallPath"

    Assert-True -Condition ($env:OS -eq 'Windows_NT') -Message 'Windows is required.'
    Assert-True -Condition ([Environment]::Is64BitOperatingSystem) -Message '64-bit Windows is required.'

    $version = $PSVersionTable.PSVersion
    $supported = ($version.Major -gt 5) -or
        ($version.Major -eq 5 -and $version.Minor -ge 1)

    Assert-True -Condition $supported -Message "PowerShell 5.1+ is required; actual=$version"

    if ($Mode -ne 'Doctor' -and $AllowDirtyWorktree) {
        throw '-AllowDirtyWorktree is permitted only in Doctor mode.'
    }

    $script:Capability.POWERSHELL_5_1_READY = 'YES'
    Write-Log -Level PASS -Message "PowerShell=$version"
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

    $repository = [IO.Path]::GetFullPath($RepositoryPath)
    $expectedPackage = Join-Path $repository 'tools\github-automation'
    $actualPackage = [IO.Path]::GetFullPath($PSScriptRoot)

    Assert-True -Condition (Test-Path -LiteralPath $repository -PathType Container) `
        -Message "Repository not found: $repository"
    Assert-True -Condition (Test-Path -LiteralPath (Join-Path $repository '.git')) `
        -Message "Git metadata not found: $repository"
    Assert-True -Condition ($actualPackage.TrimEnd('\') -ieq $expectedPackage.TrimEnd('\')) `
        -Message "Run the installer from $expectedPackage"

    if ($null -eq (Get-CommandPath -Name 'git') -and $Mode -ne 'Doctor') {
        Require-WinGet | Out-Null
    }

    $git = Ensure-WinGetPackage -PackageId 'Git.Git' -CommandName 'git'
    Write-Log -Level PASS -Message (Get-FirstLine (Invoke-External -File $git -Arguments @('--version')))
    $script:Capability.GIT_READY = 'YES'

    Push-Location -LiteralPath $repository
    try {
        $inside = Get-FirstLine (Invoke-External -File 'git' -Arguments @(
            'rev-parse', '--is-inside-work-tree'
        ))
        Assert-True -Condition ($inside -eq 'true') -Message 'Invalid Git worktree.'

        $remoteUrl = Get-FirstLine (Invoke-External -File 'git' -Arguments @(
            'remote', 'get-url', $RemoteName
        ))
        Assert-True -Condition (Test-RemoteIdentity -Url $remoteUrl) `
            -Message "Unexpected remote: $remoteUrl"

        $dirty = @(Invoke-External -File 'git' -Arguments @(
            'status', '--porcelain=v1', '--untracked-files=all'
        ))
        $dirtyAllowed = ($Mode -eq 'Doctor' -and $AllowDirtyWorktree)
        Assert-True -Condition ($dirty.Count -eq 0 -or $dirtyAllowed) `
            -Message 'Worktree is not clean.'

        if ($Mode -ne 'Doctor') {
            Invoke-External -File 'git' -Arguments @('fetch', '--prune', $RemoteName) | Out-Null
        }

        $head = Get-FirstLine (Invoke-External -File 'git' -Arguments @('rev-parse', 'HEAD'))
        $remoteMain = Get-FirstLine (Invoke-External -File 'git' -Arguments @(
            'rev-parse', "refs/remotes/$RemoteName/$MainBranch"
        ))
        $branch = Get-FirstLine (Invoke-External -File 'git' -Arguments @(
            'branch', '--show-current'
        ))

        Assert-True -Condition (-not [string]::IsNullOrWhiteSpace($branch)) `
            -Message 'Detached HEAD is prohibited.'

        if ($Mode -ne 'Doctor') {
            Assert-True -Condition ($branch -eq $MainBranch) `
                -Message "Install/Repair requires $MainBranch; actual=$branch"
            Assert-True -Condition ($head -eq $remoteMain) `
                -Message "HEAD must equal $RemoteName/$MainBranch. Local=$head Remote=$remoteMain"
        }

        $script:Capability.ASU_VCH_REPOSITORY_READY = 'YES'
        Write-Log -Level PASS -Message "Branch=$branch HEAD=$head RemoteMain=$remoteMain"
    }
    finally {
        Pop-Location
    }

    if ($Mode -eq 'Doctor') {
        if ($null -ne (Get-WinGet)) {
            Write-Log -Level PASS -Message 'WinGet ready.'
        }
        else {
            Write-Log -Level WARN -Message 'WinGet unavailable.'
        }
    }
    else {
        Require-WinGet | Out-Null
    }

    $gh = Ensure-WinGetPackage -PackageId 'GitHub.cli' -CommandName 'gh'
    Write-Log -Level PASS -Message (Get-FirstLine (Invoke-External -File $gh -Arguments @('--version')))
    $script:Capability.GITHUB_CLI_READY = 'YES'

    if (-not (Test-GitHubAuth)) {
        if ($Mode -eq 'Doctor' -or $SkipGitHubLogin) {
            $script:Pending.Add('Run: gh auth login --hostname github.com --git-protocol https --web')
            Write-Log -Level WARN -Message 'GitHub login pending.'
        }
        else {
            Write-Log -Message 'Starting GitHub browser login.'
            Invoke-Interactive -File 'gh' -Arguments @(
                'auth', 'login',
                '--hostname', 'github.com',
                '--git-protocol', 'https',
                '--web'
            )
        }
    }

    if (Test-GitHubAuth) {
        if ($Mode -ne 'Doctor') {
            Invoke-External -File 'gh' -Arguments @(
                'auth', 'setup-git', '--hostname', 'github.com'
            ) -Quiet | Out-Null
        }

        $script:Capability.GITHUB_AUTH_READY = 'YES'
        Write-Log -Level PASS -Message 'GitHub authentication ready.'

        $metadataText = Invoke-External -File 'gh' -Arguments @(
            'api', "repos/$RepositoryFullName"
        )
        $metadata = (($metadataText -join "`n") | ConvertFrom-Json)

        Assert-True -Condition ($metadata.full_name -eq $RepositoryFullName) `
            -Message "Repository identity mismatch: $($metadata.full_name)"
        Assert-True -Condition ($metadata.default_branch -eq $MainBranch) `
            -Message "Default branch mismatch: $($metadata.default_branch)"
        Assert-True -Condition ($metadata.permissions.push -eq $true) `
            -Message 'GitHub push/write permission is missing.'

        $script:Capability.GITHUB_REPOSITORY_WRITE_ACCESS = 'YES'
        Write-Log -Level PASS -Message 'GitHub repository write access ready.'
    }

    if ($SkipCodex) {
        $script:Pending.Add('Install Codex using the official Windows installer.')
        Write-Log -Level WARN -Message 'Codex installation skipped.'
    }
    else {
        $codex = Get-CommandPath -Name 'codex'

        if ($Mode -eq 'Doctor' -and $null -eq $codex) {
            $script:Pending.Add('Install Codex.')
            Write-Log -Level WARN -Message 'Codex unavailable.'
        }
        else {
            $installCodex = ($Mode -ne 'Doctor') -and (
                $null -eq $codex -or $Mode -eq 'Repair'
            )

            if ($installCodex) {
                $temporary = Join-Path $env:TEMP (
                    'asu-vch-codex-{0}' -f [guid]::NewGuid().ToString('N')
                )
                New-Item -ItemType Directory -Path $temporary | Out-Null
                $upstreamInstaller = Join-Path $temporary 'install-codex.ps1'

                try {
                    Invoke-WebRequest `
                        -UseBasicParsing `
                        -Uri 'https://chatgpt.com/codex/install.ps1' `
                        -OutFile $upstreamInstaller

                    $upstreamHash = (Get-FileHash `
                        -LiteralPath $upstreamInstaller `
                        -Algorithm SHA256).Hash.ToLowerInvariant()

                    Write-Log -Message "Codex installer SHA256=$upstreamHash"

                    if ($PSCmdlet.ShouldProcess('OpenAI Codex CLI', 'Install or repair')) {
                        Invoke-Interactive -File 'powershell.exe' -Arguments @(
                            '-NoProfile',
                            '-ExecutionPolicy', 'Bypass',
                            '-File', $upstreamInstaller
                        )
                    }
                }
                finally {
                    Remove-Item -LiteralPath $temporary -Recurse -Force -ErrorAction SilentlyContinue
                }
            }

            Refresh-ProcessPath
            $codex = Get-CommandPath -Name 'codex'
            Assert-True -Condition ($null -ne $codex) `
                -Message 'Codex is unavailable after installation.'

            Write-Log -Level PASS -Message (
                Get-FirstLine (Invoke-External -File $codex -Arguments @('--version'))
            )
            $script:Capability.CODEX_READY = 'YES'

            if (-not (Test-CodexAuth) -and $Mode -ne 'Doctor') {
                Write-Log -Message 'Starting Codex login; choose Sign in with ChatGPT.'
                Invoke-Interactive -File $codex -Arguments @('login')
            }

            if (Test-CodexAuth) {
                $script:Capability.CODEX_CHATGPT_AUTH_READY = 'YES'
                Write-Log -Level PASS -Message 'Codex ChatGPT login ready.'
            }
            else {
                $script:Pending.Add('Run: codex login')
                Write-Log -Level WARN -Message 'Codex login pending.'
            }
        }
    }

    $manifestPath = Join-Path $repository 'tools\github-automation\automation-manifest.json'
    Assert-True -Condition (Test-Path -LiteralPath $manifestPath -PathType Leaf) `
        -Message 'Manifest missing.'

    $manifest = Get-Content -LiteralPath $manifestPath -Raw -Encoding UTF8 | ConvertFrom-Json
    Assert-True -Condition ([int]$manifest.schemaVersion -eq 1) -Message 'Manifest schema mismatch.'
    Assert-True -Condition ($manifest.minimumPowerShell -eq '5.1') -Message 'Manifest PowerShell mismatch.'
    Assert-True -Condition ($manifest.repository -eq $RepositoryFullName) -Message 'Manifest repository mismatch.'
    Assert-True -Condition ($manifest.defaultInstallPath -eq 'C:\Tools\ASU-VCH') -Message 'Manifest install path mismatch.'
    Assert-True -Condition ($manifest.hashMode -eq 'utf8-lf-normalized') -Message 'Manifest hash mode mismatch.'

    $seen = @{}
    $validated = @()

    foreach ($entry in @($manifest.files)) {
        $relative = [string]$entry.path
        $installName = [string]$entry.installName
        $expectedHash = ([string]$entry.sha256).ToLowerInvariant()

        Assert-True -Condition ($relative -match '^tools/github-automation/[A-Za-z0-9._-]+$') `
            -Message "Unexpected manifest path: $relative"
        Assert-True -Condition ($relative -ne 'tools/github-automation/automation-manifest.json') `
            -Message 'Manifest self-hash is prohibited.'
        Assert-True -Condition ($installName -match '^[A-Za-z0-9._-]+$') `
            -Message "Unexpected install name: $installName"
        Assert-True -Condition ($expectedHash -match '^[0-9a-f]{64}$') `
            -Message "Invalid hash: $relative"
        Assert-True -Condition (-not $seen.ContainsKey($relative)) `
            -Message "Duplicate manifest path: $relative"

        $seen[$relative] = $true
        $source = Join-Path $repository ($relative -replace '/', '\')

        Assert-True -Condition (Test-Path -LiteralPath $source -PathType Leaf) `
            -Message "Source missing: $relative"
        Assert-True -Condition ((Get-NormalizedHash -Path $source) -eq $expectedHash) `
            -Message "Hash mismatch: $relative"

        if ([IO.Path]::GetExtension($source) -ieq '.ps1') {
            $tokens = $null
            $parseErrors = $null
            [Management.Automation.Language.Parser]::ParseFile(
                $source,
                [ref]$tokens,
                [ref]$parseErrors
            ) | Out-Null

            $messages = (@($parseErrors) | ForEach-Object { $_.Message }) -join '; '
            Assert-True -Condition (@($parseErrors).Count -eq 0) `
                -Message "PowerShell parser errors in $relative`: $messages"
        }

        $validated += [pscustomobject]@{
            Source = $source
            InstallName = $installName
            Sha256 = $expectedHash
        }
    }

    foreach ($required in @(
        'tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1',
        'tools/github-automation/CODEX-INSTRUCTIONS.md'
    )) {
        Assert-True -Condition ($seen.ContainsKey($required)) `
            -Message "Required manifest entry missing: $required"
    }

    Write-Log -Level PASS -Message "Manifest files=$($validated.Count)"

    if ($Mode -eq 'Doctor') {
        if (Test-Path -LiteralPath $InstallPath -PathType Container) {
            foreach ($file in $validated) {
                $installed = Join-Path $InstallPath $file.InstallName
                Assert-True -Condition (Test-Path -LiteralPath $installed -PathType Leaf) `
                    -Message "Installed helper missing: $($file.InstallName)"
                Assert-True -Condition ((Get-NormalizedHash -Path $installed) -eq $file.Sha256) `
                    -Message "Installed helper hash mismatch: $($file.InstallName)"
            }

            $script:Capability.ASU_VCH_LOCAL_HELPERS_READY = 'YES'
            Write-Log -Level PASS -Message 'Installed helper integrity PASS.'
        }
        else {
            $script:Pending.Add('Run installer in Install or Repair mode.')
            Write-Log -Level WARN -Message 'Local helpers are absent.'
        }
    }
    else {
        $parent = Split-Path -Parent $InstallPath
        New-Item -ItemType Directory -Path $parent -Force | Out-Null

        $staging = Join-Path $parent ('.stage.{0}' -f [guid]::NewGuid().ToString('N'))
        $backup = Join-Path $parent ('.backup.{0}' -f [guid]::NewGuid().ToString('N'))
        New-Item -ItemType Directory -Path $staging | Out-Null

        try {
            foreach ($file in $validated) {
                $destination = Join-Path $staging $file.InstallName
                Copy-Item -LiteralPath $file.Source -Destination $destination
                Assert-True -Condition ((Get-NormalizedHash -Path $destination) -eq $file.Sha256) `
                    -Message "Staged hash mismatch: $($file.InstallName)"
            }

            Copy-Item -LiteralPath $manifestPath `
                -Destination (Join-Path $staging 'automation-manifest.json')

            $hadExisting = Test-Path -LiteralPath $InstallPath
            if ($hadExisting) {
                Move-Item -LiteralPath $InstallPath -Destination $backup
            }

            try {
                Move-Item -LiteralPath $staging -Destination $InstallPath

                if ($script:Capability.GITHUB_AUTH_READY -eq 'YES') {
                    Invoke-External -File 'powershell.exe' -Arguments @(
                        '-NoProfile',
                        '-ExecutionPolicy', 'Bypass',
                        '-File', (Join-Path $InstallPath 'Invoke-ASUVCHBranchCleanup.ps1'),
                        '-Mode', 'Doctor',
                        '-RepositoryPath', $RepositoryPath,
                        '-RepositoryFullName', $RepositoryFullName,
                        '-RemoteName', $RemoteName,
                        '-MainBranch', $MainBranch
                    ) | Out-Null
                }
                else {
                    $script:Pending.Add('Run cleanup Doctor after GitHub login.')
                }

                if ($hadExisting -and (Test-Path -LiteralPath $backup)) {
                    Remove-Item -LiteralPath $backup -Recurse -Force
                }
            }
            catch {
                if (Test-Path -LiteralPath $InstallPath) {
                    Remove-Item -LiteralPath $InstallPath -Recurse -Force
                }
                if (Test-Path -LiteralPath $backup) {
                    Move-Item -LiteralPath $backup -Destination $InstallPath
                }
                throw
            }

            $script:Capability.ASU_VCH_LOCAL_HELPERS_READY = 'YES'
            Write-Log -Level PASS -Message "Helpers installed: $InstallPath"
        }
        finally {
            if (Test-Path -LiteralPath $staging) {
                Remove-Item -LiteralPath $staging -Recurse -Force
            }
        }
    }

    $ready = (
        $script:Capability.GIT_READY -eq 'YES' -and
        $script:Capability.GITHUB_CLI_READY -eq 'YES' -and
        $script:Capability.GITHUB_AUTH_READY -eq 'YES' -and
        $script:Capability.GITHUB_REPOSITORY_WRITE_ACCESS -eq 'YES' -and
        $script:Capability.CODEX_READY -eq 'YES' -and
        $script:Capability.CODEX_CHATGPT_AUTH_READY -eq 'YES' -and
        $script:Capability.ASU_VCH_REPOSITORY_READY -eq 'YES' -and
        $script:Capability.ASU_VCH_LOCAL_HELPERS_READY -eq 'YES'
    )

    $script:Capability.LOCAL_CODEX_AGENT_READY = if ($ready) { 'YES' } else { 'NO' }

    Write-Host ''
    Write-Host '=== ASU-VCH CAPABILITY MATRIX ===' -ForegroundColor Cyan
    $script:Capability.GetEnumerator() | ForEach-Object {
        Write-Host "$($_.Key)=$($_.Value)"
    }

    if ($script:Pending.Count -gt 0) {
        Write-Host ''
        Write-Host 'USER_ACTION_REQUIRED:' -ForegroundColor Yellow
        $script:Pending | ForEach-Object { Write-Host "- $_" }
    }

    Write-Host ''
    Write-Host "Log: $script:Log"
    Write-Host "Codex: Set-Location '$RepositoryPath'; codex"
    Write-Host "Instructions: $InstallPath\CODEX-INSTRUCTIONS.md"
    Write-Host "Cleanup: $InstallPath\Invoke-ASUVCHBranchCleanup.ps1"

    if ($ready) {
        Write-Log -Level PASS -Message 'Bootstrap PASS.'
        exit 0
    }

    Write-Log -Level WARN -Message 'Bootstrap completed with user action required.'
    exit 2
}
catch {
    Write-Log -Level FAIL -Message $_.Exception.Message
    Write-Host "Operation stopped fail-closed. Log: $script:Log" -ForegroundColor Red
    exit 1
}
