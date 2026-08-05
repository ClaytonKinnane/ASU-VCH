#requires -Version 5.1
# ASUVCH_PR30_REMOTE_LOADER=1
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

    (
        Get-Content `
            -LiteralPath $absolutePath `
            -Raw `
            -Encoding ASCII
    ).Trim()
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

try {
    & powershell.exe `
        -NoProfile `
        -ExecutionPolicy Bypass `
        -File $temporaryScript `
        -RepositoryPath $RepositoryPath

    $exitCode = $LASTEXITCODE
}
finally {
    Remove-Item `
        -LiteralPath $temporaryScript `
        -Force `
        -ErrorAction SilentlyContinue
}

exit $exitCode
