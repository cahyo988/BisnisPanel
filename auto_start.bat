@echo off
echo Starting BisnisPanel Project...

:: Start Laravel Backend
echo Starting Laravel Server...
start "Laravel Server" php artisan serve

:: Start Queue Worker
echo Starting Queue Worker...
start "Queue Worker" php artisan queue:work

:: Start Frontend (Vite)
echo Starting Vite...
start "Vite Dev Server" npm run dev

:: Start WA Gateway
echo Starting WA Gateway...
cd wa-gateway
start "WA Gateway" npm start
cd ..

echo All services started!
pause
