$ErrorActionPreference = 'Stop'
$pipasRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $pipasRoot
$pipasBin = 'C:\Program Files\MariaDB 11.8\bin\mariadbd.exe'
if (Test-Path -LiteralPath (Join-Path $pipasRoot 'storage\mariadb\my.ini')) {
    $pipasPort = Get-NetTCPConnection -LocalPort 3308 -State Listen -ErrorAction SilentlyContinue
    if (-not $pipasPort) {
        $dbArgs = @('--no-defaults', '--basedir="C:\Program Files\MariaDB 11.8"', ('--datadir="' + (Join-Path $pipasRoot 'storage\mariadb') + '"'), '--port=3308', '--bind-address=127.0.0.1', '--skip-log-bin', '--console')
        $dbProcess = Start-Process -FilePath $pipasBin -ArgumentList $dbArgs -WindowStyle Hidden -RedirectStandardOutput 'storage\db.stdout.log' -RedirectStandardError 'storage\db.stderr.log' -PassThru
        $dbProcess.Id | Set-Content storage\db.pid
    }
}
$pipasHttp = Get-NetTCPConnection -LocalPort 8088 -State Listen -ErrorAction SilentlyContinue
if (-not $pipasHttp) {
    $webProcess = Start-Process -FilePath (Get-Command php).Source -ArgumentList '-S','127.0.0.1:8088','-t','public','public/router.php' -WorkingDirectory $pipasRoot -WindowStyle Hidden -RedirectStandardOutput 'storage\web.stdout.log' -RedirectStandardError 'storage\web.stderr.log' -PassThru
    $webProcess.Id | Set-Content storage\web.pid
}
Write-Host 'PIPAS: http://127.0.0.1:8088'
Write-Host 'Credenciales iniciales: storage/acceso-local.txt'
