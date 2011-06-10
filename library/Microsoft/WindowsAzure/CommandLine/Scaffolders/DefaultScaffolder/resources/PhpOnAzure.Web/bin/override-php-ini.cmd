@echo off
ECHO Overriding php.ini... >> ..\startup-tasks-log.txt

powershell.exe Set-ExecutionPolicy Unrestricted
powershell.exe .\override-php-ini.ps1 >> ..\startup-tasks-log.txt 2>>..\startup-tasks-error-log.txt

ECHO Overridden php.ini. >> ..\startup-tasks-log.txt