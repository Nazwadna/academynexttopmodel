@echo off
setlocal EnableExtensions
title Academy NTM - Queue Worker (chatbot-training)
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"

echo.
echo ========================================
echo   Queue Worker: chatbot-training
echo ========================================
echo Pastikan:
echo   1. MySQL XAMPP aktif
echo   2. Migrasi jobs sudah dijalankan
echo   3. Flask chatbot (port 5000) aktif
echo.
echo Menjalankan:
echo   php artisan queue:listen --queue=chatbot-training
echo.
echo Tekan Ctrl+C untuk berhenti.
echo.

"%PHP%" artisan queue:listen --queue=chatbot-training --tries=1 --timeout=320

endlocal
