param([int]$Port = 3307)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$mysqlFolders = @(Get-ChildItem -Path 'D:\laragon\bin\mysql' -Directory -Filter 'mysql-8.4*' -ErrorAction SilentlyContinue)
if (!$mysqlFolders.Count) { throw 'Không tìm thấy MySQL 8.4 Laragon tại D:\laragon\bin\mysql. Xem docs/SETUP.md.' }
$mysqlBase = $mysqlFolders[-1].FullName
$mysqlExe = Join-Path $mysqlBase 'bin\mysqld.exe'
$mysqlData = Join-Path $projectRoot 'storage\app\mysql-data'
$mysqlLog = Join-Path $projectRoot 'storage\logs\mysql.log'
$mysqlPid = Join-Path $projectRoot 'storage\app\mysql.pid'
$probe = New-Object Net.Sockets.TcpClient
try { $probe.Connect('127.0.0.1',$Port); Write-Host "Cổng $Port đang chạy. Không khởi động instance khác."; return } catch {} finally { $probe.Dispose() }
if (!(Test-Path -LiteralPath (Join-Path $mysqlData 'mysql'))) {
    if ((Test-Path -LiteralPath $mysqlData) -and (Get-ChildItem -LiteralPath $mysqlData -Force | Measure-Object).Count -gt 0) { throw 'Thư mục MySQL chưa hoàn tất nhưng có dữ liệu. Không tự xóa; kiểm tra trước.' }
    New-Item -ItemType Directory -Force -Path $mysqlData | Out-Null
    & $mysqlExe --no-defaults --initialize-insecure "--basedir=$mysqlBase" "--datadir=$mysqlData" --console
    if ($LASTEXITCODE -ne 0) { throw 'Khởi tạo MySQL thất bại.' }
}
Start-Process -FilePath $mysqlExe -ArgumentList '--no-defaults', "--basedir=$($mysqlBase.Replace('\','/'))", "--datadir=$($mysqlData.Replace('\','/'))", "--port=$Port", '--bind-address=127.0.0.1', '--mysqlx=OFF', "--log-error=$($mysqlLog.Replace('\','/'))", "--pid-file=$($mysqlPid.Replace('\','/'))" -WindowStyle Hidden | Out-Null
$ready = $false
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    $probe = New-Object Net.Sockets.TcpClient
    try { $probe.Connect('127.0.0.1',$Port); $ready = $true; break } catch { Start-Sleep -Milliseconds 500 } finally { $probe.Dispose() }
}
if (!$ready) { throw "MySQL chưa sẵn sàng. Xem $mysqlLog" }
Write-Host "MySQL riêng đã chạy tại 127.0.0.1:$Port. Dữ liệu: $mysqlData"
