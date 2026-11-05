$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$dataPath = Join-Path $projectRoot '.local\postgres-data'
if (-not (Test-Path -LiteralPath (Join-Path $dataPath 'PG_VERSION'))) { Write-Host 'Tidak ada cluster PostgreSQL lokal proyek.'; return }
$version = (Get-Content -LiteralPath (Join-Path $dataPath 'PG_VERSION') -Raw).Trim()
$pgctl = Join-Path "C:\Program Files\PostgreSQL\$version\bin" 'pg_ctl.exe'
if (-not (Test-Path -LiteralPath $pgctl)) { throw "pg_ctl PostgreSQL $version tidak ditemukan." }
& $pgctl -D $dataPath -m fast -w stop
if ($LASTEXITCODE -ne 0) { throw 'Cluster proyek gagal dihentikan. Periksa status PostgreSQL.' }
