@echo off
setlocal enabledelayedexpansion

REM =====================================================
REM CONFIG
set DEBUG_SCAN=0
set INTERVAL=20
set IGNORE_FILE=.watchignore
set TEST_CMD=call "../PHP/php-sensitive/vendor/bin/phpunit.bat" "C:\SITE\DevProjs\Vehoute\PHP\testes"

REM Pastas monitoradas
set WATCH_1=C:\SITE\DevProjs\Vehoute\PHP\backend
set WATCH_2=C:\SITE\DevProjs\Vehoute\PHP\testes
set WATCH_3=C:\SITE\DevProjs\Vehoute\PHP\php-sensitive


REM TEMP FILES
set TMP_NEW=%temp%\watch_new.txt
set TMP_OLD=%temp%\watch_old.txt
set TMP_FILTERED=%temp%\watch_filtered.txt

REM =====================================================
REM EXECUTION COUNTER
set RUN_ID=0

echo ============================================
echo WATCHER INICIADO
echo.
echo [E] = Executar testes manualmente
echo.

call :GenerateSnapshot "%TMP_OLD%"

:loop
REM =====================================================
REM AGUARDA INTERVALO COM HOTKEY
for /L %%s in (%INTERVAL%,-1,1) do (
    choice /c ER /n /t 1 /d R >nul
    REM E = executar agora
    if !errorlevel! == 1 (
        call :RunTests MANUAL
    )
)

REM =====================================================
REM SCAN

<nul set /p="-"
call :GenerateSnapshot "%TMP_NEW%"
<nul set /p="_"

fc "%TMP_NEW%" "%TMP_OLD%" >nul

if errorlevel 1 (
    call :RunTests AUTO
    copy /y "%TMP_NEW%" "%TMP_OLD%" >nul
)
goto loop



REM =====================================================
REM EXEC TESTS
:RunTests
	set MODE=%~1
	set /a RUN_ID+=1

	cls

	echo ============================================
	echo EXECUCAO #!RUN_ID!
	echo MODO: !MODE!
	echo Data: %date%
	echo Hora: %time%
	echo.

	%TEST_CMD%

	echo.
	echo FIM EXECUCAO #!RUN_ID!
	echo.
	exit /b




:ScanFolder
	set "CURRENT_DIR=%~1"

	REM VERIFICA SE A PASTA DEVE SER IGNORADA
	if exist "%IGNORE_FILE%" (
		for /f "usebackq delims=" %%r in ("%IGNORE_FILE%") do (
			if not "%%r"=="" (
				echo %%r | findstr "^#" >nul
				if errorlevel 1 (
					REM MATCH EM PASTA
					echo "!CURRENT_DIR!" | findstr /i /c:"\%%r\" >nul
					if not errorlevel 1 (
						if "!DEBUG_SCAN!"=="1" (
							echo [IGNORED DIR] !CURRENT_DIR!
						)
						exit /b
					)
				)
			)
		)
	)

	REM SCAN DOS ARQUIVOS PHP

	for %%f in ("%CURRENT_DIR%\*.php") do (
		if exist "%%f" (
			if "!DEBUG_SCAN!"=="1" (
				echo [SCAN] %%f
			)
			echo %%~tf %%f>>"%OUTPUT%"
		)
	)

	REM RECURSÃO NAS SUBPASTAS

	for /d %%d in ("%CURRENT_DIR%\*") do (
		call :ScanFolder "%%d"
	)
	exit /b



REM =====================================================
REM SNAPSHOT
:GenerateSnapshot
	set OUTPUT=%~1

	if exist "%OUTPUT%" del "%OUTPUT%"

	for /L %%i in (1,1,999) do (
		call set CURRENT_WATCH=%%WATCH_%%i%%
		if defined CURRENT_WATCH (
			if "!DEBUG_SCAN!"=="1" (
				echo Pasta:
				echo !CURRENT_WATCH!
				echo.
			)
			call :ScanFolder "!CURRENT_WATCH!"
		)
	)
	
	exit /b