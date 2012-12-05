@ECHO OFF

if "%HOME%" == "" set HOME=%USERPROFILE%
php "%~dp0civix" %*
