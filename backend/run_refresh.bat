@echo off
REM Script para rodar o refresh_cache.php. Tenta usar php no PATH ou o PHP do XAMPP.
SETLOCAL
SET SCRIPT=%~dp0refresh_cache.php

REM Remove aspas se existirem
SET SCRIPT=%SCRIPT:~1,-1%

REM Se php estiver no PATH, usa-o
where php >nul 2>&1
IF %ERRORLEVEL%==0 (
    php "%SCRIPT%"
    GOTO :EOF
)

REM Senão, tenta o PHP padrão do XAMPP
IF EXIST "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" "%SCRIPT%"
    GOTO :EOF
)

ECHO Nao foi possivel encontrar o executavel PHP. Edite este arquivo para apontar para o php.exe correto.
ENDLOCAL
