param([switch]$Dev)
$ErrorActionPreference='Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
& (Join-Path $PSScriptRoot 'start-mysql.ps1')
$phpCommand = Get-Command php.exe -ErrorAction SilentlyContinue
if (!$phpCommand) {
    $phpCandidates=@(Get-ChildItem -Path 'D:\laragon\bin\php' -Directory -Filter 'php-8.*')
    if(!$phpCandidates.Count){throw 'Không tìm thấy PHP Laragon.'}
    $phpExe=Join-Path $phpCandidates[-1].FullName 'php.exe'
} else { $phpExe=$phpCommand.Source }
if (!(Test-Path -LiteralPath '.env')) { throw 'Chưa có .env. Làm theo docs/SETUP.md trước.' }
$viteProcess=$null
try {
    if ($Dev) {
        $viteEntry=Join-Path $projectRoot 'node_modules/vite/bin/vite.js'
        $viteProcess=Start-Process -FilePath (Get-Command node.exe).Source -ArgumentList ('"'+$viteEntry+'"') -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput 'storage/logs/vite-output.log' -RedirectStandardError 'storage/logs/vite-error.log' -PassThru
    } elseif (!(Test-Path -LiteralPath 'public/build/manifest.json')) { throw 'Chạy npm run build trước khi khởi động.' }
    if (!$Dev -and (Test-Path -LiteralPath 'public/hot')) { Remove-Item -LiteralPath (Join-Path $projectRoot 'public/hot') }
    $probe=New-Object Net.Sockets.TcpClient
    $webRunning=$false
    try{$probe.Connect('127.0.0.1',8000);$webRunning=$true}catch{}finally{$probe.Dispose()}
    if($webRunning){
        Write-Host 'Cổng web 8000 đang chạy. Kiểm tra http://127.0.0.1:8000.'
        if($Dev){Write-Host 'Vite đang chạy nền; Ctrl+C dừng phiên Vite này.';$viteProcess.WaitForExit()}
        return
    }
    Write-Host 'URL: http://127.0.0.1:8000 | Ctrl+C để dừng web và Vite của phiên này.'
    & $phpExe artisan serve --host=127.0.0.1 --port=8000 --no-reload --tries=0
} finally {
    if($viteProcess -and !$viteProcess.HasExited){Stop-Process -Id $viteProcess.Id}
    if($Dev -and (Test-Path -LiteralPath 'public/hot')){Remove-Item -LiteralPath (Join-Path $projectRoot 'public/hot')}
}
