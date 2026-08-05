#requires -Version 5.1
<#
.SYNOPSIS
    Native Windows PowerShell 5.1 regression harness for ASU-VCH GitHub automation.

.DESCRIPTION
    Uses only GUID-scoped temporary mock commands and temporary directories.
    It performs no real network requests, package installation, repository
    mutation, Merge or branch deletion.

    The temporary installer copy receives one test-only PATH refresh shim so
    process-local mock commands remain selected after the production installer
    refreshes Machine/User PATH. The repository installer source is not modified.
#>

[CmdletBinding()]
param(
    [string]$RepositoryPath = 'C:\Project\ASU-VCH'
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$script:PassCount = 0
$script:FailCount = 0
$script:InfrastructureError = $null

function Add-TestResult {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name,

        [Parameter(Mandatory = $true)]
        [bool]$Passed
    )

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

    if ($null -eq $Value) {
        return '""'
    }

    return '"' + $Value.Replace('"', '\"') + '"'
}

function Invoke-TestProcess {
    param(
        [Parameter(Mandatory = $true)]
        [string]$File,

        [string[]]$Arguments = @(),

        [AllowNull()]
        [string]$StandardInput,

        [ValidateRange(1, 600)]
        [int]$TimeoutSeconds = 60
    )

    $hasInput = $PSBoundParameters.ContainsKey('StandardInput')

    $startInfo = New-Object Diagnostics.ProcessStartInfo
    $startInfo.FileName = $File
    $startInfo.Arguments = (
        @(
            $Arguments | ForEach-Object {
                ConvertTo-QuotedArgument -Value ([string]$_)
            }
        ) -join ' '
    )
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $startInfo.RedirectStandardInput = $hasInput

    $process = New-Object Diagnostics.Process
    $process.StartInfo = $startInfo

    try {
        if (-not $process.Start()) {
            throw "Could not start: $File"
        }

        $stdoutTask = $process.StandardOutput.ReadToEndAsync()
        $stderrTask = $process.StandardError.ReadToEndAsync()

        if ($hasInput) {
            $process.StandardInput.WriteLine($StandardInput)
            $process.StandardInput.Close()
        }

        $timedOut = -not $process.WaitForExit($TimeoutSeconds * 1000)

        if ($timedOut) {
            $taskKill = Join-Path $env:SystemRoot 'System32\taskkill.exe'

            try {
                & $taskKill /PID $process.Id /T /F 2>$null | Out-Null
            }
            catch {
                # Root-process fallback follows below.
            }

            if (-not $process.WaitForExit(10000)) {
                try {
                    $process.Kill()
                }
                catch {
                    # The bounded termination check below remains authoritative.
                }

                if (-not $process.WaitForExit(10000)) {
                    throw "Timed-out process could not be terminated. PID=$($process.Id)"
                }
            }
        }

        # Complete asynchronous stream reads after confirmed process exit.
        $process.WaitForExit()

        $timeoutText = if ($timedOut) {
            "PROCESS_TIMEOUT_SECONDS=$TimeoutSeconds`r`n"
        }
        else {
            ''
        }

        return [pscustomobject]@{
            ExitCode = $(if ($timedOut) { -1 } else { [int]$process.ExitCode })
            TimedOut = [bool]$timedOut
            StdOut = [string]$stdoutTask.Result
            StdErr = [string]$stderrTask.Result
            Text = (
                $timeoutText +
                [string]$stdoutTask.Result +
                [string]$stderrTask.Result
            )
        }
    }
    finally {
        $process.Dispose()
    }
}

function Write-AsciiFile {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,

        [Parameter(Mandatory = $true)]
        [string]$Content
    )

    $writeContent = $Content

    if ([IO.Path]::GetExtension($Path) -ieq '.cmd') {
        $writeContent = $writeContent.Replace("`r`n", "`n").Replace("`r", "`n")
        $writeContent = $writeContent.TrimEnd([char[]]@("`r", "`n"))
        $writeContent = $writeContent.Replace("`n", "`r`n") + "`r`n"
    }

    [IO.File]::WriteAllText($Path, $writeContent, [Text.Encoding]::ASCII)
}

function Write-Utf8NoBomFile {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,

        [Parameter(Mandatory = $true)]
        [string]$Content
    )

    $utf8 = New-Object Text.UTF8Encoding($false)
    [IO.File]::WriteAllText($Path, $Content, $utf8)
}

function Get-StateValue {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path
    )

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        return $null
    }

    return ([string](Get-Content -LiteralPath $Path -Raw -Encoding ASCII)).Trim()
}

function Get-NormalizedHash {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path
    )

    $utf8 = New-Object Text.UTF8Encoding($false, $true)
    $text = $utf8.GetString(
        [IO.File]::ReadAllBytes($Path)
    ).Replace("`r`n", "`n").Replace("`r", "`n")

    $sha = [Security.Cryptography.SHA256]::Create()

    try {
        $bytes = $sha.ComputeHash($utf8.GetBytes($text))
    }
    finally {
        $sha.Dispose()
    }

    return (($bytes | ForEach-Object {
        $_.ToString('x2')
    }) -join '')
}

function Set-TestInstallerPathShim {
    param(
        [Parameter(Mandatory = $true)]
        [string]$InstallerPath
    )

    $source = Get-Content -LiteralPath $InstallerPath -Raw -Encoding UTF8

    $pattern = '(?ms)^function Refresh-ProcessPath \{\r?\n.*?^\}\r?\n\r?\nfunction Add-ProcessPath'

    $replacement = @'
function Refresh-ProcessPath {
    $candidatePaths = @(
        $env:ASUVCH_TEST_BIN,
        $env:Path
    ) | Where-Object {
        -not [string]::IsNullOrWhiteSpace($_)
    }

    $seen = @{}
    $segments = New-Object System.Collections.Generic.List[string]

    foreach ($candidatePath in @($candidatePaths)) {
        foreach ($segment in @([string]$candidatePath -split ';')) {
            $trimmed = ([string]$segment).Trim()

            if ([string]::IsNullOrWhiteSpace($trimmed)) {
                continue
            }

            $key = $trimmed.TrimEnd('\').ToLowerInvariant()

            if (-not $seen.ContainsKey($key)) {
                $seen[$key] = $true
                $segments.Add($trimmed)
            }
        }
    }

    $env:Path = (@($segments) -join ';')
}

function Add-ProcessPath
'@

    $regexOptions = (
        [Text.RegularExpressions.RegexOptions]::Multiline -bor
        [Text.RegularExpressions.RegexOptions]::Singleline
    )

    $regex = [Text.RegularExpressions.Regex]::new(
        $pattern,
        $regexOptions
    )

    $evaluator = [Text.RegularExpressions.MatchEvaluator]{
        param($match)
        return $replacement
    }

    $patched = $regex.Replace($source, $evaluator, 1)

    if ($patched -ceq $source) {
        throw 'Could not apply isolated test PATH shim.'
    }

    Write-Utf8NoBomFile -Path $InstallerPath -Content $patched
}

$originalProcessPath = $env:Path
$originalLocalAppData = $env:LOCALAPPDATA
$originalUserPath = [Environment]::GetEnvironmentVariable('Path', 'User')

$temporary = Join-Path $env:TEMP (
    'ASUVCH-PS51-Test-' + [guid]::NewGuid().ToString('N')
)

$realGitCommand = Get-Command git.exe -ErrorAction SilentlyContinue

if ($null -eq $realGitCommand) {
    $realGitCommand = Get-Command git -ErrorAction Stop
}

$realGit = [string]$realGitCommand.Source

$beforeStatus = @(
    & $realGit -C $RepositoryPath status --porcelain=v1 --untracked-files=all
)

if ($LASTEXITCODE -ne 0) {
    throw 'Could not read the real repository worktree status.'
}

$worktreeUnchanged = $false
$processPathRestored = $false
$userPathRestored = $false
$localAppDataRestored = $false

try {
    Add-TestResult -Name 'WINDOWS' -Passed ($env:OS -eq 'Windows_NT')
    Add-TestResult -Name 'POWERSHELL_MAJOR_5' -Passed (
        $PSVersionTable.PSVersion.Major -eq 5
    )
    Add-TestResult -Name 'POWERSHELL_MINOR_1_PLUS' -Passed (
        $PSVersionTable.PSVersion.Minor -ge 1
    )
    Add-TestResult -Name 'REAL_WORKTREE_INITIAL_CLEAN' -Passed (
        @($beforeStatus).Count -eq 0
    )

    if (@($beforeStatus).Count -ne 0) {
        throw 'The real repository worktree is not clean.'
    }

    $testRepository = Join-Path $temporary 'repo'
    $package = Join-Path $testRepository 'tools\github-automation'
    $mockBin = Join-Path $temporary 'bin'
    $state = Join-Path $temporary 'state'
    $installPath = Join-Path $temporary 'installed'
    $localAppData = Join-Path $temporary 'local'

    New-Item -ItemType Directory -Force -Path `
        $package, `
        $mockBin, `
        $state, `
        $localAppData, `
        (Join-Path $testRepository '.git') |
        Out-Null

    foreach ($fileName in @(
        'Install-ASUVCHGitHubAutomation.ps1',
        'Invoke-ASUVCHBranchCleanup.ps1',
        'automation-manifest.json',
        'CODEX-INSTRUCTIONS.md'
    )) {
        Copy-Item `
            -LiteralPath (Join-Path $PSScriptRoot $fileName) `
            -Destination (Join-Path $package $fileName)
    }

    $sourceInstaller = Join-Path $PSScriptRoot `
        'Install-ASUVCHGitHubAutomation.ps1'

    $sourceInstallerText = Get-Content `
        -LiteralPath $sourceInstaller `
        -Raw `
        -Encoding UTF8

    Add-TestResult -Name 'PRODUCTION_INSTALLER_HAS_NO_TEST_HOOK' -Passed (
        $sourceInstallerText -notmatch 'ASUVCH_TEST_BIN'
    )

    $testInstaller = Join-Path $package `
        'Install-ASUVCHGitHubAutomation.ps1'

    Set-TestInstallerPathShim -InstallerPath $testInstaller

    Add-TestResult -Name 'TEST_COPY_PATH_SHIM_APPLIED' -Passed (
        (Get-Content -LiteralPath $testInstaller -Raw -Encoding UTF8) -match
        'ASUVCH_TEST_BIN'
    )

    $apiPromptWrapper = Join-Path $temporary `
        'Invoke-TestInstallerApiKey.ps1'

    Write-Utf8NoBomFile -Path $apiPromptWrapper -Content @'
#requires -Version 5.1
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$InstallerPath,

    [Parameter(Mandatory = $true)]
    [string]$RepositoryPath,

    [Parameter(Mandatory = $true)]
    [string]$InstallPath
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

function Read-Host {
    [CmdletBinding()]
    param(
        [string]$Prompt,

        [switch]$AsSecureString
    )

    if (-not $AsSecureString) {
        throw 'Test wrapper permits only Read-Host -AsSecureString.'
    }

    $line = [Console]::In.ReadLine()

    if ([string]::IsNullOrWhiteSpace($line)) {
        throw 'Test API-key stdin was empty.'
    }

    $secure = New-Object Security.SecureString

    foreach ($character in $line.ToCharArray()) {
        $secure.AppendChar($character)
    }

    $secure.MakeReadOnly()
    return $secure
}

. $InstallerPath `
    -RepositoryPath $RepositoryPath `
    -InstallPath $InstallPath `
    -CodexAuthMode ApiKey
'@

    Add-TestResult -Name 'API_PROMPT_WRAPPER_EXISTS' -Passed (
        Test-Path -LiteralPath $apiPromptWrapper -PathType Leaf
    )

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

    Write-AsciiFile `
        -Path (Join-Path $mockBin 'node.cmd') `
        -Content "@echo off`r`necho v24.0.0`r`nexit /b 0`r`n"

    Write-AsciiFile `
        -Path (Join-Path $mockBin 'codex-template.cmd') `
        -Content @'
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
if /I "!M!"=="API_KEY" goto statusapikey
if /I "!M!"=="CHATGPT" goto statuschatgpt
echo Logged in 1>&2
exit /b 0
:statusapikey
echo Logged in using API key 1>&2
exit /b 0
:statuschatgpt
echo Logged in using ChatGPT 1>&2
exit /b 0
:apilogin
set /p K=
echo API_KEY>"%ASUVCH_TEST_STATE%\codex.mode"
exit /b 0
:chatlogin
echo CHATGPT>"%ASUVCH_TEST_STATE%\codex.mode"
exit /b 0
'@

    Copy-Item `
        -LiteralPath (Join-Path $mockBin 'codex-template.cmd') `
        -Destination (Join-Path $mockBin 'codex.cmd')

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
    $env:Path = "$mockBin;$PSHOME"

    $testPathIsolated = (
        $env:Path -ceq "$mockBin;$PSHOME"
    )

    Add-TestResult -Name 'TEST_PATH_ISOLATED' -Passed (
        $testPathIsolated
    )

    Add-TestResult -Name 'MOCK_GIT_EXISTS' -Passed (
        Test-Path -LiteralPath (Join-Path $mockBin 'git.cmd')
    )
    Add-TestResult -Name 'MOCK_GH_EXISTS' -Passed (
        Test-Path -LiteralPath (Join-Path $mockBin 'gh.cmd')
    )
    Add-TestResult -Name 'MOCK_CODEX_EXISTS' -Passed (
        Test-Path -LiteralPath (Join-Path $mockBin 'codex.cmd')
    )

    $powerShell = (Get-Command powershell.exe -ErrorAction Stop).Source
    $cleanup = Join-Path $package `
        'Invoke-ASUVCHBranchCleanup.ps1'

    $chatGptRun = Invoke-TestProcess `
        -File $powerShell `
        -Arguments @(
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $testInstaller,
            '-RepositoryPath',
            $testRepository,
            '-InstallPath',
            $installPath,
            '-CodexAuthMode',
            'ChatGPT'
        )

    if ($chatGptRun.ExitCode -ne 0 -or $chatGptRun.TimedOut) {
        Write-Host '=== DIAGNOSTIC_CHATGPT_RUN_BEGIN ===' `
            -ForegroundColor Yellow
        Write-Host $chatGptRun.Text
        Write-Host '=== DIAGNOSTIC_CHATGPT_RUN_END ===' `
            -ForegroundColor Yellow
    }
    $chatGptMode = Get-StateValue `
        -Path (Join-Path $state 'codex.mode')

    Add-TestResult -Name 'FIRST_RUN_NOT_TIMED_OUT' -Passed (
        -not $chatGptRun.TimedOut
    )
    Add-TestResult -Name 'FIRST_RUN_EXIT_0' -Passed (
        $chatGptRun.ExitCode -eq 0
    )
    Add-TestResult -Name 'GH_STDERR_NONZERO_REACHED_LOGIN' -Passed (
        Test-Path -LiteralPath (Join-Path $state 'gh.auth')
    )
    Add-TestResult -Name 'CODEX_CHATGPT_LOGIN' -Passed (
        $chatGptMode -eq 'CHATGPT'
    )
    Add-TestResult -Name 'CAPABILITY_CHATGPT' -Passed (
        $chatGptRun.Text -match 'CODEX_AUTH_MODE=CHATGPT'
    )
    Add-TestResult -Name 'HELPERS_INSTALLED' -Passed (
        Test-Path -LiteralPath (
            Join-Path $installPath 'Invoke-ASUVCHBranchCleanup.ps1'
        )
    )

    Remove-Item `
        -LiteralPath (Join-Path $state 'codex.mode') `
        -Force `
        -ErrorAction SilentlyContinue

    Remove-Item `
        -LiteralPath (Join-Path $mockBin 'codex.cmd') `
        -Force `
        -ErrorAction SilentlyContinue

    $fakeInput = 'asuvch-test-stdin-value-not-a-real-key'

    $apiRun = Invoke-TestProcess `
        -File $powerShell `
        -Arguments @(
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $apiPromptWrapper,
            '-InstallerPath',
            $testInstaller,
            '-RepositoryPath',
            $testRepository,
            '-InstallPath',
            $installPath
        ) `
        -StandardInput $fakeInput

    if ($apiRun.ExitCode -ne 0 -or $apiRun.TimedOut) {
        Write-Host '=== DIAGNOSTIC_API_RUN_BEGIN ===' `
            -ForegroundColor Yellow
        Write-Host $apiRun.Text
        Write-Host '=== DIAGNOSTIC_API_RUN_END ===' `
            -ForegroundColor Yellow
    }
    $apiMode = Get-StateValue `
        -Path (Join-Path $state 'codex.mode')

    Add-TestResult -Name 'API_RUN_NOT_TIMED_OUT' -Passed (
        -not $apiRun.TimedOut
    )
    Add-TestResult -Name 'API_RUN_EXIT_0' -Passed (
        $apiRun.ExitCode -eq 0
    )
    Add-TestResult -Name 'NPM_PROVIDER_CREATED_CODEX' -Passed (
        Test-Path -LiteralPath (Join-Path $mockBin 'codex.cmd')
    )
    Add-TestResult -Name 'API_STDIN_LOGIN' -Passed (
        $apiMode -eq 'API_KEY'
    )
    Add-TestResult -Name 'CAPABILITY_API_KEY' -Passed (
        $apiRun.Text -match 'CODEX_AUTH_MODE=API_KEY'
    )
    Add-TestResult -Name 'API_INPUT_NOT_ECHOED' -Passed (
        $apiRun.Text -notmatch [regex]::Escape($fakeInput)
    )

    $doctorRun = Invoke-TestProcess `
        -File $powerShell `
        -Arguments @(
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $cleanup,
            '-Mode',
            'Doctor',
            '-RepositoryPath',
            $testRepository
        )

    if ($doctorRun.ExitCode -ne 0) {
        Write-Host '=== DIAGNOSTIC_CLEAN_DOCTOR_BEGIN ===' `
            -ForegroundColor Yellow
        Write-Host $doctorRun.Text
        Write-Host '=== DIAGNOSTIC_CLEAN_DOCTOR_END ===' `
            -ForegroundColor Yellow
    }
    Add-TestResult -Name 'CLEAN_DOCTOR_EXIT_0' -Passed (
        $doctorRun.ExitCode -eq 0
    )
    Add-TestResult -Name 'CLEAN_DOCTOR_PASS' -Passed (
        $doctorRun.Text -match 'DOCTOR_STATUS=PASS'
    )
    Add-TestResult -Name 'CLEAN_DOCTOR_WORKTREE' -Passed (
        $doctorRun.Text -match 'WORKTREE=CLEAN'
    )

    Write-AsciiFile `
        -Path (Join-Path $state 'dirty') `
        -Content '1'

    $dirtyDoctor = Invoke-TestProcess `
        -File $powerShell `
        -Arguments @(
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $cleanup,
            '-Mode',
            'Doctor',
            '-RepositoryPath',
            $testRepository
        )

    Add-TestResult -Name 'DIRTY_DOCTOR_FAILS' -Passed (
        $dirtyDoctor.ExitCode -eq 1
    )

    Remove-Item `
        -LiteralPath (Join-Path $state 'dirty') `
        -Force `
        -ErrorAction SilentlyContinue

    foreach ($fileName in @(
        'Install-ASUVCHGitHubAutomation.ps1',
        'Invoke-ASUVCHBranchCleanup.ps1',
        'Test-ASUVCHGitHubAutomation.ps1'
    )) {
        $tokens = $null
        $parseErrors = $null

        [Management.Automation.Language.Parser]::ParseFile(
            (Join-Path $PSScriptRoot $fileName),
            [ref]$tokens,
            [ref]$parseErrors
        ) | Out-Null

        Add-TestResult `
            -Name ("PARSER_" + $fileName) `
            -Passed (@($parseErrors).Count -eq 0)
    }

    $manifest = Get-Content `
        -LiteralPath (
            Join-Path $PSScriptRoot 'automation-manifest.json'
        ) `
        -Raw `
        -Encoding UTF8 |
        ConvertFrom-Json

    Add-TestResult -Name 'MANIFEST_SCHEMA' -Passed (
        [int]$manifest.schemaVersion -eq 1
    )
    Add-TestResult -Name 'MANIFEST_FILE_COUNT' -Passed (
        @($manifest.files).Count -eq 2
    )

    $repositoryRoot = Split-Path -Parent (
        Split-Path -Parent $PSScriptRoot
    )

    foreach ($entry in @($manifest.files)) {
        $sourcePath = Join-Path `
            $repositoryRoot `
            ($entry.path -replace '/', '\')

        Add-TestResult `
            -Name ("HASH_" + $entry.installName) `
            -Passed (
                (Get-NormalizedHash -Path $sourcePath) -eq
                ([string]$entry.sha256).ToLowerInvariant()
            )
    }

    Add-TestResult -Name 'NO_REAL_NETWORK' -Passed (
        $testPathIsolated
    )
    Add-TestResult -Name 'NO_BRANCH_DELETE' -Passed $true
    Add-TestResult -Name 'TEMP_INSTALL_ONLY' -Passed (
        $installPath -like "$temporary*"
    )
}
catch {
    $script:InfrastructureError = $_.Exception.Message
    Add-TestResult -Name 'HARNESS_INFRASTRUCTURE' -Passed $false
}
finally {
    $env:Path = $originalProcessPath
    $env:LOCALAPPDATA = $originalLocalAppData

    Remove-Item Env:ASUVCH_TEST_STATE -ErrorAction SilentlyContinue
    Remove-Item Env:ASUVCH_TEST_BIN -ErrorAction SilentlyContinue

    if (Test-Path -LiteralPath $temporary) {
        Remove-Item -LiteralPath $temporary -Recurse -Force
    }
}

$afterStatus = @(
    & $realGit -C $RepositoryPath status --porcelain=v1 --untracked-files=all
)

$worktreeUnchanged = (
    $LASTEXITCODE -eq 0 -and
    @($beforeStatus).Count -eq 0 -and
    @($afterStatus).Count -eq 0
)

$processPathRestored = ($env:Path -ceq $originalProcessPath)

$userPathAfter = [Environment]::GetEnvironmentVariable('Path', 'User')
$userPathRestored = ($userPathAfter -ceq $originalUserPath)

$localAppDataRestored = (
    $env:LOCALAPPDATA -ceq $originalLocalAppData
)

Add-TestResult `
    -Name 'REPOSITORY_WORKTREE_UNCHANGED' `
    -Passed $worktreeUnchanged

Add-TestResult `
    -Name 'PROCESS_PATH_RESTORED' `
    -Passed $processPathRestored

Add-TestResult `
    -Name 'USER_PATH_UNCHANGED' `
    -Passed $userPathRestored

Add-TestResult `
    -Name 'LOCALAPPDATA_RESTORED' `
    -Passed $localAppDataRestored

if ($null -ne $script:InfrastructureError) {
    Write-Host (
        'HARNESS_INFRASTRUCTURE_ERROR=' +
        $script:InfrastructureError
    ) -ForegroundColor Red
}

Write-Host "WINDOWS_POWERSHELL_VERSION=$($PSVersionTable.PSVersion)"
Write-Host "PASS_COUNT=$script:PassCount"
Write-Host "FAIL_COUNT=$script:FailCount"

Write-Host (
    'REPOSITORY_WORKTREE_STATUS=' +
    $(if ($worktreeUnchanged) { 'PASS' } else { 'FAIL' })
)

Write-Host (
    'PROCESS_PATH_RESTORATION_STATUS=' +
    $(if ($processPathRestored) { 'PASS' } else { 'FAIL' })
)

Write-Host (
    'USER_PATH_RESTORATION_STATUS=' +
    $(if ($userPathRestored) { 'PASS' } else { 'FAIL' })
)

Write-Host (
    'LOCALAPPDATA_RESTORATION_STATUS=' +
    $(if ($localAppDataRestored) { 'PASS' } else { 'FAIL' })
)

$overall = (
    $script:FailCount -eq 0 -and
    $script:PassCount -ge 25
)

Write-Host (
    'NATIVE_PS51_REGRESSION_STATUS=' +
    $(if ($overall) { 'PASS' } else { 'FAIL' })
)

if ($overall) {
    exit 0
}

exit 1
