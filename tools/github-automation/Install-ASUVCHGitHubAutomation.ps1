#requires -Version 5.1
<#
.SYNOPSIS
    One-command local Git/GitHub/Codex bootstrap for ASU-VCH.

.DESCRIPTION
    Verifies a synchronized main branch, installs or verifies Git, GitHub CLI,
    Node.js/npm and Codex, performs safe interactive authentication, validates
    the repository manifest, tests staged helpers and atomically deploys them.

    Native executable success is determined only by exit code. Stdout/stderr are
    captured as data so Windows PowerShell 5.1 NativeCommandError behaviour does
    not convert successful native stderr output into a terminating exception.
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

    [ValidateSet('Auto', 'ChatGPT', 'ApiKey', 'Skip')]
    [string]$CodexAuthMode = 'Auto',

    [switch]$SkipCodex,
    [switch]$SkipGitHubLogin,
    [switch]$NoUpgrade,
    [switch]$AllowDirtyWorktree,
    [switch]$NonInteractivePackages
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$script:Pending = New-Object System.Collections.Generic.List[string]
$script:Capability = [ordered]@{
    POWERSHELL_5_1_READY = 'NO'
    WINGET_READY = 'NO'
    GIT_READY = 'NO'
    GITHUB_CLI_READY = 'NO'
    GITHUB_AUTH_READY = 'NO'
    GITHUB_REPOSITORY_WRITE_ACCESS = 'NO'
    NODEJS_READY = 'NO'
    NPM_READY = 'NO'
    CODEX_READY = 'NO'
    CODEX_AUTH_READY = 'NO'
    CODEX_AUTH_MODE = 'NONE'
    CODEX_CHATGPT_AUTH_READY = 'NO'
    CODEX_API_KEY_AUTH_READY = 'NO'
    CODEX_API_BALANCE = 'NOT_TESTED'
    ASU_VCH_REPOSITORY_READY = 'NO'
    ASU_VCH_LOCAL_HELPERS_READY = 'NO'
    LOCAL_CODEX_AGENT_READY = 'NO'
}

$logRoot = if ([string]::IsNullOrWhiteSpace($env:LOCALAPPDATA)) { $env:TEMP } else { $env:LOCALAPPDATA }
$logDirectory = Join-Path $logRoot 'ASU-VCH\Logs'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$script:Log = Join-Path $logDirectory ('bootstrap-{0}.log' -f (Get-Date -Format 'yyyyMMdd-HHmmss'))

function Get-FirstLine {
    param($Lines)
    $items = @($Lines)
    if (@($items).Count -eq 0) { return $null }
    return ([string]$items[0]).Trim()
}

function Write-Log {
    param(
        [Parameter(Mandatory = $true)][string]$Message,
        [ValidateSet('INFO', 'PASS', 'WARN', 'FAIL')][string]$Level = 'INFO'
    )

    $safe = $Message `
        -replace '(?i)(authorization:\s*bearer\s+)[^\s]+', '$1[REDACTED]' `
        -replace '(?i)(sk-[A-Za-z0-9_-]{8,})', '[REDACTED]' `
        -replace '(?i)(gh[opusr]_[A-Za-z0-9_]{8,})', '[REDACTED]' `
        -replace '(?i)(token|api[_ -]?key|password|cookie|device[_ -]?code)\s*[:=]\s*[^\s;]+', '$1=[REDACTED]'

    $line = '{0} [{1}] {2}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Level, $safe
    Add-Content -LiteralPath $script:Log -Value $line -Encoding UTF8
    $color = switch ($Level) { 'PASS' { 'Green' } 'WARN' { 'Yellow' } 'FAIL' { 'Red' } default { 'Gray' } }
    Write-Host $line -ForegroundColor $color
}

function Assert-True {
    param([Parameter(Mandatory = $true)][bool]$Condition, [Parameter(Mandatory = $true)][string]$Message)
    if (-not $Condition) { throw $Message }
}

function Get-CommandPath {
    param([Parameter(Mandatory = $true)][string]$Name)
    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($null -eq $command) { return $null }
    return [string]$command.Source
}


function Get-PreferredCommandPath {
    param([Parameter(Mandatory = $true)][string[]]$Names)
    foreach ($name in @($Names)) {
        $path = Get-CommandPath -Name $name
        if ($null -ne $path) { return $path }
    }
    return $null
}

function ConvertTo-WindowsArgument {
    param([AllowNull()][string]$Value)
    if ($null -eq $Value -or $Value.Length -eq 0) { return '""' }
    if ($Value -notmatch '[\s"]') { return $Value }

    $builder = New-Object Text.StringBuilder
    [void]$builder.Append('"')
    $slashes = 0
    foreach ($character in $Value.ToCharArray()) {
        if ($character -eq '\') {
            $slashes++
            continue
        }
        if ($character -eq '"') {
            [void]$builder.Append(('\' * (($slashes * 2) + 1)))
            [void]$builder.Append('"')
            $slashes = 0
            continue
        }
        if ($slashes -gt 0) {
            [void]$builder.Append(('\' * $slashes))
            $slashes = 0
        }
        [void]$builder.Append($character)
    }
    if ($slashes -gt 0) { [void]$builder.Append(('\' * ($slashes * 2))) }
    [void]$builder.Append('"')
    return $builder.ToString()
}

function New-NativeStartInfo {
    param(
        [Parameter(Mandatory = $true)][string]$File,
        [string[]]$Arguments = @(),
        [switch]$Redirect,
        [switch]$RedirectInput
    )

    $resolved = $File
    if (-not [IO.Path]::IsPathRooted($resolved)) {
        $candidate = Get-CommandPath -Name $resolved
        if ($null -ne $candidate) { $resolved = $candidate }
    }
    Assert-True -Condition (-not [string]::IsNullOrWhiteSpace($resolved)) -Message "Command is unavailable: $File"

    $argumentText = (@($Arguments | ForEach-Object { ConvertTo-WindowsArgument -Value ([string]$_) })) -join ' '
    $extension = [IO.Path]::GetExtension($resolved)
    $startInfo = New-Object Diagnostics.ProcessStartInfo

    if ($extension -ieq '.cmd' -or $extension -ieq '.bat') {
        $comspec = if ([string]::IsNullOrWhiteSpace($env:ComSpec)) { 'cmd.exe' } else { $env:ComSpec }
        $commandText = '"{0}"' -f $resolved
        if (-not [string]::IsNullOrWhiteSpace($argumentText)) { $commandText = "$commandText $argumentText" }
        $startInfo.FileName = $comspec
        $startInfo.Arguments = '/d /s /c "{0}"' -f $commandText
    }
    else {
        $startInfo.FileName = $resolved
        $startInfo.Arguments = $argumentText
    }

    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = [bool]$Redirect
    $startInfo.RedirectStandardOutput = [bool]$Redirect
    $startInfo.RedirectStandardError = [bool]$Redirect
    $startInfo.RedirectStandardInput = [bool]$RedirectInput
    return $startInfo
}

function Convert-TextToLines {
    param([AllowNull()][string]$Text)
    if ([string]::IsNullOrEmpty($Text)) { return @() }
    $normalized = $Text.Replace("`r`n", "`n").Replace("`r", "`n")
    $lines = @($normalized -split "`n")
    while (@($lines).Count -gt 0 -and [string]::IsNullOrEmpty([string]$lines[@($lines).Count - 1])) {
        if (@($lines).Count -eq 1) { $lines = @(); break }
        $lines = @($lines[0..(@($lines).Count - 2)])
    }
    return @($lines)
}

function Invoke-NativeCaptured {
    param(
        [Parameter(Mandatory = $true)][string]$File,
        [string[]]$Arguments = @(),
        [AllowNull()][string]$StandardInput,
        [switch]$SensitiveInput
    )

    $startInfo = New-NativeStartInfo -File $File -Arguments $Arguments -Redirect -RedirectInput:($PSBoundParameters.ContainsKey('StandardInput'))
    $process = New-Object Diagnostics.Process
    $process.StartInfo = $startInfo
    try {
        Assert-True -Condition $process.Start() -Message "Could not start command: $File"
        $stdoutTask = $process.StandardOutput.ReadToEndAsync()
        $stderrTask = $process.StandardError.ReadToEndAsync()
        if ($PSBoundParameters.ContainsKey('StandardInput')) {
            $process.StandardInput.Write($StandardInput)
            $process.StandardInput.WriteLine()
            $process.StandardInput.Close()
        }
        $process.WaitForExit()
        $stdout = $stdoutTask.Result
        $stderr = $stderrTask.Result
        return [pscustomobject]@{
            ExitCode = [int]$process.ExitCode
            StdOut = @(Convert-TextToLines -Text $stdout)
            StdErr = @(Convert-TextToLines -Text $stderr)
        }
    }
    finally {
        $process.Dispose()
        if ($SensitiveInput) { Remove-Variable StandardInput -ErrorAction SilentlyContinue }
    }
}

function Invoke-NativeChecked {
    param(
        [Parameter(Mandatory = $true)][string]$File,
        [string[]]$Arguments = @(),
        [int[]]$AllowedExitCodes = @(0),
        [switch]$Quiet
    )

    $result = Invoke-NativeCaptured -File $File -Arguments $Arguments
    if ($AllowedExitCodes -notcontains $result.ExitCode) {
        $details = if ($Quiet) { '[output suppressed]' } else { (@($result.StdOut) + @($result.StdErr)) -join [Environment]::NewLine }
        throw ('Command failed ({0}): {1} {2}{3}{4}' -f $result.ExitCode, $File, ($Arguments -join ' '), [Environment]::NewLine, $details)
    }
    return $result
}

function Invoke-NativeInteractive {
    param([Parameter(Mandatory = $true)][string]$File, [string[]]$Arguments = @())
    $startInfo = New-NativeStartInfo -File $File -Arguments $Arguments
    $process = New-Object Diagnostics.Process
    $process.StartInfo = $startInfo
    try {
        Assert-True -Condition $process.Start() -Message "Could not start interactive command: $File"
        $process.WaitForExit()
        return [int]$process.ExitCode
    }
    finally { $process.Dispose() }
}

function Refresh-ProcessPath {
    $paths = @(
        [Environment]::GetEnvironmentVariable('Path', 'Machine'),
        [Environment]::GetEnvironmentVariable('Path', 'User')
    ) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
    $env:Path = (@($paths) -join ';')
}

function Add-ProcessPath {
    param([Parameter(Mandatory = $true)][string]$Path)
    if (-not [string]::IsNullOrWhiteSpace($Path) -and (Test-Path -LiteralPath $Path -PathType Container)) {
        $segments = @($env:Path -split ';')
        if ($segments -inotcontains $Path) { $env:Path = "$Path;$env:Path" }
    }
}

function Get-NormalizedHash {
    param([Parameter(Mandatory = $true)][string]$Path)
    $utf8 = New-Object Text.UTF8Encoding($false, $true)
    $text = $utf8.GetString([IO.File]::ReadAllBytes($Path)).Replace("`r`n", "`n").Replace("`r", "`n")
    $sha = [Security.Cryptography.SHA256]::Create()
    try { $bytes = $sha.ComputeHash($utf8.GetBytes($text)) } finally { $sha.Dispose() }
    return (($bytes | ForEach-Object { $_.ToString('x2') }) -join '')
}

function Test-RemoteIdentity {
    param([Parameter(Mandatory = $true)][string]$Url)
    $repository = $RepositoryFullName.Trim('/').ToLowerInvariant()
    $actual = $Url.Trim().TrimEnd('/').ToLowerInvariant()
    return @(
        "https://github.com/$repository",
        "https://github.com/$repository.git",
        "git@github.com:$repository.git",
        "ssh://git@github.com/$repository.git"
    ) -contains $actual
}

function Get-WinGet {
    Refresh-ProcessPath
    $winget = Get-CommandPath -Name 'winget'
    if ($null -eq $winget) { return $null }
    $probe = Invoke-NativeCaptured -File $winget -Arguments @('--info')
    if ($probe.ExitCode -ne 0) { return $null }
    $script:Capability.WINGET_READY = 'YES'
    return $winget
}

function Require-WinGet {
    $winget = Get-WinGet
    if ($null -ne $winget) { Write-Log -Level PASS -Message "WinGet=$winget"; return $winget }
    Write-Log -Level WARN -Message 'WinGet is unavailable; Microsoft App Installer is required.'
    Write-Host 'Official page: https://apps.microsoft.com/detail/9NBLGGH4NNS1'
    try { if ($PSCmdlet.ShouldProcess('Microsoft App Installer page', 'Open')) { Start-Process 'https://apps.microsoft.com/detail/9NBLGGH4NNS1' } } catch { }
    throw 'Install Microsoft App Installer and rerun the same command.'
}

function Ensure-WinGetPackage {
    param(
        [Parameter(Mandatory = $true)][string]$PackageId,
        [Parameter(Mandatory = $true)][string]$CommandName
    )

    $current = Get-CommandPath -Name $CommandName
    if ($Mode -eq 'Doctor') {
        Assert-True -Condition ($null -ne $current) -Message "$CommandName is unavailable in Doctor mode."
        return $current
    }

    $operation = if ($null -eq $current) { 'install' } elseif ($Mode -eq 'Repair' -and -not $NoUpgrade) { 'upgrade' } else { $null }
    if ($null -ne $operation) {
        $arguments = @($operation, '--id', $PackageId, '-e', '--source', 'winget', '--accept-source-agreements', '--accept-package-agreements')
        if ($NonInteractivePackages) { $arguments += @('--silent', '--disable-interactivity') }
        if ($PSCmdlet.ShouldProcess($PackageId, "winget $operation")) {
            $result = Invoke-NativeCaptured -File (Require-WinGet) -Arguments $arguments
            Assert-True -Condition (@(0, -1978335189) -contains $result.ExitCode) -Message "WinGet $operation failed for $PackageId. ExitCode=$($result.ExitCode)"
        }
    }

    Refresh-ProcessPath
    $resolved = Get-CommandPath -Name $CommandName
    Assert-True -Condition ($null -ne $resolved) -Message "$CommandName is unavailable after package operation."
    return $resolved
}

function Get-GitHubAuthReady {
    param([Parameter(Mandatory = $true)][string]$Gh)
    $result = Invoke-NativeCaptured -File $Gh -Arguments @('auth', 'status', '--hostname', 'github.com', '--active')
    return ($result.ExitCode -eq 0)
}

function Get-CodexAuthState {
    param([Parameter(Mandatory = $true)][string]$Codex)
    $result = Invoke-NativeCaptured -File $Codex -Arguments @('login', 'status')
    if ($result.ExitCode -ne 0) {
        return [pscustomobject]@{ Ready = $false; Mode = 'NONE' }
    }

    $text = ((@($result.StdOut) + @($result.StdErr)) -join "`n")
    $mode = if ($text -match '(?i)api\s*key') { 'API_KEY' } elseif ($text -match '(?i)chatgpt|chat\s*gpt') { 'CHATGPT' } else { 'UNKNOWN' }
    $text = $null
    return [pscustomobject]@{ Ready = $true; Mode = $mode }
}

function Set-CodexCapability {
    param([Parameter(Mandatory = $true)]$State)
    $script:Capability.CODEX_AUTH_READY = if ($State.Ready) { 'YES' } else { 'NO' }
    $script:Capability.CODEX_AUTH_MODE = [string]$State.Mode
    $script:Capability.CODEX_CHATGPT_AUTH_READY = if ($State.Ready -and $State.Mode -eq 'CHATGPT') { 'YES' } else { 'NO' }
    $script:Capability.CODEX_API_KEY_AUTH_READY = if ($State.Ready -and $State.Mode -eq 'API_KEY') { 'YES' } else { 'NO' }
}

function Invoke-CodexApiKeyLogin {
    param([Parameter(Mandatory = $true)][string]$Codex)
    Write-Host 'API usage is billed separately from ChatGPT subscriptions.' -ForegroundColor Yellow
    $secure = Read-Host -Prompt 'Enter OpenAI API key (input hidden)' -AsSecureString
    $bstr = [IntPtr]::Zero
    $plain = $null
    try {
        $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
        $plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
        Assert-True -Condition (-not [string]::IsNullOrWhiteSpace($plain)) -Message 'API key was not entered.'
        $result = Invoke-NativeCaptured -File $Codex -Arguments @('login', '--with-api-key') -StandardInput $plain -SensitiveInput
        Assert-True -Condition ($result.ExitCode -eq 0) -Message "Codex API-key login failed. ExitCode=$($result.ExitCode)"
    }
    finally {
        if ($bstr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) }
        $plain = $null
        Remove-Variable secure -ErrorAction SilentlyContinue
    }
}

function Show-CapabilityMatrix {
    Write-Host ''
    Write-Host '=== ASU-VCH CAPABILITY MATRIX ===' -ForegroundColor Cyan
    $script:Capability.GetEnumerator() | ForEach-Object { Write-Host "$($_.Key)=$($_.Value)" }
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
}

try {
    Write-Log -Message "Mode=$Mode Repository=$RepositoryFullName InstallPath=$InstallPath CodexAuthMode=$CodexAuthMode"
    Assert-True -Condition ($env:OS -eq 'Windows_NT') -Message 'Windows is required.'
    Assert-True -Condition ([Environment]::Is64BitOperatingSystem) -Message '64-bit Windows is required.'
    $version = $PSVersionTable.PSVersion
    Assert-True -Condition (($version.Major -gt 5) -or ($version.Major -eq 5 -and $version.Minor -ge 1)) -Message "PowerShell 5.1+ is required; actual=$version"
    $script:Capability.POWERSHELL_5_1_READY = 'YES'
    Write-Log -Level PASS -Message "PowerShell=$version"

    if ($Mode -ne 'Doctor' -and $AllowDirtyWorktree) { throw '-AllowDirtyWorktree is permitted only in Doctor mode.' }
    if ($SkipCodex -and $CodexAuthMode -ne 'Auto' -and $CodexAuthMode -ne 'Skip') { throw '-SkipCodex conflicts with explicit CodexAuthMode.' }
    if ($SkipCodex) { $CodexAuthMode = 'Skip' }

    $repository = [IO.Path]::GetFullPath($RepositoryPath)
    $expectedPackage = Join-Path $repository 'tools\github-automation'
    $actualPackage = [IO.Path]::GetFullPath($PSScriptRoot)
    Assert-True -Condition (Test-Path -LiteralPath $repository -PathType Container) -Message "Repository not found: $repository"
    Assert-True -Condition (Test-Path -LiteralPath (Join-Path $repository '.git')) -Message "Git metadata not found: $repository"
    Assert-True -Condition ($actualPackage.TrimEnd('\') -ieq $expectedPackage.TrimEnd('\')) -Message "Run the installer from $expectedPackage"

    if ($null -eq (Get-CommandPath -Name 'git') -and $Mode -ne 'Doctor') { Require-WinGet | Out-Null }
    $git = Ensure-WinGetPackage -PackageId 'Git.Git' -CommandName 'git'
    $gitVersion = Invoke-NativeChecked -File $git -Arguments @('--version')
    Write-Log -Level PASS -Message (Get-FirstLine -Lines $gitVersion.StdOut)
    $script:Capability.GIT_READY = 'YES'

    Push-Location -LiteralPath $repository
    try {
        $inside = Get-FirstLine -Lines ((Invoke-NativeChecked -File $git -Arguments @('rev-parse', '--is-inside-work-tree')).StdOut)
        Assert-True -Condition ($inside -eq 'true') -Message 'Invalid Git worktree.'
        $remoteUrl = Get-FirstLine -Lines ((Invoke-NativeChecked -File $git -Arguments @('remote', 'get-url', $RemoteName)).StdOut)
        Assert-True -Condition (Test-RemoteIdentity -Url $remoteUrl) -Message "Unexpected remote: $remoteUrl"
        $dirty = @((Invoke-NativeChecked -File $git -Arguments @('status', '--porcelain=v1', '--untracked-files=all')).StdOut)
        $dirtyAllowed = ($Mode -eq 'Doctor' -and $AllowDirtyWorktree)
        Assert-True -Condition (@($dirty).Count -eq 0 -or $dirtyAllowed) -Message 'Worktree is not clean.'
        if ($Mode -ne 'Doctor') { Invoke-NativeChecked -File $git -Arguments @('fetch', '--prune', $RemoteName) | Out-Null }
        $head = Get-FirstLine -Lines ((Invoke-NativeChecked -File $git -Arguments @('rev-parse', 'HEAD')).StdOut)
        $remoteMain = Get-FirstLine -Lines ((Invoke-NativeChecked -File $git -Arguments @('rev-parse', "refs/remotes/$RemoteName/$MainBranch")).StdOut)
        $branch = Get-FirstLine -Lines ((Invoke-NativeChecked -File $git -Arguments @('branch', '--show-current')).StdOut)
        Assert-True -Condition (-not [string]::IsNullOrWhiteSpace($branch)) -Message 'Detached HEAD is prohibited.'
        if ($Mode -ne 'Doctor') {
            Assert-True -Condition ($branch -eq $MainBranch) -Message "Install/Repair requires $MainBranch; actual=$branch"
            Assert-True -Condition ($head -eq $remoteMain) -Message "HEAD must equal $RemoteName/$MainBranch. Local=$head Remote=$remoteMain"
        }
        $script:Capability.ASU_VCH_REPOSITORY_READY = 'YES'
        Write-Log -Level PASS -Message "Branch=$branch HEAD=$head RemoteMain=$remoteMain"
    }
    finally { Pop-Location }

    if ($Mode -eq 'Doctor') { if ($null -ne (Get-WinGet)) { Write-Log -Level PASS -Message 'WinGet ready.' } else { Write-Log -Level WARN -Message 'WinGet unavailable.' } } else { Require-WinGet | Out-Null }

    $gh = Ensure-WinGetPackage -PackageId 'GitHub.cli' -CommandName 'gh'
    Write-Log -Level PASS -Message (Get-FirstLine -Lines ((Invoke-NativeChecked -File $gh -Arguments @('--version')).StdOut))
    $script:Capability.GITHUB_CLI_READY = 'YES'

    $githubReady = Get-GitHubAuthReady -Gh $gh
    if (-not $githubReady -and $Mode -ne 'Doctor' -and -not $SkipGitHubLogin) {
        Write-Log -Message 'Starting GitHub browser login.'
        $loginCode = Invoke-NativeInteractive -File $gh -Arguments @('auth', 'login', '--hostname', 'github.com', '--git-protocol', 'https', '--web')
        Assert-True -Condition ($loginCode -eq 0) -Message "GitHub browser login failed. ExitCode=$loginCode"
        $githubReady = Get-GitHubAuthReady -Gh $gh
    }

    if (-not $githubReady) {
        $script:Pending.Add('Run: gh auth login --hostname github.com --git-protocol https --web')
        Write-Log -Level WARN -Message 'GitHub authentication pending.'
    }
    else {
        if ($Mode -ne 'Doctor') { Invoke-NativeChecked -File $gh -Arguments @('auth', 'setup-git', '--hostname', 'github.com') -Quiet | Out-Null }
        $script:Capability.GITHUB_AUTH_READY = 'YES'
        Write-Log -Level PASS -Message 'GitHub authentication ready.'
        $metadataResult = Invoke-NativeChecked -File $gh -Arguments @('api', "repos/$RepositoryFullName") -Quiet
        $metadata = ((@($metadataResult.StdOut) -join "`n") | ConvertFrom-Json)
        Assert-True -Condition ($metadata.full_name -eq $RepositoryFullName) -Message "Repository identity mismatch: $($metadata.full_name)"
        Assert-True -Condition ($metadata.default_branch -eq $MainBranch) -Message "Default branch mismatch: $($metadata.default_branch)"
        Assert-True -Condition ($metadata.permissions.push -eq $true) -Message 'GitHub push/write permission is missing.'
        $script:Capability.GITHUB_REPOSITORY_WRITE_ACCESS = 'YES'
        Write-Log -Level PASS -Message 'GitHub repository write access ready.'
    }

    $codex = $null
    if ($CodexAuthMode -eq 'Skip') {
        $script:Pending.Add('Codex was skipped. Rerun with -CodexAuthMode Auto, ChatGPT or ApiKey.')
        Write-Log -Level WARN -Message 'Codex installation/authentication skipped.'
    }
    else {
        $node = Get-PreferredCommandPath -Names @('node.exe', 'node')
        $npm = Get-PreferredCommandPath -Names @('npm.cmd', 'npm.exe', 'npm')
        if ($null -eq $node -or $null -eq $npm) {
            if ($Mode -eq 'Doctor') { throw 'Node.js/npm is unavailable in Doctor mode.' }
            $operation = if ($Mode -eq 'Repair' -and -not $NoUpgrade) { 'upgrade' } else { 'install' }
            $arguments = @($operation, '--id', 'OpenJS.NodeJS.LTS', '-e', '--source', 'winget', '--accept-source-agreements', '--accept-package-agreements')
            if ($NonInteractivePackages) { $arguments += @('--silent', '--disable-interactivity') }
            if ($PSCmdlet.ShouldProcess('OpenJS.NodeJS.LTS', "winget $operation")) {
                $nodeInstall = Invoke-NativeCaptured -File (Require-WinGet) -Arguments $arguments
                Assert-True -Condition (@(0, -1978335189) -contains $nodeInstall.ExitCode) -Message "Node.js LTS package operation failed. ExitCode=$($nodeInstall.ExitCode)"
            }
            Refresh-ProcessPath
            $node = Get-PreferredCommandPath -Names @('node.exe', 'node')
            $npm = Get-PreferredCommandPath -Names @('npm.cmd', 'npm.exe', 'npm')
        }
        Assert-True -Condition ($null -ne $node) -Message 'Node.js is unavailable.'
        Assert-True -Condition ($null -ne $npm) -Message 'npm is unavailable.'
        Write-Log -Level PASS -Message ("Node.js=" + (Get-FirstLine -Lines ((Invoke-NativeChecked -File $node -Arguments @('--version')).StdOut)))
        Write-Log -Level PASS -Message ("npm=" + (Get-FirstLine -Lines ((Invoke-NativeChecked -File $npm -Arguments @('--version')).StdOut)))
        $script:Capability.NODEJS_READY = 'YES'
        $script:Capability.NPM_READY = 'YES'

        $codex = Get-PreferredCommandPath -Names @('codex.cmd', 'codex.exe', 'codex')
        $installCodex = ($Mode -ne 'Doctor') -and ($null -eq $codex -or ($Mode -eq 'Repair' -and -not $NoUpgrade))
        if ($installCodex -and $PSCmdlet.ShouldProcess('@openai/codex@latest', 'npm install --global')) {
            $npmInstall = Invoke-NativeCaptured -File $npm -Arguments @('install', '--global', '@openai/codex@latest')
            Assert-True -Condition ($npmInstall.ExitCode -eq 0) -Message "Codex npm installation failed. ExitCode=$($npmInstall.ExitCode)"
        }
        Refresh-ProcessPath
        $prefixResult = Invoke-NativeCaptured -File $npm -Arguments @('prefix', '--global')
        if ($prefixResult.ExitCode -eq 0) { Add-ProcessPath -Path (Get-FirstLine -Lines $prefixResult.StdOut) }
        $codex = Get-PreferredCommandPath -Names @('codex.cmd', 'codex.exe', 'codex')
        Assert-True -Condition ($null -ne $codex) -Message 'Codex is unavailable after npm installation.'
        Write-Log -Level PASS -Message (Get-FirstLine -Lines ((Invoke-NativeChecked -File $codex -Arguments @('--version')).StdOut))
        $script:Capability.CODEX_READY = 'YES'

        $codexState = Get-CodexAuthState -Codex $codex
        if (-not $codexState.Ready -and $Mode -ne 'Doctor') {
            $selected = $CodexAuthMode
            if ($selected -eq 'Auto') {
                if ($NonInteractivePackages) {
                    $selected = 'Skip'
                }
                else {
                    Write-Host 'Codex authentication: [1] ChatGPT (recommended), [2] API key (separate billing), [3] defer'
                    $choice = Read-Host 'Choose 1, 2 or 3'
                    $selected = switch ($choice) { '2' { 'ApiKey' } '3' { 'Skip' } default { 'ChatGPT' } }
                }
            }

            if ($selected -eq 'ChatGPT') {
                Write-Log -Message 'Starting Codex ChatGPT login.'
                $loginCode = Invoke-NativeInteractive -File $codex -Arguments @('login')
                if ($loginCode -ne 0) { $script:Pending.Add('Complete OpenAI account verification or rerun with -CodexAuthMode ApiKey.') }
            }
            elseif ($selected -eq 'ApiKey') {
                Invoke-CodexApiKeyLogin -Codex $codex
            }
            else {
                $script:Pending.Add('Run installer with -CodexAuthMode ChatGPT or -CodexAuthMode ApiKey.')
            }
            $codexState = Get-CodexAuthState -Codex $codex
        }

        Set-CodexCapability -State $codexState
        if ($codexState.Ready) { Write-Log -Level PASS -Message "Codex authentication ready. Mode=$($codexState.Mode)" } else { Write-Log -Level WARN -Message 'Codex authentication pending.' }
    }

    $prerequisitesReady = (
        $script:Capability.GITHUB_AUTH_READY -eq 'YES' -and
        $script:Capability.GITHUB_REPOSITORY_WRITE_ACCESS -eq 'YES' -and
        $script:Capability.CODEX_READY -eq 'YES' -and
        $script:Capability.CODEX_AUTH_READY -eq 'YES'
    )

    if (-not $prerequisitesReady) {
        Show-CapabilityMatrix
        Write-Log -Level WARN -Message 'Bootstrap completed with user action required.'
        exit 2
    }

    $manifestPath = Join-Path $repository 'tools\github-automation\automation-manifest.json'
    Assert-True -Condition (Test-Path -LiteralPath $manifestPath -PathType Leaf) -Message 'Manifest missing.'
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
        Assert-True -Condition ($relative -match '^tools/github-automation/[A-Za-z0-9._-]+$') -Message "Unexpected manifest path: $relative"
        Assert-True -Condition ($relative -ne 'tools/github-automation/automation-manifest.json') -Message 'Manifest self-hash is prohibited.'
        Assert-True -Condition ($installName -match '^[A-Za-z0-9._-]+$') -Message "Unexpected install name: $installName"
        Assert-True -Condition ($expectedHash -match '^[0-9a-f]{64}$') -Message "Invalid hash: $relative"
        Assert-True -Condition (-not $seen.ContainsKey($relative)) -Message "Duplicate manifest path: $relative"
        $seen[$relative] = $true
        $source = Join-Path $repository ($relative -replace '/', '\')
        Assert-True -Condition (Test-Path -LiteralPath $source -PathType Leaf) -Message "Source missing: $relative"
        Assert-True -Condition ((Get-NormalizedHash -Path $source) -eq $expectedHash) -Message "Hash mismatch: $relative"
        if ([IO.Path]::GetExtension($source) -ieq '.ps1') {
            $tokens = $null; $parseErrors = $null
            [Management.Automation.Language.Parser]::ParseFile($source, [ref]$tokens, [ref]$parseErrors) | Out-Null
            $errors = @($parseErrors)
            Assert-True -Condition (@($errors).Count -eq 0) -Message "PowerShell parser errors in $relative`: $((@($errors | ForEach-Object { $_.Message })) -join '; ')"
        }
        $validated += [pscustomobject]@{ Source = $source; InstallName = $installName; Sha256 = $expectedHash }
    }
    foreach ($required in @('tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1', 'tools/github-automation/CODEX-INSTRUCTIONS.md')) {
        Assert-True -Condition ($seen.ContainsKey($required)) -Message "Required manifest entry missing: $required"
    }
    Write-Log -Level PASS -Message "Manifest files=$(@($validated).Count)"

    if ($Mode -eq 'Doctor') {
        Assert-True -Condition (Test-Path -LiteralPath $InstallPath -PathType Container) -Message 'Local helpers are absent.'
        foreach ($file in @($validated)) {
            $installed = Join-Path $InstallPath $file.InstallName
            Assert-True -Condition (Test-Path -LiteralPath $installed -PathType Leaf) -Message "Installed helper missing: $($file.InstallName)"
            Assert-True -Condition ((Get-NormalizedHash -Path $installed) -eq $file.Sha256) -Message "Installed helper hash mismatch: $($file.InstallName)"
        }
        $script:Capability.ASU_VCH_LOCAL_HELPERS_READY = 'YES'
        Write-Log -Level PASS -Message 'Installed helper integrity PASS.'
    }
    else {
        $parent = Split-Path -Parent $InstallPath
        New-Item -ItemType Directory -Path $parent -Force | Out-Null
        $staging = Join-Path $parent ('.stage.{0}' -f [guid]::NewGuid().ToString('N'))
        $backup = Join-Path $parent ('.backup.{0}' -f [guid]::NewGuid().ToString('N'))
        New-Item -ItemType Directory -Path $staging | Out-Null
        try {
            foreach ($file in @($validated)) {
                $destination = Join-Path $staging $file.InstallName
                Copy-Item -LiteralPath $file.Source -Destination $destination
                Assert-True -Condition ((Get-NormalizedHash -Path $destination) -eq $file.Sha256) -Message "Staged hash mismatch: $($file.InstallName)"
            }
            Copy-Item -LiteralPath $manifestPath -Destination (Join-Path $staging 'automation-manifest.json')

            $doctorResult = Invoke-NativeCaptured -File 'powershell.exe' -Arguments @(
                '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', (Join-Path $staging 'Invoke-ASUVCHBranchCleanup.ps1'),
                '-Mode', 'Doctor', '-RepositoryPath', $RepositoryPath, '-RepositoryFullName', $RepositoryFullName,
                '-RemoteName', $RemoteName, '-MainBranch', $MainBranch
            )
            Assert-True -Condition ($doctorResult.ExitCode -eq 0) -Message "Staged Cleanup Doctor failed. ExitCode=$($doctorResult.ExitCode)"
            $doctorText = (@($doctorResult.StdOut) + @($doctorResult.StdErr)) -join "`n"
            Assert-True -Condition ($doctorText -match 'DOCTOR_STATUS=PASS') -Message 'Staged Cleanup Doctor did not report PASS.'

            $hadExisting = Test-Path -LiteralPath $InstallPath
            if ($hadExisting) { Move-Item -LiteralPath $InstallPath -Destination $backup }
            try {
                Move-Item -LiteralPath $staging -Destination $InstallPath
                foreach ($file in @($validated)) {
                    $installed = Join-Path $InstallPath $file.InstallName
                    Assert-True -Condition ((Get-NormalizedHash -Path $installed) -eq $file.Sha256) -Message "Installed helper hash mismatch: $($file.InstallName)"
                }
                if ($hadExisting -and (Test-Path -LiteralPath $backup)) { Remove-Item -LiteralPath $backup -Recurse -Force }
            }
            catch {
                if (Test-Path -LiteralPath $InstallPath) { Remove-Item -LiteralPath $InstallPath -Recurse -Force }
                if (Test-Path -LiteralPath $backup) { Move-Item -LiteralPath $backup -Destination $InstallPath }
                throw
            }
            $script:Capability.ASU_VCH_LOCAL_HELPERS_READY = 'YES'
            Write-Log -Level PASS -Message "Helpers installed: $InstallPath"
        }
        finally { if (Test-Path -LiteralPath $staging) { Remove-Item -LiteralPath $staging -Recurse -Force } }
    }

    $ready = (
        $script:Capability.GIT_READY -eq 'YES' -and
        $script:Capability.GITHUB_CLI_READY -eq 'YES' -and
        $script:Capability.GITHUB_AUTH_READY -eq 'YES' -and
        $script:Capability.GITHUB_REPOSITORY_WRITE_ACCESS -eq 'YES' -and
        $script:Capability.CODEX_READY -eq 'YES' -and
        $script:Capability.CODEX_AUTH_READY -eq 'YES' -and
        $script:Capability.ASU_VCH_REPOSITORY_READY -eq 'YES' -and
        $script:Capability.ASU_VCH_LOCAL_HELPERS_READY -eq 'YES'
    )
    $script:Capability.LOCAL_CODEX_AGENT_READY = if ($ready) { 'YES' } else { 'NO' }
    Show-CapabilityMatrix
    if ($ready) { Write-Log -Level PASS -Message 'Bootstrap PASS.'; exit 0 }
    Write-Log -Level WARN -Message 'Bootstrap completed with user action required.'
    exit 2
}
catch {
    Write-Log -Level FAIL -Message $_.Exception.Message
    Write-Host "Operation stopped fail-closed. Log: $script:Log" -ForegroundColor Red
    exit 1
}
