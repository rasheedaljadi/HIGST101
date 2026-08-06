$user = "highest-ye"
$ip = "76.13.79.242"
ssh -o StrictHostKeyChecking=no "${user}@${ip}" "find / -name 'artisan' -maxdepth 5 2>/dev/null"
