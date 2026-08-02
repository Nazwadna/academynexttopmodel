@echo off
setlocal EnableExtensions
title Academy NTM - Stop All
cd /d "%~dp0"

echo Menghentikan proses di port 5000 (Flask) dan 8000 (Laravel)...

for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":5000" ^| findstr "LISTENING"') do (
  echo Kill PID %%a (port 5000)
  taskkill /F /PID %%a >nul 2>&1
)

for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000" ^| findstr "LISTENING"') do (
  echo Kill PID %%a (port 8000)
  taskkill /F /PID %%a >nul 2>&1
)

REM tutup jendela judul Flask jika masih ada
taskkill /FI "WINDOWTITLE eq AcademyNTM-Flask*" /F >nul 2>&1

echo Selesai.
endlocal
