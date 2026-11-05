@echo off
title SWMS - Public Demo Runner
echo ========================================================
echo    SMART WASTE MANAGEMENT SYSTEM (SWMS) - LIVE DEMO
echo ========================================================
echo.

echo [1/3] Memastikan Service AI (FastAPI port 8001) aktif...
start "SWMS-AI" /min cmd /c "cd /d %~dp0machine-learning && python app.py"
timeout /t 3 /nobreak >nul

echo [2/3] Memastikan Website & REST API Laravel (port 8000) aktif...
start "SWMS-Web" /min cmd /c "cd /d %~dp0 && php -d upload_max_filesize=25M -d post_max_size=30M artisan serve --host=127.0.0.1 --port=8000"
timeout /t 3 /nobreak >nul

echo [3/3] Mengaktifkan Cloudflare Tunnel Publik (HTTPS Gratis)...
echo.
echo Silakan tunggu URL publik trycloudflare.com muncul di bawah:
echo ========================================================
"%USERPROFILE%\bin\cloudflared.exe" tunnel --url http://127.0.0.1:8000
