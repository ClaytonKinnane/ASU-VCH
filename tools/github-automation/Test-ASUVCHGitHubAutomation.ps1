#requires -Version 5.1
# ASUVCH_PR30_REMOTE_LOADER=1
# ASUVCH_PR30_REMOTE_LOADER_REVISION=8
[CmdletBinding()]
param(
    [string]$RepositoryPath = 'C:\Project\ASU-VCH'
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$ChunkPaths = @(
    'docs/architecture/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-ARCHITECTURE.md',
    'docs/specification/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-SPECIFICATION.md',
    'docs/review/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-FORMAL-REVIEW.md',
    'docs/decisions/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-APPROVAL.md',
    'docs/implementation/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-IMPLEMENTATION.md',
    'docs/testing/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-VALIDATION.md',
    'docs/review/GITHUB-LOCAL-AUTOMATION-PS51-FIRST-RUN-CORRECTION-PR-FINAL-REVIEW.md',
    'tools/github-automation/Install-ASUVCHGitHubAutomation.ps1',
    'tools/github-automation/Invoke-ASUVCHBranchCleanup.ps1',
    'tools/github-automation/README.md',
    'tools/github-automation/CODEX-INSTRUCTIONS.md'
)

function Replace-RegexExactlyOnce {
    param(
        [Parameter(Mandatory = $true)][string]$Text,
        [Parameter(Mandatory = $true)][string]$Pattern,
        [Parameter(Mandatory = $true)][string]$Replacement,
        [Parameter(Mandatory = $true)][string]$Label
    )

    $regex = New-Object Text.RegularExpressions.Regex($Pattern)
    $matches = @($regex.Matches($Text))

    if (@($matches).Count -ne 1) {
        throw "$Label anchor count mismatch: $(@($matches).Count)"
    }

    $evaluator = [Text.RegularExpressions.MatchEvaluator]{
        param($match)
        return $Replacement
    }

    return $regex.Replace($Text, $evaluator, 1)
}

Set-Location -LiteralPath $RepositoryPath

$encodedParts = foreach ($relativePath in @($ChunkPaths)) {
    $absolutePath = Join-Path $RepositoryPath $relativePath

    if (-not (Test-Path -LiteralPath $absolutePath -PathType Leaf)) {
        throw "Remote orchestrator chunk is missing: $relativePath"
    }

    $chunkContent = Get-Content `
        -LiteralPath $absolutePath `
        -Raw `
        -Encoding ASCII

    if ($null -eq $chunkContent) {
        ''
    }
    else {
        ([string]$chunkContent).Trim()
    }
}

$encoded = (@($encodedParts) -join '')

if ([string]::IsNullOrWhiteSpace($encoded)) {
    throw 'Remote orchestrator payload is empty.'
}

$temporaryScript = Join-Path $env:TEMP (
    'ASUVCH-PR30-Orchestrator-' +
    [guid]::NewGuid().ToString('N') +
    '.ps1'
)

$compressedBytes = [Convert]::FromBase64String($encoded)
$inputStream = New-Object IO.MemoryStream(,$compressedBytes)
$gzipStream = New-Object IO.Compression.GzipStream(
    $inputStream,
    [IO.Compression.CompressionMode]::Decompress
)
$outputStream = New-Object IO.MemoryStream

try {
    $gzipStream.CopyTo($outputStream)
    [IO.File]::WriteAllBytes(
        $temporaryScript,
        $outputStream.ToArray()
    )
}
finally {
    $outputStream.Dispose()
    $gzipStream.Dispose()
    $inputStream.Dispose()
}

$utf8 = New-Object Text.UTF8Encoding($false, $true)
$scriptText = $utf8.GetString(
    [IO.File]::ReadAllBytes($temporaryScript)
).Replace("`r`n", "`n").Replace("`r", "`n")

$parentReplacement = '$bootstrapParent = $ExpectedOriginalHead'
$patchedText = Replace-RegexExactlyOnce `
    -Text $scriptText `
    -Pattern '(?m)^\$bootstrapParent\s*=\s*\(\(& \$GitExe rev-parse ''HEAD\^''\) -join ''''\)\.Trim\(\)\s*$' `
    -Replacement $parentReplacement `
    -Label 'bootstrap parent'

$changedReplacement = @'
& $GitExe diff `
            --name-only `
            $ExpectedOriginalHead `
            $bootstrapHead
'@.TrimEnd()

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?ms)&\s+\$GitExe\s+diff-tree\b.*?\$bootstrapHead' `
    -Replacement $changedReplacement `
    -Label 'cumulative changed paths'

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?m)^& \$GitExe add -- \$ExpectedChangedPaths\s*$' `
    -Replacement '& $GitExe add -A' `
    -Label 'corrective git add'

$networkHelpers = @'
$ProgressPreference = 'SilentlyContinue'

function Get-RemoteHeadWithRetry {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][string]$Ref,
        [int]$Attempts = 5
    )

    $lastMessage = ''

    for ($attempt = 1; $attempt -le $Attempts; $attempt++) {
        $lines = @(
            & $GitExe ls-remote --heads origin $Ref 2>&1
        )
        $exitCode = $LASTEXITCODE
        $text = (@($lines | ForEach-Object { [string]$_ }) -join "`n").Trim()

        if ($exitCode -eq 0) {
            if ([string]::IsNullOrWhiteSpace($text)) {
                throw "Remote ref not found: $Ref"
            }

            $firstLine = ($text -split "`r?`n")[0]
            $head = ($firstLine -split '\s+')[0]

            if ($head -notmatch '^[0-9a-f]{40}$') {
                throw "Unexpected ls-remote output for $Ref: $text"
            }

            return $head
        }

        $lastMessage = $text

        if ($attempt -lt $Attempts) {
            $delay = [Math]::Min(2 * $attempt, 8)
            Write-Host (
                "REMOTE_RETRY=$Ref ATTEMPT=$attempt " +
                "EXIT_CODE=$exitCode DELAY_SECONDS=$delay"
            ) -ForegroundColor Yellow
            Start-Sleep -Seconds $delay
        }
    }

    throw (
        "git ls-remote failed after $Attempts attempts for $Ref. " +
        "Last=$lastMessage"
    )
}

function Invoke-GitPushWithRetry {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][string]$Refspec,
        [int]$Attempts = 3
    )

    for ($attempt = 1; $attempt -le $Attempts; $attempt++) {
        & $GitExe push origin $Refspec
        $exitCode = $LASTEXITCODE

        if ($exitCode -eq 0) {
            return
        }

        if ($attempt -lt $Attempts) {
            $delay = [Math]::Min(2 * $attempt, 6)
            Write-Host (
                "PUSH_RETRY_ATTEMPT=$attempt " +
                "EXIT_CODE=$exitCode DELAY_SECONDS=$delay"
            ) -ForegroundColor Yellow
            Start-Sleep -Seconds $delay
        }
    }

    throw "git push failed after $Attempts attempts."
}
'@

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?m)^\$ProgressPreference = ''SilentlyContinue''\s*$' `
    -Replacement $networkHelpers.TrimEnd() `
    -Label 'network retry helpers'

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?ms)^\$remoteBranchLine\s*=.*?(?=^\$remoteMainLine\s*=)' `
    -Replacement ('$remoteBranchHead = Get-RemoteHeadWithRetry -Ref "refs/heads/$Branch"' + "`n`n") `
    -Label 'initial remote branch lookup'

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?ms)^\$remoteMainLine\s*=.*?(?=^if \(\$remoteBranchHead -ne )' `
    -Replacement ('$remoteMainHead = Get-RemoteHeadWithRetry -Ref ''refs/heads/main''' + "`n`n") `
    -Label 'initial main lookup'

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?ms)^\$remoteBeforePushLine\s*=.*?(?=^if \(\$remoteBeforePush -ne )' `
    -Replacement ('$remoteBeforePush = Get-RemoteHeadWithRetry -Ref "refs/heads/$Branch"' + "`n`n") `
    -Label 'pre-push remote lookup'

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?ms)^& \$GitExe push origin "HEAD:refs/heads/\$Branch"\s*\n\s*if \(\$LASTEXITCODE -ne 0\) \{\s*\n\s*throw "git push failed with exit code \$LASTEXITCODE\."\s*\n\}\s*$' `
    -Replacement ('Invoke-GitPushWithRetry -Refspec "HEAD:refs/heads/$Branch"' + "`n`n") `
    -Label 'push retry'

$patchedText = Replace-RegexExactlyOnce `
    -Text $patchedText `
    -Pattern '(?ms)^\$remoteAfterPushLine\s*=.*?(?=^if \(\$remoteAfterPush -ne )' `
    -Replacement ('$remoteAfterPush = Get-RemoteHeadWithRetry -Ref "refs/heads/$Branch"' + "`n`n") `
    -Label 'post-push remote lookup'

[IO.File]::WriteAllText(
    $temporaryScript,
    $patchedText,
    (New-Object Text.UTF8Encoding($true))
)

$tokens = $null
$parseErrors = $null
[Management.Automation.Language.Parser]::ParseFile(
    $temporaryScript,
    [ref]$tokens,
    [ref]$parseErrors
) | Out-Null

$errors = @($parseErrors)
if (@($errors).Count -ne 0) {
    foreach ($parseError in @($errors)) {
        Write-Host (
            'REMOTE_ORCHESTRATOR_PARSE_ERROR={0}:{1}:{2}' -f
            $parseError.Extent.StartLineNumber,
            $parseError.Extent.StartColumnNumber,
            $parseError.Message
        ) -ForegroundColor Red
    }

    throw 'Remote orchestrator parser validation failed.'
}

Write-Host 'REMOTE_ORCHESTRATOR_ENCODING=UTF8_BOM' -ForegroundColor Green
Write-Host 'REMOTE_ORCHESTRATOR_GIT_ADD_MODE=ALL_NO_PATHSPEC' -ForegroundColor Green
Write-Host 'REMOTE_ORCHESTRATOR_NETWORK_RETRY=ENABLED' -ForegroundColor Green
Write-Host 'REMOTE_ORCHESTRATOR_PARSER=PASS' -ForegroundColor Green

try {
    & powershell.exe `
        -NoProfile `
        -ExecutionPolicy Bypass `
        -File $temporaryScript `
        -RepositoryPath $RepositoryPath

    $exitCode = $LASTEXITCODE
}
catch {
    Write-Host ('REMOTE_ORCHESTRATOR_LOADER_ERROR=' + $_.Exception.Message) `
        -ForegroundColor Red
    Write-Host ('REMOTE_ORCHESTRATOR_LOADER_POSITION=' + $_.InvocationInfo.PositionMessage) `
        -ForegroundColor Red
    throw
}
finally {
    Remove-Item `
        -LiteralPath $temporaryScript `
        -Force `
        -ErrorAction SilentlyContinue
}

exit $exitCode
