@echo off
setlocal EnableExtensions
title Academy NTM - Laravel + Chatbot
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"

echo.
echo ========================================
echo   Academy NTM - Start All
echo ========================================
echo.

REM Matikan instance lama di port 5000/8000
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":5000" ^| findstr "LISTENING"') do taskkill /F /PID %%a >nul 2>&1
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8000" ^| findstr "LISTENING"') do taskkill /F /PID %%a >nul 2>&1

echo [1/2] Menjalankan Flask chatbot (jendela terpisah, JANGAN ditutup)...
start "AcademyNTM-Flask" cmd /k "cd /d "%~dp0chatbot_hybrid" && (py -3 app.py || python app.py)"

echo Menunggu Flask siap (TensorFlow butuh beberapa detik)...
set "READY=0"
for /L %%i in (1,1,30) do (
  powershell -NoProfile -Command "try { $r=Invoke-WebRequest -Uri http://127.0.0.1:5000/health -UseBasicParsing -TimeoutSec 1; if ($r.StatusCode -eq 200) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1
  if not errorlevel 1 (
    set "READY=1"
    goto :flask_ok
  )
  timeout /t 1 /nobreak >nul
)

:flask_ok
if "%READY%"=="0" (
  echo.
  echo [ERROR] Flask belum siap di http://127.0.0.1:5000
  echo Cek jendela "AcademyNTM-Flask" untuk pesan error.
  echo Chatbot akan mode offline sampai Flask aktif.
  echo.
) else (
  echo Flask OK: http://127.0.0.1:5000/health
)

echo.
echo [2/2] Menjalankan Laravel di terminal ini...
echo Website : http://127.0.0.1:8000
echo.
"%PHP%" artisan serve --host=127.0.0.1 --port=8000

echo.
echo Laravel berhenti. Jalankan stop_all.bat jika Flask masih hidup.
endlocal
