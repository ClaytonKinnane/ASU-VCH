#requires -Version 5.1
# ASUVCH_PR30_REMOTE_LOADER=1
# ASUVCH_PR30_REMOTE_LOADER_REVISION=4
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

$parentPattern = '(?m)^\$bootstrapParent\s*=\s*\(\(& \$GitExe rev-parse ''HEAD\^''\) -join ''''\)\.Trim\(\)\s*$'
$parentRegex = New-Object Text.RegularExpressions.Regex($parentPattern)
$parentMatches = @($parentRegex.Matches($scriptText))

if (@($parentMatches).Count -ne 1) {
    throw (
        'Bootstrap-parent anchor count mismatch: ' +
        @($parentMatches).Count
    )
}

$parentEvaluator = [Text.RegularExpressions.MatchEvaluator]{
    param($match)
    return '$bootstrapParent = $ExpectedOriginalHead'
}

$patchedText = $parentRegex.Replace(
    $scriptText,
    $parentEvaluator,
    1
)

$changedPattern = '(?ms)&\s+\$GitExe\s+diff-tree\b.*?\$bootstrapHead'
$changedRegex = New-Object Text.RegularExpressions.Regex($changedPattern)
$changedMatches = @($changedRegex.Matches($patchedText))

if (@($changedMatches).Count -ne 1) {
    throw (
        'Cumulative changed-path anchor count mismatch: ' +
        @($changedMatches).Count
    )
}

$changedReplacement = @'
& $GitExe diff `
            --name-only `
            $ExpectedOriginalHead `
            $bootstrapHead
'@

$changedEvaluator = [Text.RegularExpressions.MatchEvaluator]{
    param($match)
    return $changedReplacement.TrimEnd()
}

$secondPatch = $changedRegex.Replace(
    $patchedText,
    $changedEvaluator,
    1
)

[IO.File]::WriteAllText(
    $temporaryScript,
    $secondPatch,
    (New-Object Text.UTF8Encoding($false))
)

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
