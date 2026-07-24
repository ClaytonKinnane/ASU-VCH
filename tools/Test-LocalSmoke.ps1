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
        throw "Неожиданный HTTP-статус $StatusCode для $Uri. Ожидалось: $($ExpectedStatusCodes -join ', ')."
    }

    Write-Host "OK $StatusCode $Uri" -ForegroundColor Green
}

$PreviousCertificateCallback = [System.Net.ServicePointManager]::ServerCertificateValidationCallback

try {
    if ($AllowInvalidCertificate) {
        [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
    }

    Invoke-SmokeRequest -Uri "$BaseUrl/" -ExpectedStatusCodes @(200)
    Invoke-SmokeRequest -Uri "$BaseUrl/health.php" -ExpectedStatusCodes @(200)
    Invoke-SmokeRequest -Uri "$BaseUrl/admin/" -ExpectedStatusCodes @(302)

    $Health = Invoke-RestMethod -Uri "$BaseUrl/health.php" -Method Get
    if ($Health.status -ne 'ok') {
        throw 'health.php не вернул status=ok.'
    }
    if ($Health.database.status -ne 'connected') {
        throw 'health.php не подтвердил подключение к базе данных.'
    }

    Write-Host "`nSmoke-тест завершен успешно." -ForegroundColor Green
    exit 0
}
catch {
    Write-Host "`nSMOKE-ТЕСТ НЕ ПРОЙДЕН." -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
finally {
    if ($AllowInvalidCertificate) {
        [System.Net.ServicePointManager]::ServerCertificateValidationCallback = $PreviousCertificateCallback
    }
}
