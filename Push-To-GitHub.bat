@echo off
echo ========================================================
echo Pushing College Event Management System to GitHub
echo GitHub Account: https://github.com/radhikaahire
echo Target Repo:    College-Event-Management
echo ========================================================
echo.
echo Step 1: Make sure you have created the new repository on GitHub:
echo         https://github.com/new
echo         - Repository name: College-Event-Management
echo         - Public or Private: Your choice
echo         - Do NOT initialize with README (already created locally)
echo.
echo Press any key when the GitHub repository is created to push...
pause >nul

echo.
echo Pushing code to GitHub...
"C:\Git\cmd\git.exe" push -u origin main

if %ERRORLEVEL% equ 0 (
    echo.
    echo ========================================================
    echo SUCCESS! Code pushed to:
    echo https://github.com/radhikaahire/College-Event-Management
    echo ========================================================
) else (
    echo.
    echo If sign-in failed, please retry or create the repo at https://github.com/new
)

echo.
pause
