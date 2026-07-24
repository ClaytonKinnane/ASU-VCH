#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$BaseUrl = 'https://asu-vch.local',
    [switch]$AllowInvalidCertificate
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$Curl = Get-Command curl.exe -ErrorAction SilentlyContinue
if ($null -eq $Curl) {
    throw 'curl.exe was not found in PATH.'
}

function Invoke-SmokeRequest {
    param(
        [Parameter(Mandatory = $true)][string]$Uri,
        [Parameter(Mandatory = $true)][int[]]$ExpectedStatusCodes,
        [string]$OutputFile
    )

    $Arguments = @('--silent', '--show-error', '--output')

    if ([string]::IsNullOrWhiteSpace($OutputFile)) {
        $Arguments += 'NUL'
    }
    else {
        $Arguments += $OutputFile
    }

    $Arguments += @('--write-out', '%{http_code}')

    if ($AllowInvalidCertificate) {
        $Arguments += '--insecure'
    }

    $Arguments += $Uri

    $StatusText = & $Curl.Source @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "curl.exe failed for $Uri with exit code $LASTEXITCODE."
    }

    $StatusCode = 0
    if (-not [int]::TryParse(($StatusText | Out-String).Trim(), [ref]$StatusCode)) {
        throw "curl.exe returned an invalid HTTP status for ${Uri}: $StatusText"
    }

    if ($ExpectedStatusCodes -notcontains $StatusCode) {
        throw "Unexpected HTTP status $StatusCode for $Uri. Expected: $($ExpectedStatusCodes -join ', ')."
    }

    Write-Host "OK $StatusCode $Uri" -ForegroundColor Green
}

$HealthFile = Join-Path ([System.IO.Path]::GetTempPath()) ("asu-vch-health-{0}.json" -f [guid]::NewGuid().ToString('N'))

try {
    Invoke-SmokeRequest -Uri "$BaseUrl/" -ExpectedStatusCodes @(200)
    Invoke-SmokeRequest -Uri "$BaseUrl/health.php" -ExpectedStatusCodes @(200) -OutputFile $HealthFile
    Invoke-SmokeRequest -Uri "$BaseUrl/admin/" -ExpectedStatusCodes @(302)

    $Health = Get-Content -LiteralPath $HealthFile -Raw | ConvertFrom-Json
    if ($Health.status -ne 'ok') {
        throw 'health.php did not return status=ok.'
    }
    if ($Health.database.status -ne 'connected') {
        throw 'health.php did not confirm a database connection.'
    }

    Write-Host "`nSmoke test completed successfully." -ForegroundColor Green
    exit 0
}
catch {
    Write-Host "`nSMOKE TEST FAILED." -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
finally {
    if (Test-Path -LiteralPath $HealthFile) {
        Remove-Item -LiteralPath $HealthFile -Force
    }
}
