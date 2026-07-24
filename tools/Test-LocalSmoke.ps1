#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$BaseUrl = 'https://asu-vch.local',
    [switch]$AllowInvalidCertificate
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Invoke-SmokeRequest {
    param(
        [Parameter(Mandatory = $true)][string]$Uri,
        [Parameter(Mandatory = $true)][int[]]$ExpectedStatusCodes
    )

    try {
        $Parameters = @{
            Uri = $Uri
            Method = 'GET'
            MaximumRedirection = 0
            UseBasicParsing = $true
            ErrorAction = 'Stop'
        }

        $Response = Invoke-WebRequest @Parameters
        $StatusCode = [int]$Response.StatusCode
    }
    catch [System.Net.WebException] {
        if ($null -eq $_.Exception.Response) {
            throw
        }
        $StatusCode = [int]$_.Exception.Response.StatusCode
    }

    if ($ExpectedStatusCodes -notcontains $StatusCode) {
        throw "Unexpected HTTP status $StatusCode for $Uri. Expected: $($ExpectedStatusCodes -join ', ')."
    }

    Write-Host "OK $StatusCode $Uri" -ForegroundColor Green
}

$PreviousCertificateCallback = [System.Net.ServicePointManager]::ServerCertificateValidationCallback
$PreviousSecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol

try {
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

    if ($AllowInvalidCertificate) {
        [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
    }

    Invoke-SmokeRequest -Uri "$BaseUrl/" -ExpectedStatusCodes @(200)
    Invoke-SmokeRequest -Uri "$BaseUrl/health.php" -ExpectedStatusCodes @(200)
    Invoke-SmokeRequest -Uri "$BaseUrl/admin/" -ExpectedStatusCodes @(302)

    $Health = Invoke-RestMethod -Uri "$BaseUrl/health.php" -Method Get
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
    [System.Net.ServicePointManager]::SecurityProtocol = $PreviousSecurityProtocol

    if ($AllowInvalidCertificate) {
        [System.Net.ServicePointManager]::ServerCertificateValidationCallback = $PreviousCertificateCallback
    }
}
