@echo off
title VideyView Sync Tool
echo Starting VideyView Sync...
echo ----------------------------------------

:: Check for python
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python not found! Please install Python 3.
    pause
    exit /b
)

:: Ask for threads (Default to 1 if empty)
set /p THREADS="[INPUT] Number of parallel threads (default 1): "
if "%THREADS%"=="" set THREADS=1

:: Ask for Remote Sync (Optional)
set /p RURL="[INPUT] Remote Hosting URL (leave empty to skip): "
set /p RKEY="[INPUT] Internal Sync API Key (leave empty to skip): "

:: Build arguments
set ARGS=--threads %THREADS%
if not "%RURL%"=="" set ARGS=%ARGS% --remote-url %RURL%
if not "%RKEY%"=="" set ARGS=%ARGS% --api-key %RKEY%

:: Run the script
echo.
echo [INFO] Running: python sync.py %ARGS%
python sync.py %ARGS%

:: If it crashes, keep window open
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Script crashed with exit code %errorlevel%
    pause
)
