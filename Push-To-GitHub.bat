@echo off
echo ========================================================
echo Pushing College Event Management System to GitHub
echo GitHub Account: https://github.com/radhikaahire
echo Target Repo:    https://github.com/radhikaahire/college-event-.git
echo ========================================================
echo.
cd /d "C:\Users\radhika\Downloads\projects\College Event-20261012T171641Z-1-001"

echo Pushing code to GitHub (main branch)...
"C:\Git\cmd\git.exe" push -u origin main

if %ERRORLEVEL% equ 0 (
    echo.
    echo ========================================================
    echo SUCCESS! All code has been pushed to:
    echo https://github.com/radhikaahire/college-event-
    echo ========================================================
) else (
    echo.
    echo If sign-in is required, please sign in via browser or run Login-To-GitHub.bat on Desktop.
)

echo.
pause
