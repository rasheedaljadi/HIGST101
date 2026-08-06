$password = "YoK2PBV1fo82yujX2tDq"
$user = "highest-ye"
$ip = "76.13.79.242"

# Check if plink exists or use ssh
if (Get-Command plink -ErrorAction SilentlyContinue) {
    plink -batch -pw $password "${user}@${ip}" "cd /var/www/html 2>/dev/null || cd /home/${user}/public_html 2>/dev/null || cd ~/ 2>/dev/null; git pull origin main; php artisan optimize:clear; php artisan config:cache; php artisan route:cache; php artisan view:cache"
} else {
    Write-Host "Attempting SSH connection to $ip..."
    ssh -o StrictHostKeyChecking=no "${user}@${ip}" "git pull origin main; php artisan optimize:clear; php artisan config:cache; php artisan route:cache; php artisan view:cache"
}
