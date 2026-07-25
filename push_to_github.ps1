# PowerShell Script to Commit and Push Changes to GitHub
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host " Pushing All Project Fixes & Code to GitHub " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

git add .
git commit -m "Fix automatic sync scheduling, production readiness checks, storage symlink, and SyncRun tracking"
git push

Write-Host "`n[SUCCESS] Code pushed to GitHub successfully!" -ForegroundColor Green
