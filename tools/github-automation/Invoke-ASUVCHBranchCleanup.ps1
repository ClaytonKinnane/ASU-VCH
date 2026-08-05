#requires -Version 5.1
<#
.SYNOPSIS
    Fail-closed branch cleanup tool for ASU-VCH.

.DESCRIPTION
    Doctor performs environment diagnostics. Verify performs the full remote
    branch-deletion preflight without deleting. Delete repeats the preflight,
    requires an exact approval token and deletes only the approved remote branch.

    Native executable success is determined only by exit code. Stdout/stderr are
    captured as data to avoid Windows PowerShell 5.1 NativeCommandError failures.
#>

[CmdletBinding(SupportsShouldProcess = $true, ConfirmImpact = 'High')]
param(
    [ValidateSet('Doctor', 'Verify', 'Delete')]
    [string]$Mode = 'Doctor',

    [string]$RepositoryPath = 'C:\Project\ASU-VCH',
    [string]$RepositoryFullName = 'ClaytonKinnane/ASU-VCH',
    [string]$RemoteName = 'origin',
    [string]$MainBranch = 'main',
    [int]$PullRequestNumber = 0,
    [string]$BranchName,
    [string]$ExpectedMainSha,
    [string]$ExpectedPrHeadSha,
    [string]$ExpectedMergeCommitSha,
    [long]$PostMergeRunId = 0,
    [string]$JobName = 'asu-vch-static-verification',
    [string[]]$RequiredJobSteps = @(
        'Verify PHP runtime and clean checkout',
        'Event-aware git diff check',
        'Lint tracked PHP files',
        'Run CI-safe checkers',
        'Verify final repository integrity'
    ),
    [string]$ApprovalToken,
    [switch]$AllowDirtyWorktree
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

function Assert-Condition {
    param([Parameter(Mandatory = $true)][bool]$Condition, [Parameter(Mandatory = $true)][string]$Message)
    if (-not $Condition) { throw $Message }
}

function Assert-FullSha {
    param([Parameter(Mandatory = $true)][string]$Sha, [Parameter(Mandatory = $true)][string]$Name)
    Assert-Condition -Condition ($Sha -match '^[0-9a-fA-F]{40}$') -Message ('{0} must be a full 40-character SHA.' -f $Name)
}

function Get-CommandPath {
    param([Parameter(Mandatory = $true)][string]$Name)
    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($null -eq $command) { return $null }
    return [string]$command.Source
}

function Get-FirstLine {
    param($Lines)
    $items = @($Lines)
    if (@($items).Count -eq 0) { return $null }
    return ([string]$items[0]).Trim()
}

function ConvertTo-WindowsArgument {
    param([AllowNull()][string]$Value)
    if ($null -eq $Value -or $Value.Length -eq 0) { return '""' }
    if ($Value -notmatch '[\s"]') { return $Value }
    $builder = New-Object Text.StringBuilder
    [void]$builder.Append('"')
    $slashes = 0
    foreach ($character in $Value.ToCharArray()) {
        if ($character -eq '\') { $slashes++; continue }
        if ($character -eq '"') {
            [void]$builder.Append(('\' * (($slashes * 2) + 1)))
            [void]$builder.Append('"')
            $slashes = 0
            continue
        }
        if ($slashes -gt 0) { [void]$builder.Append(('\' * $slashes)); $slashes = 0 }
        [void]$builder.Append($character)
    }
    if ($slashes -gt 0) { [void]$builder.Append(('\' * ($slashes * 2))) }
    [void]$builder.Append('"')
    return $builder.ToString()
}

function New-NativeStartInfo {
    param([Parameter(Mandatory = $true)][string]$File, [string[]]$Arguments = @())
    $resolved = $File
    if (-not [IO.Path]::IsPathRooted($resolved)) {
        $candidate = Get-CommandPath -Name $resolved
        if ($null -ne $candidate) { $resolved = $candidate }
    }
    Assert-Condition -Condition (-not [string]::IsNullOrWhiteSpace($resolved)) -Message "Command is unavailable: $File"
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
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
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
    param([Parameter(Mandatory = $true)][string]$File, [string[]]$Arguments = @())
    $process = New-Object Diagnostics.Process
    $process.StartInfo = New-NativeStartInfo -File $File -Arguments $Arguments
    try {
        Assert-Condition -Condition $process.Start() -Message "Could not start command: $File"
        $stdoutTask = $process.StandardOutput.ReadToEndAsync()
        $stderrTask = $process.StandardError.ReadToEndAsync()
        $process.WaitForExit()
        return [pscustomobject]@{
            ExitCode = [int]$process.ExitCode
            StdOut = @(Convert-TextToLines -Text $stdoutTask.Result)
            StdErr = @(Convert-TextToLines -Text $stderrTask.Result)
        }
    }
    finally { $process.Dispose() }
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

function Invoke-GhJson {
    param([Parameter(Mandatory = $true)][string[]]$Arguments)
    $result = Invoke-NativeChecked -File 'gh' -Arguments (@('api') + @($Arguments)) -Quiet
    $text = (@($result.StdOut) -join "`n").Trim()
    if ([string]::IsNullOrWhiteSpace($text)) { return $null }
    return ($text | ConvertFrom-Json)
}

function Test-CanonicalRemote {
    param([Parameter(Mandatory = $true)][string]$RemoteUrl, [Parameter(Mandatory = $true)][string]$ExpectedRepository)
    $repo = $ExpectedRepository.Trim('/').ToLowerInvariant()
    $url = $RemoteUrl.Trim().TrimEnd('/').ToLowerInvariant()
    return @(
        ('https://github.com/{0}' -f $repo),
        ('https://github.com/{0}.git' -f $repo),
        ('git@github.com:{0}.git' -f $repo),
        ('ssh://git@github.com/{0}.git' -f $repo)
    ) -contains $url
}

function Get-RemoteBranchSha {
    param([Parameter(Mandatory = $true)][string]$Branch)
    $result = Invoke-NativeChecked -File 'git' -Arguments @('ls-remote', '--heads', $RemoteName, ('refs/heads/{0}' -f $Branch))
    $lines = @($result.StdOut)
    if (@($lines).Count -eq 0) { return $null }
    return (([string]$lines[0] -split '\s+')[0]).ToLowerInvariant()
}

function Invoke-Doctor {
    Assert-Condition -Condition ($env:OS -eq 'Windows_NT') -Message 'Windows is required.'
    Assert-Condition -Condition ([Environment]::Is64BitOperatingSystem) -Message '64-bit Windows is required.'
    Assert-Condition -Condition ($PSVersionTable.PSVersion.Major -ge 5) -Message 'PowerShell 5.1 or newer is required.'
    Assert-Condition -Condition (Test-Path -LiteralPath $RepositoryPath -PathType Container) -Message ('Repository path not found: {0}' -f $RepositoryPath)
    Assert-Condition -Condition ($null -ne (Get-CommandPath -Name 'git')) -Message 'git command is not available.'
    Assert-Condition -Condition ($null -ne (Get-CommandPath -Name 'gh')) -Message 'gh command is not available.'

    $auth = Invoke-NativeCaptured -File 'gh' -Arguments @('auth', 'status', '--hostname', 'github.com', '--active')
    Assert-Condition -Condition ($auth.ExitCode -eq 0) -Message 'GitHub CLI authentication is not active.'

    Push-Location -LiteralPath $RepositoryPath
    try {
        $inside = Get-FirstLine -Lines ((Invoke-NativeChecked -File 'git' -Arguments @('rev-parse', '--is-inside-work-tree')).StdOut)
        Assert-Condition -Condition ($inside -eq 'true') -Message 'RepositoryPath is not a Git worktree.'
        $remoteUrl = Get-FirstLine -Lines ((Invoke-NativeChecked -File 'git' -Arguments @('remote', 'get-url', $RemoteName)).StdOut)
        Assert-Condition -Condition (Test-CanonicalRemote -RemoteUrl $remoteUrl -ExpectedRepository $RepositoryFullName) -Message ('Remote identity mismatch: {0}' -f $remoteUrl)
        $status = @((Invoke-NativeChecked -File 'git' -Arguments @('status', '--porcelain=v1', '--untracked-files=all')).StdOut)
        if (@($status).Count -gt 0 -and -not $AllowDirtyWorktree) { throw 'Worktree is not clean.' }
        $repo = Invoke-GhJson -Arguments @(('repos/{0}' -f $RepositoryFullName))
        Assert-Condition -Condition ($repo.default_branch -eq $MainBranch) -Message ('Default branch mismatch: {0}' -f $repo.default_branch)
        Assert-Condition -Condition ($repo.permissions.push -eq $true) -Message 'GitHub account does not have push/write permission.'
        Write-Host 'DOCTOR_STATUS=PASS'
        Write-Host ('WORKTREE={0}' -f $(if (@($status).Count -eq 0) { 'CLEAN' } else { 'DIRTY_ALLOWED' }))
        Write-Host ('REPOSITORY={0}' -f $RepositoryFullName)
        Write-Host ('REMOTE={0}' -f $remoteUrl)
        Write-Host ('DEFAULT_BRANCH={0}' -f $repo.default_branch)
    }
    finally { Pop-Location }
}

function Invoke-Preflight {
    Assert-Condition -Condition ($PullRequestNumber -gt 0) -Message 'PullRequestNumber must be positive.'
    Assert-Condition -Condition ($PostMergeRunId -gt 0) -Message 'PostMergeRunId must be positive.'
    Assert-Condition -Condition (-not [string]::IsNullOrWhiteSpace($BranchName)) -Message 'BranchName is required.'
    Assert-FullSha -Sha $ExpectedMainSha -Name 'ExpectedMainSha'
    Assert-FullSha -Sha $ExpectedPrHeadSha -Name 'ExpectedPrHeadSha'
    Assert-FullSha -Sha $ExpectedMergeCommitSha -Name 'ExpectedMergeCommitSha'
    Assert-Condition -Condition ($BranchName -cne $MainBranch) -Message 'Deleting main is prohibited.'
    Assert-Condition -Condition ($BranchName -notmatch '^(?i:main|master)$') -Message ('Protected branch identity: {0}' -f $BranchName)

    Push-Location -LiteralPath $RepositoryPath
    try {
        Invoke-NativeChecked -File 'git' -Arguments @('check-ref-format', '--branch', $BranchName) | Out-Null
        Invoke-NativeChecked -File 'git' -Arguments @('fetch', '--prune', $RemoteName) | Out-Null
        $repo = Invoke-GhJson -Arguments @(('repos/{0}' -f $RepositoryFullName))
        Assert-Condition -Condition ($repo.default_branch -eq $MainBranch) -Message ('Default branch mismatch: {0}' -f $repo.default_branch)
        Assert-Condition -Condition ($BranchName -cne [string]$repo.default_branch) -Message 'Deleting the repository default branch is prohibited.'

        $mainSha = Get-RemoteBranchSha -Branch $MainBranch
        Assert-Condition -Condition ($mainSha -eq $ExpectedMainSha.ToLowerInvariant()) -Message ('Exact main mismatch. Expected={0}; Actual={1}' -f $ExpectedMainSha, $mainSha)
        $pr = Invoke-GhJson -Arguments @(('repos/{0}/pulls/{1}' -f $RepositoryFullName, $PullRequestNumber))
        Assert-Condition -Condition ($pr.state -eq 'closed') -Message ('PR #{0} is not closed.' -f $PullRequestNumber)
        Assert-Condition -Condition ($null -ne $pr.merged_at) -Message ('PR #{0} is not merged.' -f $PullRequestNumber)
        Assert-Condition -Condition ($pr.base.ref -eq $MainBranch) -Message ('PR base mismatch: {0}' -f $pr.base.ref)
        Assert-Condition -Condition ($pr.head.ref -eq $BranchName) -Message ('PR head branch mismatch: {0}' -f $pr.head.ref)
        Assert-Condition -Condition ($pr.head.sha.ToLowerInvariant() -eq $ExpectedPrHeadSha.ToLowerInvariant()) -Message ('PR head SHA mismatch: {0}' -f $pr.head.sha)
        Assert-Condition -Condition ($pr.merge_commit_sha.ToLowerInvariant() -eq $ExpectedMergeCommitSha.ToLowerInvariant()) -Message ('Merge commit mismatch: {0}' -f $pr.merge_commit_sha)

        $run = Invoke-GhJson -Arguments @(('repos/{0}/actions/runs/{1}' -f $RepositoryFullName, $PostMergeRunId))
        Assert-Condition -Condition ($run.event -eq 'push') -Message ('Workflow event mismatch: {0}' -f $run.event)
        Assert-Condition -Condition ($run.head_branch -eq $MainBranch) -Message ('Workflow branch mismatch: {0}' -f $run.head_branch)
        Assert-Condition -Condition ($run.head_sha.ToLowerInvariant() -eq $ExpectedMainSha.ToLowerInvariant()) -Message ('Workflow head mismatch: {0}' -f $run.head_sha)
        Assert-Condition -Condition ($run.status -eq 'completed' -and $run.conclusion -eq 'success') -Message ('Workflow run is not successful: {0}/{1}' -f $run.status, $run.conclusion)

        $jobsResponse = Invoke-GhJson -Arguments @(('repos/{0}/actions/runs/{1}/jobs?per_page=100' -f $RepositoryFullName, $PostMergeRunId))
        $jobs = @($jobsResponse.jobs | Where-Object { $_.name -eq $JobName })
        Assert-Condition -Condition (@($jobs).Count -eq 1) -Message ('Expected one job named {0}; found {1}.' -f $JobName, @($jobs).Count)
        $job = $jobs[0]
        Assert-Condition -Condition ($job.status -eq 'completed' -and $job.conclusion -eq 'success') -Message ('Workflow job is not successful: {0}/{1}' -f $job.status, $job.conclusion)
        foreach ($stepName in @($RequiredJobSteps)) {
            $steps = @($job.steps | Where-Object { $_.name -eq $stepName })
            Assert-Condition -Condition (@($steps).Count -eq 1) -Message ('Required workflow step missing or ambiguous: {0}' -f $stepName)
            Assert-Condition -Condition ($steps[0].status -eq 'completed' -and $steps[0].conclusion -eq 'success') -Message ('Required workflow step is not successful: {0}' -f $stepName)
        }

        $commentsResponse = Invoke-GhJson -Arguments @(('repos/{0}/issues/{1}/comments?per_page=100' -f $RepositoryFullName, $PullRequestNumber))
        $comments = @($commentsResponse)
        $passComments = @($comments | Where-Object {
            $null -ne $_.body -and $_.body.Contains('POST_MERGE_VERIFICATION_STATUS=PASS') -and
            $_.body.Contains('POST_MERGE_PUSH_RUN_EVIDENCE=PASS') -and $_.body.Contains($ExpectedMainSha)
        })
        Assert-Condition -Condition (@($passComments).Count -gt 0) -Message 'Canonical post-merge PASS comment was not found.'

        $branchSha = Get-RemoteBranchSha -Branch $BranchName
        $branchExists = ($null -ne $branchSha)
        if ($branchExists) {
            Assert-Condition -Condition ($branchSha -eq $ExpectedPrHeadSha.ToLowerInvariant()) -Message ('Remote branch SHA mismatch: {0}' -f $branchSha)
            Invoke-NativeChecked -File 'git' -Arguments @(
                'fetch', $RemoteName,
                ('+refs/heads/{0}:refs/remotes/{1}/{0}' -f $MainBranch, $RemoteName),
                ('+refs/heads/{0}:refs/remotes/{1}/{0}' -f $BranchName, $RemoteName)
            ) | Out-Null
            $comparisonHead = 'refs/remotes/{0}/{1}' -f $RemoteName, $BranchName
        }
        else {
            Invoke-NativeChecked -File 'git' -Arguments @('cat-file', '-e', ($ExpectedPrHeadSha + '^{commit}')) | Out-Null
            $comparisonHead = $ExpectedPrHeadSha
        }

        $comparisonMain = 'refs/remotes/{0}/{1}' -f $RemoteName, $MainBranch
        $countsLine = Get-FirstLine -Lines ((Invoke-NativeChecked -File 'git' -Arguments @('rev-list', '--left-right', '--count', ('{0}...{1}' -f $comparisonMain, $comparisonHead))).StdOut)
        $counts = @($countsLine -split '\s+')
        Assert-Condition -Condition (@($counts).Count -ge 2) -Message ('Could not parse branch divergence: {0}' -f $countsLine)
        $behind = [int]$counts[0]
        $ahead = [int]$counts[1]
        Assert-Condition -Condition ($ahead -eq 0) -Message ('Branch has unique unmerged commits: ahead={0}' -f $ahead)

        Write-Host ('MAIN_HEAD={0}' -f $mainSha)
        Write-Host ('PR_{0}=CLOSED / MERGED' -f $PullRequestNumber)
        Write-Host ('MERGE_COMMIT={0}' -f $pr.merge_commit_sha)
        Write-Host ('WORKFLOW_RUN_ID={0}' -f $run.id)
        Write-Host ('WORKFLOW_RUN_NUMBER={0}' -f $run.run_number)
        Write-Host ('WORKFLOW_JOB_ID={0}' -f $job.id)
        Write-Host ('BRANCH_STATUS={0}' -f $(if ($branchExists) { 'EXISTS' } else { 'ALREADY ABSENT' }))
        Write-Host ('BRANCH_AHEAD={0}' -f $ahead)
        Write-Host ('BRANCH_BEHIND={0}' -f $behind)
        Write-Host 'UNIQUE_UNMERGED_COMMITS=0'
        Write-Host 'PREFLIGHT_STATUS=PASS'
        return [pscustomobject]@{ BranchExists = $branchExists; BranchSha = $branchSha; MainSha = $mainSha }
    }
    finally { Pop-Location }
}

try {
    if ($Mode -eq 'Doctor') { Invoke-Doctor; exit 0 }
    $evidence = Invoke-Preflight
    if ($Mode -eq 'Verify') { Write-Host 'VERIFY_STATUS=PASS'; exit 0 }

    Assert-Condition -Condition ($ApprovalToken -ceq $BranchName) -Message 'ApprovalToken must exactly equal BranchName (case-sensitive).'
    if (-not $evidence.BranchExists) { Write-Host 'DELETE_STATUS=ALREADY_ABSENT'; exit 0 }

    if ($PSCmdlet.ShouldProcess("$RemoteName/$BranchName", 'Delete approved remote branch')) {
        $delete = Invoke-NativeCaptured -File 'git' -Arguments @('push', $RemoteName, '--delete', $BranchName)
        Assert-Condition -Condition ($delete.ExitCode -eq 0) -Message "Remote branch deletion failed. ExitCode=$($delete.ExitCode)"
        $remaining = Get-RemoteBranchSha -Branch $BranchName
        Assert-Condition -Condition ($null -eq $remaining) -Message 'Remote branch still exists after deletion command.'
        Write-Host 'DELETE_STATUS=PASS'
    }
    else { Write-Host 'DELETE_STATUS=NOT_EXECUTED' }
    exit 0
}
catch {
    Write-Error -ErrorAction Continue $_.Exception.Message
    Write-Host 'STATUS=FAIL'
    exit 1
}
