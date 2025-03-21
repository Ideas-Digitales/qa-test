#!/bin/bash

git submodule update --init

echo "Debug info"
echo "Database username: SA"
echo "Database password: $MSSQL_SA_PASSWORD"
echo "Database: $MSSQL_DATABASE"

while ! sqlcmd -S sqlserver -U SA -P $MSSQL_SA_PASSWORD -C -q "GO
SELECT 1
GO
EXIT"; do
    echo "Waiting for SQL Server connection" && sleep 1
done
    sqlcmd -S sqlserver -U SA -P $MSSQL_SA_PASSWORD -C -q "USE master;
    GO
    IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = '$MSSQL_DATABASE')
    BEGIN
    CREATE DATABASE $MSSQL_DATABASE;
    END
    GO
    EXIT" && echo "The database has been initialized"

composer install
php artisan key:generate
php artisan migrate
