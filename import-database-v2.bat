cd c:\laragon\www\kaih

REM Cari MySQL binary di Laragon
for /d %%D in (C:\laragon\bin\mysql\mysql-*) do (
    set MYSQL_PATH=%%D\bin\mysql.exe
)

echo Dropping database kaih jika ada...
%MYSQL_PATH% -u root -h localhost -e "DROP DATABASE IF EXISTS kaih;" 

echo Creating dan importing database kaih...
%MYSQL_PATH% -u root -h localhost < database\kaih.sql

echo.
echo DATABASE KAIH BERHASIL DIIMPORT!
echo.

pause
