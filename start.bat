@echo off
echo ========================================================
echo Starting College Event Management System...
echo ========================================================

cd /d C:\xampp

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo MySQL is already running.
) else (
    echo Starting MySQL...
    start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
)

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo Apache is already running.
) else (
    echo Starting Apache...
    start "" "C:\xampp\apache\bin\httpd.exe"
)

timeout /t 2 >nul
echo Opening College Event System in your browser...
start http://localhost/College-Event/login.php

echo.
echo College Event System is live at: http://localhost/College-Event/login.php
echo phpMyAdmin is live at:          http://localhost/phpmyadmin/
echo.
pause
