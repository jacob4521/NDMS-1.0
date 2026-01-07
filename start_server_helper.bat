@echo off
echo.
echo ========================================
echo   NDMS - PHP Server Launcher
echo ========================================
echo.

REM Get the current IP configuration
echo 🔍 Current Network Configuration:
ipconfig | findstr /i "IPv4"
echo.

REM Read current server URL from config
if exist server_config.txt (
    for /f "tokens=2 delims==" %%a in ('findstr "SERVER_URL" server_config.txt') do set CURRENT_URL=%%a
    echo 📡 Current Server URL: %CURRENT_URL%
) else (
    echo ⚠️  server_config.txt not found!
)

echo.
echo 🚀 To start the PHP server, use one of these commands:
echo.
echo    For specific IP:  php -S 192.168.8.240:3000
echo    For any IP:       php -S 0.0.0.0:3000
echo    For localhost:    php -S localhost:3000
echo.
echo 💡 Replace 192.168.8.240 with your actual IP address from above
echo 💡 Make sure to update server_config.txt with the same IP
echo.
echo 📁 Config file location: server_config.txt
echo 📖 Full instructions: HOW_TO_CHANGE_IP.txt
echo.
pause
