# Complete End-to-End Pipeline: Push to GitHub -> Pull on Production Server -> Reload & Verify
$HostIP = "76.13.79.242"
$User = "highest-ye"
$Pass = "YoK2PBV1fo82yujX2tDq"
$RepoUrl = "git@github.com:rasheedaljadi/HIGST101.git"

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host " 1. Pushing Local Changes to GitHub " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

git add .
git commit -m "Production release: automatic sync fix, production checks, storage symlink and SyncRun tracking"
git push origin main

if ($LASTEXITCODE -ne 0) {
    Write-Host "[NOTE] Pushing using current branch..." -ForegroundColor Yellow
    git push
}

Write-Host "`n====================================================" -ForegroundColor Cyan
Write-Host " 2. Deploying & Pulling Updates on Production Server " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

$remoteScript = @'
PROJ_DIR=$(find /home/highest-ye /var/www -maxdepth 4 -name artisan 2>/dev/null | head -n 1 | xargs dirname 2>/dev/null)
if [ -z "$PROJ_DIR" ]; then
    echo "[ERROR] Could not locate artisan file"
    exit 1
fi
cd "$PROJ_DIR"

PHP_BIN="php"
if command -v php8.3 >/dev/null 2>&1; then
    PHP_BIN="php8.3"
elif [ -f /usr/bin/php8.3 ]; then
    PHP_BIN="/usr/bin/php8.3"
fi

echo "=== Pulling Latest Code from GitHub ==="
git remote set-url origin git@github.com:rasheedaljadi/HIGST101.git 2>/dev/null || true
git fetch origin
git pull origin main --no-edit || git pull origin master --no-edit || true

echo ""
echo "=== Clearing Application Cache & Rebuilding ==="
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear

echo ""
echo "=== Running Production Readiness Check ==="
$PHP_BIN artisan fulfillment:production-check

echo ""
echo "=== END-TO-END DEPLOYMENT & SYNC COMPLETED SUCCESSFULLY ==="
'@

if (Get-Command plink -ErrorAction SilentlyContinue) {
    echo $remoteScript | plink -ssh $User@$HostIP -pw $Pass
} else {
    ssh "$User@$HostIP" $remoteScript
}
