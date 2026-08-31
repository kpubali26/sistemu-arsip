@echo off
cd /d C:\xampp\htdocs\sistemu-arsip2\sistemu-arsip
C:\xampp\php\php.exe artisan queue:work --sleep=3 --tries=1 --timeout=3600