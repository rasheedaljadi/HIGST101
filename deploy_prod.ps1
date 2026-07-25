# Complete End-to-End Pipeline: Force Track Views -> Push to GitHub -> Deploy to Server
$HostIP = "76.13.79.242"
$User = "highest-ye"
$Pass = "YoK2PBV1fo82yujX2tDq"
$RepoUrl = "git@github.com:rasheedaljadi/HIGST101.git"

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host " 1. Force Adding & Pushing AliExpress Views to GitHub " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

# Force track all blade views in resources/views/aliexpress
git add -f resources/views/aliexpress/import.blade.php 2>$null
git add -f resources/views/aliexpress/keys.blade.php 2>$null
git add -f resources/views/aliexpress/sync.blade.php 2>$null
git add -A
git commit -m "Force track and push resources/views/aliexpress blade views" 2>$null
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
echo "=== 2. Verifying resources/views/aliexpress Directory on Server ==="
ls -la resources/views/aliexpress/ 2>/dev/null || echo "Directory resources/views/aliexpress still missing!"

echo ""
echo "=== 3. Creating Framework Storage Directories & Setting Permissions ==="
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "=== 4. Re-creating Storage Symlink ==="
rm -rf public/storage
$PHP_BIN artisan storage:link --force
chmod -R 775 storage public/storage 2>/dev/null || true

echo ""
echo "=== 5. Clearing View & Application Cache & Recovering Sync Runs ==="
$PHP_BIN artisan fulfillment:recover-sync-runs
$PHP_BIN artisan view:clear
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
$PHP_BIN artisan route:clear

echo ""
echo "=== 6. Testing AliExpress Views Existence ==="
$PHP_BIN artisan tinker --execute="echo 'aliexpress.import: ' . (view()->exists('aliexpress.import') ? 'YES' : 'NO') . '\n'; echo 'aliexpress.sync: ' . (view()->exists('aliexpress.sync') ? 'YES' : 'NO') . '\n'; echo 'aliexpress.keys: ' . (view()->exists('aliexpress.keys') ? 'YES' : 'NO') . '\n';"

echo ""
echo "=== 7. Running Production Readiness Check ==="
$PHP_BIN artisan fulfillment:production-check

echo ""
echo "=== VIEWS FORCE PUSH & DEPLOYMENT COMPLETED SUCCESSFULLY ==="
'@

if (Get-Command plink -ErrorAction SilentlyContinue) {
    echo $remoteScript | plink -ssh $User@$HostIP -pw $Pass
} else {
    ssh "$User@$HostIP" $remoteScript
}
