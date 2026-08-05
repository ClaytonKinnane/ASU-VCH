#requires -Version 5.1
<#
.SYNOPSIS
    Fail-closed branch cleanup tool for ASU-VCH.

.DESCRIPTION
    Doctor performs environment diagnostics.
    Verify performs the full remote branch deletion preflight without deleting.
    Delete repeats the preflight, requires an exact approval token and deletes
    only the approved remote branch.

    The tool never deletes a local branch and never changes repository settings.
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
    param(
        [Parameter(Mandatory = $true)][bool]$Condition,
        [Parameter(Mandatory = $true)][string]$Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

function Assert-FullSha {
    param(
        [Parameter(Mandatory = $true)][string]$Sha,
        [Parameter(Mandatory = $true)][string]$Name
    )

    Assert-Condition -Condition ($Sha -match '^[0-9a-fA-F]{40}$') `
        -Message ('{0} must be a full 40-character SHA.' -f $Name)
}

function Invoke-External {
    param(
        [Parameter(Mandatory = $true)][string]$FilePath,
        [string[]]$ArgumentList = @(),
        [int[]]$AllowedExitCodes = @(0),
        [switch]$SuppressOutput
    )

    if ($SuppressOutput) {
        & $FilePath @ArgumentList *> $null
        $exitCode = $LASTEXITCODE
        $lines = @()
    }
    else {
        $output = & $FilePath @ArgumentList 2>&1
        $exitCode = $LASTEXITCODE
        $lines = @($output | ForEach-Object { [string]$_ })
    }

    if ($AllowedExitCodes -notcontains $exitCode) {
        throw ('Command failed ({0}): {1} {2}{3}{4}' -f
            $exitCode,
            $FilePath,
            ($ArgumentList -join ' '),
            [Environment]::NewLine,
            ($lines -join [Environment]::NewLine))
    }

    return $lines
}

function Invoke-GhJson {
    param([Parameter(Mandatory = $true)][string[]]$Arguments)

    $lines = Invoke-External -FilePath 'gh' -ArgumentList (@('api') + $Arguments)
    $text = ($lines -join "`n").Trim()
    if ([string]::IsNullOrWhiteSpace($text)) {
        return $null
    }

    return ($text | ConvertFrom-Json)
}

function Get-FirstLine {
    param([string[]]$Lines)

    if ($null -eq $Lines -or $Lines.Count -eq 0) {
        return $null
    }

    return ([string]$Lines[0]).Trim()
}

function Test-CanonicalRemote {
    param(
        [Parameter(Mandatory = $true)][string]$RemoteUrl,
        [Parameter(Mandatory = $true)][string]$ExpectedRepository
    )

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

    $lines = Invoke-External -FilePath 'git' -ArgumentList @(
        'ls-remote', '--heads', $RemoteName, ('refs/heads/{0}' -f $Branch)
    )

    if ($lines.Count -eq 0) {
        return $null
    }

    return (([string]$lines[0] -split '\s+')[0]).ToLowerInvariant()
}

function Invoke-Doctor {
    Assert-Condition -Condition ($env:OS -eq 'Windows_NT') `
        -Message 'Windows is required.'
    Assert-Condition -Condition ([Environment]::Is64BitOperatingSystem) `
        -Message '64-bit Windows is required.'
    Assert-Condition -Condition ($PSVersionTable.PSVersion.Major -ge 5) `
        -Message 'PowerShell 5.1 or newer is required.'
    Assert-Condition -Condition (Test-Path -LiteralPath $RepositoryPath -PathType Container) `
        -Message ('Repository path not found: {0}' -f $RepositoryPath)
    Assert-Condition -Condition ($null -ne (Get-Command git -ErrorAction SilentlyContinue)) `
        -Message 'git command is not available.'
    Assert-Condition -Condition ($null -ne (Get-Command gh -ErrorAction SilentlyContinue)) `
        -Message 'gh command is not available.'

    & gh auth status --hostname github.com --active *> $null
    Assert-Condition -Condition ($LASTEXITCODE -eq 0) `
        -Message 'GitHub CLI authentication is not active.'

    Push-Location -LiteralPath $RepositoryPath
    try {
        $inside = Get-FirstLine (Invoke-External -FilePath 'git' -ArgumentList @(
            'rev-parse', '--is-inside-work-tree'
        ))
        Assert-Condition -Condition ($inside -eq 'true') `
            -Message 'RepositoryPath is not a Git worktree.'

        $remoteUrl = Get-FirstLine (Invoke-External -FilePath 'git' -ArgumentList @(
            'remote', 'get-url', $RemoteName
        ))
        Assert-Condition -Condition (Test-CanonicalRemote -RemoteUrl $remoteUrl -ExpectedRepository $RepositoryFullName) `
            -Message ('Remote identity mismatch: {0}' -f $remoteUrl)

        $status = Invoke-External -FilePath 'git' -ArgumentList @(
            'status', '--porcelain=v1', '--untracked-files=all'
        )
        if ($status.Count -gt 0 -and -not $AllowDirtyWorktree) {
            throw 'Worktree is not clean.'
        }

        $repo = Invoke-GhJson -Arguments @(('repos/{0}' -f $RepositoryFullName))
        Assert-Condition -Condition ($repo.default_branch -eq $MainBranch) `
            -Message ('Default branch mismatch: {0}' -f $repo.default_branch)
        Assert-Condition -Condition ($repo.permissions.push -eq $true) `
            -Message 'GitHub account does not have push/write permission.'

        Write-Host 'DOCTOR_STATUS=PASS'
        Write-Host ('REPOSITORY={0}' -f $RepositoryFullName)
        Write-Host ('REMOTE={0}' -f $remoteUrl)
        Write-Host ('DEFAULT_BRANCH={0}' -f $repo.default_branch)
    }
    finally {
        Pop-Location
    }
}

function Invoke-Preflight {
    Assert-Condition -Condition ($PullRequestNumber -gt 0) `
        -Message 'PullRequestNumber must be positive.'
    Assert-Condition -Condition ($PostMergeRunId -gt 0) `
        -Message 'PostMergeRunId must be positive.'
    Assert-Condition -Condition (-not [string]::IsNullOrWhiteSpace($BranchName)) `
        -Message 'BranchName is required.'
    Assert-FullSha -Sha $ExpectedMainSha -Name 'ExpectedMainSha'
    Assert-FullSha -Sha $ExpectedPrHeadSha -Name 'ExpectedPrHeadSha'
    Assert-FullSha -Sha $ExpectedMergeCommitSha -Name 'ExpectedMergeCommitSha'

    Assert-Condition -Condition ($BranchName -cne $MainBranch) `
        -Message 'Deleting main is prohibited.'
    Assert-Condition -Condition ($BranchName -notmatch '^(?i:main|master)$') `
        -Message ('Protected branch identity: {0}' -f $BranchName)

    Push-Location -LiteralPath $RepositoryPath
    try {
        Invoke-External -FilePath 'git' -ArgumentList @(
            'check-ref-format', '--branch', $BranchName
        ) | Out-Null

        Invoke-External -FilePath 'git' -ArgumentList @(
            'fetch', '--prune', $RemoteName
        ) | Out-Null

        $repo = Invoke-GhJson -Arguments @(('repos/{0}' -f $RepositoryFullName))
        Assert-Condition -Condition ($repo.default_branch -eq $MainBranch) `
            -Message ('Default branch mismatch: {0}' -f $repo.default_branch)
        Assert-Condition -Condition ($BranchName -cne [string]$repo.default_branch) `
            -Message 'Deleting the repository default branch is prohibited.'

        $mainSha = Get-RemoteBranchSha -Branch $MainBranch
        Assert-Condition -Condition ($mainSha -eq $ExpectedMainSha.ToLowerInvariant()) `
            -Message ('Exact main mismatch. Expected={0}; Actual={1}' -f $ExpectedMainSha, $mainSha)

        $pr = Invoke-GhJson -Arguments @(
            ('repos/{0}/pulls/{1}' -f $RepositoryFullName, $PullRequestNumber)
        )
        Assert-Condition -Condition ($pr.state -eq 'closed') `
            -Message ('PR #{0} is not closed.' -f $PullRequestNumber)
        Assert-Condition -Condition ($null -ne $pr.merged_at) `
            -Message ('PR #{0} is not merged.' -f $PullRequestNumber)
        Assert-Condition -Condition ($pr.base.ref -eq $MainBranch) `
            -Message ('PR base mismatch: {0}' -f $pr.base.ref)
        Assert-Condition -Condition ($pr.head.ref -eq $BranchName) `
            -Message ('PR head branch mismatch: {0}' -f $pr.head.ref)
        Assert-Condition -Condition ($pr.head.sha.ToLowerInvariant() -eq $ExpectedPrHeadSha.ToLowerInvariant()) `
            -Message ('PR head SHA mismatch: {0}' -f $pr.head.sha)
        Assert-Condition -Condition ($pr.merge_commit_sha.ToLowerInvariant() -eq $ExpectedMergeCommitSha.ToLowerInvariant()) `
            -Message ('Merge commit mismatch: {0}' -f $pr.merge_commit_sha)

        $run = Invoke-GhJson -Arguments @(
            ('repos/{0}/actions/runs/{1}' -f $RepositoryFullName, $PostMergeRunId)
        )
        Assert-Condition -Condition ($run.event -eq 'push') `
            -Message ('Workflow event mismatch: {0}' -f $run.event)
        Assert-Condition -Condition ($run.head_branch -eq $MainBranch) `
            -Message ('Workflow branch mismatch: {0}' -f $run.head_branch)
        Assert-Condition -Condition ($run.head_sha.ToLowerInvariant() -eq $ExpectedMainSha.ToLowerInvariant()) `
            -Message ('Workflow head mismatch: {0}' -f $run.head_sha)
        Assert-Condition -Condition ($run.status -eq 'completed' -and $run.conclusion -eq 'success') `
            -Message ('Workflow run is not successful: {0}/{1}' -f $run.status, $run.conclusion)

        $jobsResponse = Invoke-GhJson -Arguments @(
            ('repos/{0}/actions/runs/{1}/jobs?per_page=100' -f $RepositoryFullName, $PostMergeRunId)
        )
        $jobs = @($jobsResponse.jobs | Where-Object { $_.name -eq $JobName })
        Assert-Condition -Condition ($jobs.Count -eq 1) `
            -Message ('Expected one job named {0}; found {1}.' -f $JobName, $jobs.Count)

        $job = $jobs[0]
        Assert-Condition -Condition ($job.status -eq 'completed' -and $job.conclusion -eq 'success') `
            -Message ('Workflow job is not successful: {0}/{1}' -f $job.status, $job.conclusion)

        foreach ($stepName in $RequiredJobSteps) {
            $steps = @($job.steps | Where-Object { $_.name -eq $stepName })
            Assert-Condition -Condition ($steps.Count -eq 1) `
                -Message ('Required workflow step missing or ambiguous: {0}' -f $stepName)
            Assert-Condition -Condition (
                $steps[0].status -eq 'completed' -and $steps[0].conclusion -eq 'success'
            ) -Message ('Required workflow step is not successful: {0}' -f $stepName)
        }

        $comments = Invoke-GhJson -Arguments @(
            ('repos/{0}/issues/{1}/comments?per_page=100' -f $RepositoryFullName, $PullRequestNumber)
        )
        $passComments = @($comments | Where-Object {
            $null -ne $_.body -and
            $_.body.Contains('POST_MERGE_VERIFICATION_STATUS=PASS') -and
            $_.body.Contains('POST_MERGE_PUSH_RUN_EVIDENCE=PASS') -and
            $_.body.Contains($ExpectedMainSha)
        })
        Assert-Condition -Condition ($passComments.Count -gt 0) `
            -Message 'Canonical post-merge PASS comment was not found.'

        $branchSha = Get-RemoteBranchSha -Branch $BranchName
        $branchExists = ($null -ne $branchSha)

        if ($branchExists) {
            Assert-Condition -Condition ($branchSha -eq $ExpectedPrHeadSha.ToLowerInvariant()) `
                -Message ('Remote branch SHA mismatch: {0}' -f $branchSha)

            Invoke-External -FilePath 'git' -ArgumentList @(
                'fetch', $RemoteName,
                ('+refs/heads/{0}:refs/remotes/{1}/{0}' -f $MainBranch, $RemoteName),
                ('+refs/heads/{0}:refs/remotes/{1}/{0}' -f $BranchName, $RemoteName)
            ) | Out-Null
            $comparisonHead = 'refs/remotes/{0}/{1}' -f $RemoteName, $BranchName
        }
        else {
            Invoke-External -FilePath 'git' -ArgumentList @(
                'cat-file', '-e', ($ExpectedPrHeadSha + '^{commit}')
            ) | Out-Null
            $comparisonHead = $ExpectedPrHeadSha
        }

        $comparisonMain = 'refs/remotes/{0}/{1}' -f $RemoteName, $MainBranch
        $countsLine = Get-FirstLine (Invoke-External -FilePath 'git' -ArgumentList @(
            'rev-list', '--left-right', '--count',
            ('{0}...{1}' -f $comparisonMain, $comparisonHead)
        ))
        $counts = $countsLine -split '\s+'
        Assert-Condition -Condition ($counts.Count -ge 2) `
            -Message ('Could not parse branch divergence: {0}' -f $countsLine)

        $behind = [int]$counts[0]
        $ahead = [int]$counts[1]
        Assert-Condition -Condition ($ahead -eq 0) `
            -Message ('Branch has unique unmerged commits: ahead={0}' -f $ahead)

        Write-Host ('MAIN_HEAD={0}' -f $mainSha)
        Write-Host ('PR_{0}=CLOSED / MERGED' -f $PullRequestNumber)
        Write-Host ('MERGE_COMMIT={0}' -f $pr.merge_commit_sha)
        Write-Host ('WORKFLOW_RUN_ID={0}' -f $run.id)
        Write-Host ('WORKFLOW_RUN_NUMBER={0}' -f $run.run_number)
        Write-Host ('WORKFLOW_JOB_ID={0}' -f $job.id)
        Write-Host ('BRANCH_STATUS={0}' -f $(if ($branchExists) { 'EXISTS' } else { 'ALREADY_ABSENT' }))
        Write-Host ('BRANCH_AHEAD_OF_MAIN={0}' -f $ahead)
        Write-Host ('BRANCH_BEHIND_MAIN={0}' -f $behind)
        Write-Host 'UNIQUE_UNMERGED_CHANGES=0'
        Write-Host 'POST_MERGE_VERIFICATION=PASS'
        Write-Host 'DELETION_SAFETY=PASS'

        return [pscustomobject]@{
            BranchExists = $branchExists
            MainSha = $mainSha
            BranchSha = $branchSha
            Ahead = $ahead
            Behind = $behind
            MergeCommit = [string]$pr.merge_commit_sha
        }
    }
    finally {
        Pop-Location
    }
}

try {
    Invoke-Doctor

    if ($Mode -eq 'Doctor') {
        exit 0
    }

    $preflight = Invoke-Preflight

    if ($Mode -eq 'Verify') {
        Write-Host 'VERIFY_ONLY=PASS'
        exit 0
    }

    Assert-Condition -Condition ($preflight.BranchExists) `
        -Message 'Remote branch is already absent; deletion is not required.'
    Assert-Condition -Condition (-not [string]::IsNullOrWhiteSpace($ApprovalToken)) `
        -Message 'ApprovalToken is required for Delete.'
    Assert-Condition -Condition ($ApprovalToken -ceq $BranchName) `
        -Message 'ApprovalToken must exactly equal BranchName, case-sensitive.'

    $target = '{0}:refs/heads/{1}' -f $RepositoryFullName, $BranchName
    if ($PSCmdlet.ShouldProcess($target, 'Delete approved remote branch')) {
        Push-Location -LiteralPath $RepositoryPath
        try {
            Invoke-External -FilePath 'git' -ArgumentList @(
                'push', $RemoteName, '--delete', $BranchName
            ) | Out-Null

            Invoke-External -FilePath 'git' -ArgumentList @(
                'fetch', '--prune', $RemoteName
            ) | Out-Null

            $afterBranch = Get-RemoteBranchSha -Branch $BranchName
            Assert-Condition -Condition ($null -eq $afterBranch) `
                -Message ('Branch still exists after deletion: {0}' -f $afterBranch)

            $afterMain = Get-RemoteBranchSha -Branch $MainBranch
            Assert-Condition -Condition ($afterMain -eq $ExpectedMainSha.ToLowerInvariant()) `
                -Message ('main changed during cleanup: {0}' -f $afterMain)

            $afterPr = Invoke-GhJson -Arguments @(
                ('repos/{0}/pulls/{1}' -f $RepositoryFullName, $PullRequestNumber)
            )
            Assert-Condition -Condition (
                $afterPr.state -eq 'closed' -and
                $null -ne $afterPr.merged_at -and
                $afterPr.merge_commit_sha.ToLowerInvariant() -eq $ExpectedMergeCommitSha.ToLowerInvariant()
            ) -Message 'PR merged state or merge commit changed after cleanup.'

            Write-Host 'BRANCH_STATUS=ABSENT'
            Write-Host ('MAIN_HEAD={0}' -f $afterMain)
            Write-Host ('PR_{0}=CLOSED / MERGED' -f $PullRequestNumber)
            Write-Host 'FEATURE_BRANCH_DELETION_STATUS=PASS'
        }
        finally {
            Pop-Location
        }
    }

    exit 0
}
catch {
    Write-Error $_.Exception.Message
    exit 1
}
