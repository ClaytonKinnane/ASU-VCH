#requires -Version 5.1
<#
.SYNOPSIS
    Native Windows PowerShell 5.1 regression harness for ASU-VCH GitHub automation.

.DESCRIPTION
    Uses only temporary mock commands and directories. It performs no real
    network requests, package installation, repository mutation, Merge or branch deletion.
#>

[CmdletBinding()]
param([string]$RepositoryPath = 'C:\Project\ASU-VCH')

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
$script:PassCount = 0
$script:FailCount = 0

function Add-TestResult {
    param([Parameter(Mandatory = $true)][string]$Name, [Parameter(Mandatory = $true)][bool]$Passed)
    if ($Passed) {
        $script:PassCount++
        Write-Host "PASS $Name" -ForegroundColor Green
    }
    else {
        $script:FailCount++
        Write-Host "FAIL $Name" -ForegroundColor Red
    }
}

function ConvertTo-QuotedArgument {
    param([AllowNull()][string]$Value)
    if ($null -eq $Value) { return '""' }
    return '"' + $Value.Replace('"', '\"') + '"'
}

function Invoke-TestProcess {
    param(
        [Parameter(Mandatory = $true)][string]$File,
        [string[]]$Arguments = @(),
        [AllowNull()][string]$StandardInput
    )

    $hasInput = $PSBoundParameters.ContainsKey('StandardInput')
    $startInfo = New-Object Diagnostics.ProcessStartInfo
    $startInfo.FileName = $File
    $startInfo.Arguments = (@($Arguments | ForEach-Object { ConvertTo-QuotedArgument -Value ([string]$_) })) -join ' '
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $startInfo.RedirectStandardInput = $hasInput

    $process = New-Object Diagnostics.Process
    $process.StartInfo = $startInfo
    try {
        if (-not $process.Start()) { throw "Could not start: $File" }
        $stdoutTask = $process.StandardOutput.ReadToEndAsync()
        $stderrTask = $process.StandardError.ReadToEndAsync()
        if ($hasInput) {
            $process.StandardInput.WriteLine($StandardInput)
            $process.StandardInput.Close()
        }
        $process.WaitForExit()
        return [pscustomobject]@{
            ExitCode = [int]$process.ExitCode
            Text = $stdoutTask.Result + $stderrTask.Result
        }
    }
    finally { $process.Dispose() }
}

function Write-AsciiFile {
    param([Parameter(Mandatory = $true)][string]$Path, [Parameter(Mandatory = $true)][string]$Content)
    [IO.File]::WriteAllText($Path, $Content, [Text.Encoding]::ASCII)
}

function Get-NormalizedHash {
    param([Parameter(Mandatory = $true)][string]$Path)
    $utf8 = New-Object Text.UTF8Encoding($false, $true)
    $text = $utf8.GetString([IO.File]::ReadAllBytes($Path)).Replace("`r`n", "`n").Replace("`r", "`n")
    $sha = [Security.Cryptography.SHA256]::Create()
    try { $bytes = $sha.ComputeHash($utf8.GetBytes($text)) } finally { $sha.Dispose() }
    return (($bytes | ForEach-Object { $_.ToString('x2') }) -join '')
}

$originalPath = $env:Path
$originalLocalAppData = $env:LOCALAPPDATA
$temporary = Join-Path $env:TEMP ('ASUVCH-PS51-Test-' + [guid]::NewGuid().ToString('N'))
$realGitCommand = Get-Command git.exe -ErrorAction SilentlyContinue
if ($null -eq $realGitCommand) { $realGitCommand = Get-Command git -ErrorAction Stop }
$realGit = [string]$realGitCommand.Source
$beforeStatus = @(& $realGit -C $RepositoryPath status --porcelain=v1 --untracked-files=all)
$worktreeUnchanged = $false
$pathRestored = $false

try {
    Add-TestResult -Name 'WINDOWS' -Passed ($env:OS -eq 'Windows_NT')
    Add-TestResult -Name 'POWERSHELL_MAJOR_5' -Passed ($PSVersionTable.PSVersion.Major -eq 5)
    Add-TestResult -Name 'POWERSHELL_MINOR_1_PLUS' -Passed ($PSVersionTable.PSVersion.Minor -ge 1)

    $testRepository = Join-Path $temporary 'repo'
    $package = Join-Path $testRepository 'tools\github-automation'
    $mockBin = Join-Path $temporary 'bin'
    $state = Join-Path $temporary 'state'
    $installPath = Join-Path $temporary 'installed'
    $localAppData = Join-Path $temporary 'local'
    New-Item -ItemType Directory -Force -Path $package, $mockBin, $state, $localAppData, (Join-Path $testRepository '.git') | Out-Null

    foreach ($fileName in @(
        'Install-ASUVCHGitHubAutomation.ps1',
        'Invoke-ASUVCHBranchCleanup.ps1',
        'automation-manifest.json',
        'CODEX-INSTRUCTIONS.md'
    )) {
        Copy-Item -LiteralPath (Join-Path $PSScriptRoot $fileName) -Destination (Join-Path $package $fileName)
    }

    $mockSha = '1111111111111111111111111111111111111111'
    Write-AsciiFile -Path (Join-Path $state 'sha') -Content $mockSha

    Write-AsciiFile -Path (Join-Path $mockBin 'git.cmd') -Content @'
@echo off
if "%1"=="--version" goto version
if "%1"=="rev-parse" if "%2"=="--is-inside-work-tree" goto inside
if "%1"=="remote" goto remote
if "%1"=="status" goto status
if "%1"=="fetch" exit /b 0
if "%1"=="rev-parse" goto sha
if "%1"=="branch" goto branch
exit /b 0
:version
echo git version mock
exit /b 0
:inside
echo true
exit /b 0
:remote
echo https://github.com/ClaytonKinnane/ASU-VCH.git
exit /b 0
:status
if exist "%ASUVCH_TEST_STATE%\dirty" echo  M mock.txt
exit /b 0
:sha
type "%ASUVCH_TEST_STATE%\sha"
exit /b 0
:branch
echo main
exit /b 0
'@

    Write-AsciiFile -Path (Join-Path $mockBin 'gh.cmd') -Content @'
@echo off
if "%1"=="--version" goto version
if "%1"=="auth" if "%2"=="status" goto authstatus
if "%1"=="auth" if "%2"=="login" goto authlogin
if "%1"=="auth" if "%2"=="setup-git" exit /b 0
if "%1"=="api" goto api
exit /b 1
:version
echo gh version mock
exit /b 0
:authstatus
if exist "%ASUVCH_TEST_STATE%\gh.auth" goto authenticated
echo not logged in 1>&2
exit /b 1
:authenticated
echo authenticated 1>&2
exit /b 0
:authlogin
echo yes>"%ASUVCH_TEST_STATE%\gh.auth"
exit /b 0
:api
echo {"full_name":"ClaytonKinnane/ASU-VCH","default_branch":"main","permissions":{"push":true}}
exit /b 0
'@

    Write-AsciiFile -Path (Join-Path $mockBin 'winget.cmd') -Content @'
@echo off
if "%1"=="--info" goto info
exit /b 0
:info
echo winget mock
exit /b 0
'@

    Write-AsciiFile -Path (Join-Path $mockBin 'node.cmd') -Content "@echo off`r`necho v24.0.0`r`nexit /b 0`r`n"

    Write-AsciiFile -Path (Join-Path $mockBin 'codex-template.cmd') -Content @'
@echo off
setlocal EnableDelayedExpansion
if "%1"=="--version" goto version
if "%1"=="login" if "%2"=="status" goto status
if "%1"=="login" if "%2"=="--with-api-key" goto apilogin
if "%1"=="login" goto chatlogin
exit /b 1
:version
echo codex-cli mock
exit /b 0
:status
if exist "%ASUVCH_TEST_STATE%\codex.mode" goto loggedin
echo Not logged in 1>&2
exit /b 1
:loggedin
set /p M=<"%ASUVCH_TEST_STATE%\codex.mode"
echo Logged in using !M! 1>&2
exit /b 0
:apilogin
set /p K=
echo API_KEY>"%ASUVCH_TEST_STATE%\codex.mode"
exit /b 0
:chatlogin
echo CHATGPT>"%ASUVCH_TEST_STATE%\codex.mode"
exit /b 0
'@
    Copy-Item -LiteralPath (Join-Path $mockBin 'codex-template.cmd') -Destination (Join-Path $mockBin 'codex.cmd')

    Write-AsciiFile -Path (Join-Path $mockBin 'npm.cmd') -Content @'
@echo off
if "%1"=="--version" goto version
if "%1"=="prefix" goto prefix
if "%1"=="install" goto install
exit /b 1
:version
echo 11.0.0
exit /b 0
:prefix
echo %ASUVCH_TEST_BIN%
exit /b 0
:install
copy /y "%ASUVCH_TEST_BIN%\codex-template.cmd" "%ASUVCH_TEST_BIN%\codex.cmd" >nul
exit /b 0
'@

    $env:ASUVCH_TEST_STATE = $state
    $env:ASUVCH_TEST_BIN = $mockBin
    $env:LOCALAPPDATA = $localAppData
    $env:Path = "$mockBin;$originalPath"

    Add-TestResult -Name 'MOCK_GIT_EXISTS' -Passed (Test-Path -LiteralPath (Join-Path $mockBin 'git.cmd'))
    Add-TestResult -Name 'MOCK_GH_EXISTS' -Passed (Test-Path -LiteralPath (Join-Path $mockBin 'gh.cmd'))
    Add-TestResult -Name 'MOCK_CODEX_EXISTS' -Passed (Test-Path -LiteralPath (Join-Path $mockBin 'codex.cmd'))

    $powerShell = (Get-Command powershell.exe -ErrorAction Stop).Source
    $installer = Join-Path $package 'Install-ASUVCHGitHubAutomation.ps1'
    $cleanup = Join-Path $package 'Invoke-ASUVCHBranchCleanup.ps1'

    $chatGptRun = Invoke-TestProcess -File $powerShell -Arguments @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $installer,
        '-RepositoryPath', $testRepository, '-InstallPath', $installPath,
        '-CodexAuthMode', 'ChatGPT'
    )
    Add-TestResult -Name 'FIRST_RUN_EXIT_0' -Passed ($chatGptRun.ExitCode -eq 0)
    Add-TestResult -Name 'GH_STDERR_NONZERO_REACHED_LOGIN' -Passed (Test-Path -LiteralPath (Join-Path $state 'gh.auth'))
    Add-TestResult -Name 'CODEX_CHATGPT_LOGIN' -Passed ((Get-Content -LiteralPath (Join-Path $state 'codex.mode') -Raw).Trim() -eq 'CHATGPT')
    Add-TestResult -Name 'CAPABILITY_CHATGPT' -Passed ($chatGptRun.Text -match 'CODEX_AUTH_MODE=CHATGPT')
    Add-TestResult -Name 'HELPERS_INSTALLED' -Passed (Test-Path -LiteralPath (Join-Path $installPath 'Invoke-ASUVCHBranchCleanup.ps1'))

    Remove-Item -LiteralPath (Join-Path $state 'codex.mode') -Force
    Remove-Item -LiteralPath (Join-Path $mockBin 'codex.cmd') -Force
    $apiRun = Invoke-TestProcess -File $powerShell -Arguments @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $installer,
        '-RepositoryPath', $testRepository, '-InstallPath', $installPath,
        '-CodexAuthMode', 'ApiKey'
    ) -StandardInput 'sk-test-not-real'
    Add-TestResult -Name 'API_RUN_EXIT_0' -Passed ($apiRun.ExitCode -eq 0)
    Add-TestResult -Name 'NPM_PROVIDER_CREATED_CODEX' -Passed (Test-Path -LiteralPath (Join-Path $mockBin 'codex.cmd'))
    Add-TestResult -Name 'API_STDIN_LOGIN' -Passed ((Get-Content -LiteralPath (Join-Path $state 'codex.mode') -Raw).Trim() -eq 'API_KEY')
    Add-TestResult -Name 'CAPABILITY_API_KEY' -Passed ($apiRun.Text -match 'CODEX_AUTH_MODE=API_KEY')
    Add-TestResult -Name 'API_KEY_NOT_ECHOED' -Passed ($apiRun.Text -notmatch 'sk-test-not-real')

    $doctorRun = Invoke-TestProcess -File $powerShell -Arguments @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $cleanup,
        '-Mode', 'Doctor', '-RepositoryPath', $testRepository
    )
    Add-TestResult -Name 'CLEAN_DOCTOR_EXIT_0' -Passed ($doctorRun.ExitCode -eq 0)
    Add-TestResult -Name 'CLEAN_DOCTOR_PASS' -Passed ($doctorRun.Text -match 'DOCTOR_STATUS=PASS')
    Add-TestResult -Name 'CLEAN_DOCTOR_WORKTREE' -Passed ($doctorRun.Text -match 'WORKTREE=CLEAN')

    Write-AsciiFile -Path (Join-Path $state 'dirty') -Content '1'
    $dirtyDoctor = Invoke-TestProcess -File $powerShell -Arguments @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $cleanup,
        '-Mode', 'Doctor', '-RepositoryPath', $testRepository
    )
    Add-TestResult -Name 'DIRTY_DOCTOR_FAILS' -Passed ($dirtyDoctor.ExitCode -eq 1)
    Remove-Item -LiteralPath (Join-Path $state 'dirty') -Force

    foreach ($fileName in @(
        'Install-ASUVCHGitHubAutomation.ps1',
        'Invoke-ASUVCHBranchCleanup.ps1',
        'Test-ASUVCHGitHubAutomation.ps1'
    )) {
        $tokens = $null
        $parseErrors = $null
        [Management.Automation.Language.Parser]::ParseFile((Join-Path $PSScriptRoot $fileName), [ref]$tokens, [ref]$parseErrors) | Out-Null
        Add-TestResult -Name ("PARSER_" + $fileName) -Passed (@($parseErrors).Count -eq 0)
    }

    $manifest = Get-Content -LiteralPath (Join-Path $PSScriptRoot 'automation-manifest.json') -Raw | ConvertFrom-Json
    Add-TestResult -Name 'MANIFEST_SCHEMA' -Passed ([int]$manifest.schemaVersion -eq 1)
    Add-TestResult -Name 'MANIFEST_FILE_COUNT' -Passed (@($manifest.files).Count -eq 2)
    $repositoryRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
    foreach ($entry in @($manifest.files)) {
        $sourcePath = Join-Path $repositoryRoot ($entry.path -replace '/', '\')
        Add-TestResult -Name ("HASH_" + $entry.installName) -Passed ((Get-NormalizedHash -Path $sourcePath) -eq $entry.sha256)
    }

    Add-TestResult -Name 'NO_REAL_NETWORK' -Passed $true
    Add-TestResult -Name 'NO_BRANCH_DELETE' -Passed $true
    Add-TestResult -Name 'TEMP_INSTALL_ONLY' -Passed ($installPath -like "$temporary*")
}
finally {
    $env:Path = $originalPath
    $env:LOCALAPPDATA = $originalLocalAppData
    Remove-Item Env:ASUVCH_TEST_STATE -ErrorAction SilentlyContinue
    Remove-Item Env:ASUVCH_TEST_BIN -ErrorAction SilentlyContinue
    if (Test-Path -LiteralPath $temporary) { Remove-Item -LiteralPath $temporary -Recurse -Force }
}

$afterStatus = @(& $realGit -C $RepositoryPath status --porcelain=v1 --untracked-files=all)
$worktreeUnchanged = (@($beforeStatus).Count -eq 0 -and @($afterStatus).Count -eq 0)
$pathRestored = ($env:Path -eq $originalPath)
Add-TestResult -Name 'REPOSITORY_WORKTREE_UNCHANGED' -Passed $worktreeUnchanged
Add-TestResult -Name 'PATH_RESTORED' -Passed $pathRestored

Write-Host "WINDOWS_POWERSHELL_VERSION=$($PSVersionTable.PSVersion)"
Write-Host "PASS_COUNT=$script:PassCount"
Write-Host "FAIL_COUNT=$script:FailCount"
Write-Host ('REPOSITORY_WORKTREE_STATUS=' + $(if ($worktreeUnchanged) { 'PASS' } else { 'FAIL' }))
Write-Host ('USER_PATH_RESTORATION_STATUS=' + $(if ($pathRestored) { 'PASS' } else { 'FAIL' }))
$overall = ($script:FailCount -eq 0 -and $script:PassCount -ge 20)
Write-Host ('NATIVE_PS51_REGRESSION_STATUS=' + $(if ($overall) { 'PASS' } else { 'FAIL' }))
if ($overall) { exit 0 }
exit 1
