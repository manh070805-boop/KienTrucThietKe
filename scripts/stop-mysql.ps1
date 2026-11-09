$ErrorActionPreference='Stop'
$mysqlFolders=@(Get-ChildItem -Path 'D:\laragon\bin\mysql' -Directory -Filter 'mysql-8.4*')
$adminExe=Join-Path $mysqlFolders[-1].FullName 'bin\mysqladmin.exe'
& $adminExe --no-defaults -h 127.0.0.1 -P 3307 -u root shutdown
if($LASTEXITCODE -ne 0){throw 'Không dừng được MySQL riêng. Kiểm tra cổng và thông tin đăng nhập.'}
