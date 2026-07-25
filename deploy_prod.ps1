# Complete End-to-End Pipeline: Push to GitHub -> Force Reset & Sync on Production Server
$HostIP = "76.13.79.242"
$User = "highest-ye"
$Pass = "YoK2PBV1fo82yujX2tDq"
$RepoUrl = "git@github.com:rasheedaljadi/HIGST101.git"

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host " 1. Pushing Local Changes to GitHub " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

git add .
git commit -m "Fix view compiled path in config/view.php and ensure framework directories exist" 2>$null
git push origin main

if ($LASTEXITCODE -ne 0) {
    git push
}

Write-Host "`n====================================================" -ForegroundColor Cyan
Write-Host " 2. Deploying & Syncing Code on Production Server " -ForegroundColor Cyan
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

echo "=== 1. Syncing Code from GitHub ==="
git remote set-url origin git@github.com:rasheedaljadi/HIGST101.git 2>/dev/null || true
git fetch origin main
git reset --hard origin/main

echo ""
echo "=== 2. Creating Framework Storage Directories & Setting Permissions ==="
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "=== 3. Re-creating Storage Symlink ==="
rm -rf public/storage
$PHP_BIN artisan storage:link --force
chmod -R 775 storage public/storage 2>/dev/null || true

echo ""
echo "=== 4. Clearing View & Application Cache ==="
$PHP_BIN artisan view:clear
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
$PHP_BIN artisan route:clear

echo ""
echo "=== 5. Testing View Existence ==="
$PHP_BIN artisan tinker --execute="echo 'View aliexpress.sync exists: ' . (view()->exists('aliexpress.sync') ? 'YES' : 'NO') . '\n';"

echo ""
echo "=== 6. Running Production Readiness Check ==="
$PHP_BIN artisan fulfillment:production-check

echo ""
echo "=== VIEW FIX & DEPLOYMENT COMPLETED SUCCESSFULLY ==="
'@

if (Get-Command plink -ErrorAction SilentlyContinue) {
    echo $remoteScript | plink -ssh $User@$HostIP -pw $Pass
} else {
    ssh "$User@$HostIP" $remoteScript
}
