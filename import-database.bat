@echo off
REM Script untuk import database kaih ke MySQL
REM Pastikan Laragon sudah running sebelum menjalankan script ini

setlocal enabledelayedexpansion

echo ========================================
echo Import Database KAIH
echo ========================================
echo.

REM Tentukan path MySQL
set MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe
set SQL_FILE=database\kaih.sql

REM Check if MySQL exists
if not exist "%MYSQL_PATH%" (
    echo ERROR: MySQL tidak ditemukan di %MYSQL_PATH%
    echo Pastikan Laragon sudah diinstall di direktori default
    pause
    exit /b 1
)

REM Check if SQL file exists
if not exist "%SQL_FILE%" (
    echo ERROR: File %SQL_FILE% tidak ditemukan
    pause
    exit /b 1
)

echo Dropping database kaih jika ada...
"%MYSQL_PATH%" -uroot -p"" -e "DROP DATABASE IF EXISTS kaih;" 2>nul

echo Mengimport file kaih.sql...
"%MYSQL_PATH%" -uroot -p"" < "%SQL_FILE%"

if !errorlevel! equ 0 (
    echo.
    echo ========================================
    echo [OK] Database kaih berhasil diimport!
    echo ========================================
    echo.
    echo Informasi koneksi:
    echo - Host: localhost
    echo - User: root
    echo - Password: (kosong)
    echo - Database: kaih
    echo.
) else (
    echo.
    echo [ERROR] Gagal mengimport database
    echo.
)

pause
