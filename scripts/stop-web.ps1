param([ValidateSet(8000,8001)][int]$Port = 8000)
$ErrorActionPreference='Stop'
$projectRoot=Split-Path -Parent $PSScriptRoot
$record=Join-Path $projectRoot "storage/logs/web-server-$Port.json"
if (!(Test-Path -LiteralPath $record)) { throw 'Không có PID server nền. Với server chạy bằng run.bat/dev.bat, dùng Ctrl+C trong terminal đó.' }
$serverRecord=Get-Content -LiteralPath $record -Raw | ConvertFrom-Json
if($serverRecord.project_root -ne $projectRoot -or [int]$serverRecord.port -ne $Port) { throw 'PID record không thuộc project/cổng yêu cầu.' }
foreach($serverPid in $serverRecord.pids) {
    $process=Get-CimInstance Win32_Process -Filter "ProcessId=$serverPid" -ErrorAction SilentlyContinue
    if(!$process){continue}
    $isArtisan=$process.CommandLine -match "artisan.*serve.*--port=$Port"
    $isPhpServer=($process.CommandLine -match "-S 127\.0\.0\.1:$Port\s") -and ($process.CommandLine -match [regex]::Escape($projectRoot))
    if($process.Name -ne 'php.exe' -or (!$isArtisan -and !$isPhpServer)) { throw "PID $serverPid không còn khớp server; không dừng." }
    Stop-Process -Id $serverPid
}
Write-Host "Đã dừng server nền cổng $Port của project."
