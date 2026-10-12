@echo off
echo ========================================================
echo Connecting your GitHub account (radhikaahire)
echo ========================================================
echo.
echo This will open GitHub in your browser to authorize your PC.
echo.
"C:\Git\cmd\gh.exe" auth login --web --git-protocol https

echo.
echo Checking authentication status:
"C:\Git\cmd\gh.exe" auth status

echo.
pause
