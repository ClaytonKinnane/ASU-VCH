#requires -Version 5.1

[CmdletBinding()]
param(
    [string]$PhpExecutable = 'php'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$RepositoryRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$PhpFiles = @(Get-ChildItem -LiteralPath $RepositoryRoot -Recurse -File -Filter '*.php' | Where-Object {
    $_.FullName -notmatch '[\\/]vendor[\\/]'
})

if ($PhpFiles.Count -eq 0) {
    Write-Host 'PHP-файлы не найдены.'
    exit 0
}

$Failures = New-Object System.Collections.Generic.List[string]

foreach ($File in $PhpFiles) {
    $Output = & $PhpExecutable -l $File.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        $Failures.Add("$($File.FullName)`n$($Output -join [Environment]::NewLine)")
        Write-Host "FAIL $($File.FullName)" -ForegroundColor Red
    }
    else {
        Write-Host "OK   $($File.FullName)" -ForegroundColor Green
    }
}

if ($Failures.Count -gt 0) {
    Write-Host "`nОбнаружены синтаксические ошибки:" -ForegroundColor Red
    $Failures | ForEach-Object { Write-Host "`n$_" -ForegroundColor Red }
    exit 1
}

Write-Host "`nПроверено PHP-файлов: $($PhpFiles.Count). Ошибок нет." -ForegroundColor Green
exit 0
