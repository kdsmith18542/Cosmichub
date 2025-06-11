@echo off
SETLOCAL

REM Check common PHP installation paths
SET PHP_PATHS=^
C:\wamp\bin\php\php8.2.13\php.exe;^
C:\wamp64\bin\php\php8.2.13\php.exe;^
C:\wamp\bin\php\php8.3.14\php.exe;^
C:\wamp64\bin\php\php8.3.14\php.exe;^
C:\xampp\php\php.exe

FOR %%p IN (%PHP_PATHS%) DO (
    IF EXIST %%p (
        ECHO Found PHP at: %%p
        ECHO Running migration...
        %%p database/migrate.php
        EXIT /B
    )
)

ECHO Could not find PHP executable. Please ensure WAMP/XAMPP is installed correctly.
EXIT /B 1