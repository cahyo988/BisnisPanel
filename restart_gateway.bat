@echo off
echo Restarting WA Gateway...

:: Stop existing WA Gateway process by port (default 3001)
for /f "tokens=5" %%P in ('netstat -ano ^| findstr :3001 ^| findstr LISTENING') do (
  taskkill /PID %%P /T /F >nul 2>&1
)

:: Start WA Gateway
pushd wa-gateway
start "WA Gateway" npm start
popd

echo WA Gateway restarted.
pause
