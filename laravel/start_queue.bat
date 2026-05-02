@echo off
REM AIStory Queue Worker — start with: start_queue.bat
REM Ensure php is in PATH or update the path below
set PHP=D:\xampp\php\php.exe
set ARTISAN=d:\办公\manju\laravel\artisan

echo Starting AIStory Queue Worker...
echo Press Ctrl+C to stop

:loop
%PHP% %ARTISAN% queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=600
echo [%date% %time%] Queue worker exited, restarting in 5s...
timeout /t 5 /nobreak >nul
goto loop
