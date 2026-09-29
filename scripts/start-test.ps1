$ErrorActionPreference = 'Stop'
$pipasRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $pipasRoot
$testName = (Get-Content tmp\test-db-name.txt).Trim()
if ($testName -notmatch '^pipas_tlalpan_test_[0-9]+$') { throw 'Nombre de base de pruebas inválido.' }
$env:PIPAS_DB_NAME = $testName
$env:PIPAS_ENVIRONMENT = 'testing'
$pipasTestPort = Get-NetTCPConnection -LocalPort 8089 -State Listen -ErrorAction SilentlyContinue
if (-not $pipasTestPort) {
    $testWeb = Start-Process -FilePath (Get-Command php).Source -ArgumentList '-S','127.0.0.1:8089','-t','public','public/router.php' -WorkingDirectory $pipasRoot -WindowStyle Hidden -RedirectStandardOutput 'storage\test-web.stdout.log' -RedirectStandardError 'storage\test-web.stderr.log' -PassThru
    $testWeb.Id | Set-Content storage\test-web.pid
}
Write-Host 'Pruebas: http://127.0.0.1:8089 (datos ficticios)'
