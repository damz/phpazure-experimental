@echo off
cls
echo Running command-line tests...
echo.

REM ### Set environment variables
SET SubscriptionID=402604cd-98cb-435b-9a35-e8853d1d78b0
SET Certificate=Microsoft\WindowsAzure\Management\_files\management.pem

REM ### Storage - ListAccounts
php ..\library\Microsoft\WindowsAzure\CommandLine\Storage.php ListAccounts -p:"phpazure"

REM ### Storage - GetProperties
php ..\library\Microsoft\WindowsAzure\CommandLine\Storage.php GetProperties -p:"phpazure" --AccountName:"phptestsdk"

REM ### Storage - GetProperty
php ..\library\Microsoft\WindowsAzure\CommandLine\Storage.php GetProperty -p:"phpazure" --AccountName:"phptestsdk" --Property:Url

REM ### Storage - GetKeys
php ..\library\Microsoft\WindowsAzure\CommandLine\Storage.php GetKeys -p:"phpazure" --AccountName:"phptestsdk"

REM ### Storage - GetKey
php ..\library\Microsoft\WindowsAzure\CommandLine\Storage.php GetKey -p:"phpazure" --AccountName:"phptestsdk" -k:secondary

REM ### Storage - RegenerateKeys
php ..\library\Microsoft\WindowsAzure\CommandLine\Storage.php RegenerateKeys -p:"phpazure" --AccountName:"phptestsdk" -k:secondary

echo.
echo Finished test run.
pause