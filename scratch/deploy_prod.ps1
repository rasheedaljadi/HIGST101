$user = "highest-ye"
$ip = "76.13.79.242"
$remoteCmd = @'
for dir in ~/ /home/highest-ye/* /www/wwwroot/* /var/www/*; do
  if [ -f "$dir/artisan" ]; then
    echo "FOUND_PROJECT_DIR: $dir"
    cd "$dir"
    echo "=== Running git pull ==="
    git pull origin main
    echo "=== Clearing and caching ==="
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    exit 0
  fi
done
echo "Searching home directory..."
cd ~
pwd
ls -la
'@

ssh -o StrictHostKeyChecking=no "${user}@${ip}" "$remoteCmd"
