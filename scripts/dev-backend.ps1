param(
    [switch]$PrepareOnly,
    [string]$PostgresBin
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$localRoot = Join-Path $projectRoot '.local'
$backendRoot = Join-Path $projectRoot 'backend'
New-Item -ItemType Directory -Force -Path $localRoot | Out-Null

if (-not $PostgresBin) {
    $installations = Get-ChildItem 'C:\Program Files\PostgreSQL' -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -match '^\d+$' } | Sort-Object { [int]$_.Name } -Descending
    foreach ($installation in $installations) {
        $candidate = Join-Path $installation.FullName 'bin'
        if (Test-Path -LiteralPath (Join-Path $candidate 'initdb.exe')) { $PostgresBin = $candidate; break }
    }
}
if (-not $PostgresBin -or -not (Test-Path -LiteralPath (Join-Path $PostgresBin 'pg_ctl.exe'))) {
    throw 'PostgreSQL belum ditemukan. Pasang PostgreSQL atau gunakan -PostgresBin <folder-bin>.'
}

$dataPath = Join-Path $localRoot 'postgres-data'
$logPath = Join-Path $localRoot 'postgres.log'
$passwordFile = Join-Path $localRoot 'postgres-init-password'
if (-not (Test-Path -LiteralPath (Join-Path $dataPath 'PG_VERSION'))) {
    Write-Host 'Menyiapkan cluster PostgreSQL khusus proyek di .local/postgres-data ...'
    Set-Content -LiteralPath $passwordFile -Value 'stockflow' -Encoding ASCII
    try {
        & (Join-Path $PostgresBin 'initdb.exe') -D $dataPath -U stockflow --auth=scram-sha-256 --pwfile=$passwordFile --encoding=UTF8 --locale=C
        if ($LASTEXITCODE -ne 0) { throw 'initdb gagal. Lihat pesan PostgreSQL di atas.' }
    } finally {
        Remove-Item -LiteralPath $passwordFile -ErrorAction SilentlyContinue
    }
}

& (Join-Path $PostgresBin 'pg_isready.exe') -h 127.0.0.1 -p 55432 *> $null
if ($LASTEXITCODE -ne 0) {
    & (Join-Path $PostgresBin 'pg_ctl.exe') -D $dataPath -l $logPath -o '-h 127.0.0.1 -p 55432' -w -t 30 start
    if ($LASTEXITCODE -ne 0) {
        # Some managed Windows sessions cannot create pg_ctl's restricted token.
        # Running the installed server as the current user needs no service change.
        Write-Host 'Mencoba menjalankan postgres langsung sebagai proses pengguna ...'
        $serverArguments = @('-D', ('"' + $dataPath + '"'), '-h', '127.0.0.1', '-p', '55432')
        Start-Process -FilePath (Join-Path $PostgresBin 'postgres.exe') -ArgumentList $serverArguments -WindowStyle Hidden -RedirectStandardOutput (Join-Path $localRoot 'postgres-stdout.log') -RedirectStandardError $logPath | Out-Null
        $ready = $false
        for ($attempt = 0; $attempt -lt 30; $attempt++) {
            & (Join-Path $PostgresBin 'pg_isready.exe') -h 127.0.0.1 -p 55432 *> $null
            if ($LASTEXITCODE -eq 0) { $ready = $true; break }
            Start-Sleep -Milliseconds 200
        }
        if (-not $ready) { throw "PostgreSQL gagal dimulai. Periksa $logPath dan pastikan port 55432 bebas." }
    }
}

$previousPassword = $env:PGPASSWORD
try {
    $env:PGPASSWORD = 'stockflow'
    $connectedData = & (Join-Path $PostgresBin 'psql.exe') -X -w -h 127.0.0.1 -p 55432 -U stockflow -d postgres -tAc 'SHOW data_directory'
    if ($LASTEXITCODE -ne 0) { throw 'Koneksi PostgreSQL proyek gagal.' }
    if ([System.IO.Path]::GetFullPath(($connectedData -join '').Trim()) -ne [System.IO.Path]::GetFullPath($dataPath)) {
        throw 'Port 55432 dipakai cluster lain. Skrip berhenti untuk menjaga database tersebut.'
    }
    $exists = & (Join-Path $PostgresBin 'psql.exe') -X -w -h 127.0.0.1 -p 55432 -U stockflow -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='stockflow'"
    if ($LASTEXITCODE -ne 0) { throw 'Koneksi PostgreSQL proyek gagal.' }
    if (($exists -join '').Trim() -ne '1') {
        & (Join-Path $PostgresBin 'createdb.exe') -w -h 127.0.0.1 -p 55432 -U stockflow stockflow
        if ($LASTEXITCODE -ne 0) { throw 'Pembuatan database stockflow gagal.' }
    }
} finally {
    $env:PGPASSWORD = $previousPassword
}

$envFile = Join-Path $backendRoot '.env'
if (-not (Test-Path -LiteralPath $envFile)) { Copy-Item -LiteralPath (Join-Path $backendRoot '.env.example') -Destination $envFile }
Write-Host 'PostgreSQL proyek siap pada 127.0.0.1:55432 (database: stockflow).'
if ($PrepareOnly) { return }

if (-not (Get-Command go -ErrorAction SilentlyContinue)) { throw 'Go belum ditemukan dalam PATH.' }
$env:GOCACHE = Join-Path $localRoot 'go-build'
$env:GOMODCACHE = Join-Path $localRoot 'go-mod'
Push-Location $backendRoot
try {
    if (-not (Test-Path -LiteralPath (Join-Path $backendRoot 'vendor\modules.txt'))) {
        Write-Host 'Memeriksa dependency Go ...'
        & go mod tidy
        if ($LASTEXITCODE -ne 0) { throw 'Dependency gagal diunduh. Periksa konfigurasi proxy jaringan.' }
    }
    Write-Host 'Membangun backend ...'
    $binaryPath = Join-Path $localRoot 'stockflow.exe'
    & go build -o $binaryPath ./cmd/server
    if ($LASTEXITCODE -ne 0) { throw 'Build backend gagal. Periksa pesan compiler di atas.' }
    Write-Host 'Menjalankan API. Migrasi dan seed diterapkan otomatis satu kali. Tekan Ctrl+C untuk menghentikan API.'
    & $binaryPath
    if ($LASTEXITCODE -ne 0) { throw 'Backend berhenti dengan error. Periksa pesan di atas.' }
} finally {
    Pop-Location
}
