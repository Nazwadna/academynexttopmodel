@echo off
title Academy NTM Chatbot API
cd /d "%~dp0"
echo.
echo Folder kerja: %CD%
echo Memasang dependensi (pertama kali bisa beberapa menit)...
py -3 -m pip install -r requirements.txt -q 2>nul
if errorlevel 1 (
  python -m pip install -r requirements.txt -q
)
echo.
echo Menjalankan server di http://127.0.0.1:5000
echo Tutup jendela ini untuk menghentikan server.
echo.
py -3 app.py 2>nul
if errorlevel 1 python app.py
pause
