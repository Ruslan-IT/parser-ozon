@echo off
echo 🚀 Автодеплой Laravel (без Vite) запущен...

setlocal enabledelayedexpansion

echo 📦 Добавляем изменения в git...
git add .

echo 📝 Коммит...
git commit -m "update backend"

echo ⬆️ Пуш в репозиторий...
git push origin master

echo ✅ Деплой завершен
pause
