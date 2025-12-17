@echo off
echo 🚀 Автодеплой запущен...

REM Ожидание завершения каждой команды
setlocal enabledelayedexpansion

REM Добавляем изменения и пушим
echo 📦 Добавляем изменения в git...
git add .
git commit -m "commit admin panel category"
git push origin master

REM Сборка проекта с ожиданием завершения
echo ⚙️ Сборка проекта...
call npm run build

REM Проверяем успешность сборки
if %errorlevel% neq 0 (
    echo ❌ Ошибка сборки! Код ошибки: %errorlevel%
    pause
    exit /b
)

REM Добавляем сборку в git
echo 📦 Добавляем сборку в git...
git add -f public/build/
git commit -m "Build assets for production"
git push origin master

echo ✅ Локальный деплой завершен
pause
