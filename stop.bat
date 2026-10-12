@echo off
echo Stopping Apache and MySQL...
taskkill /F /IM httpd.exe >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1
echo Done! Servers stopped.
pause
