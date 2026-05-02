@echo off
title VideyView - Queue Worker
color 0A
echo ==========================================
echo  VideyView Queue Worker
echo  Processing: DistributeToHostJob, etc.
echo  Press CTRL+C to stop.
echo ==========================================
echo.
cd /d c:\xampp\htdocs\videyview
php artisan queue:work --sleep=3 --tries=3 --timeout=600 --queue=default
pause
