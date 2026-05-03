@echo off
REM AIStory Laravel Server — Windows startup
REM
REM Uses custom server.php router to fix:
REM 1. Admin SPA routing (SCRIPT_NAME override)
REM 2. Static file detection (is_file vs file_exists)
REM
REM Usage:
REM   start-server.bat          Start server on port 8000
REM   start-server.bat 8080     Start server on custom port
REM
REM For production, use Nginx/Apache instead.

set PORT=%1
if "%PORT%"=="" set PORT=8000

set PHP=D:\xampp\php\php.exe
set PUBLIC=d:\办公\manju\laravel\public
set ROUTER=d:\办公\manju\laravel\server.php

echo ============================================
echo   AIStory Laravel Server
echo   Port: %PORT%
echo   Router: server.php
echo   Press Ctrl+C to stop
echo ============================================

cd /d %PUBLIC%
%PHP% -S 127.0.0.1:%PORT% %ROUTER%
