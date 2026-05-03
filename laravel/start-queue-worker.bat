@echo off
REM AIStory Queue Worker — startup script for Windows
REM
REM Usage:
REM   start-queue-worker.bat        Start worker (processes queued jobs)
REM   start-queue-worker.bat stop   Stop all workers
REM
REM Prerequisites:
REM   - QUEUE_CONNECTION=database in .env (NOT sync)
REM   - Database migrations run (jobs and failed_jobs tables)
REM
REM For production, run this as a Windows Service or use NSSM.

if "%1"=="stop" (
    taskkill /f /im php.exe /fi "WINDOWTITLE eq queue:work*" 2>nul
    echo Queue worker stopped.
    goto :eof
)

echo Starting AIStory queue worker...
echo Driver: database
echo Polling interval: 3 seconds
echo Timeout: 60 seconds
echo Max attempts: 3 (per job config)
echo.

D:\xampp\php\php.exe artisan queue:work database --sleep=3 --timeout=60 --tries=3 --max-time=3600
