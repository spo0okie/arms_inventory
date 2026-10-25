@echo off
rem Все наборы используют отдельный PHP-сервер без настроек Apache/Xdebug.
php "%~dp0tests\bin\run.php" %*
exit /b %errorlevel%
