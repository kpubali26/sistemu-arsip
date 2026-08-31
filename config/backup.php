<?php
// config/backup.php

return [
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
    'arsip_storage_path' => env('ARSIP_STORAGE_PATH', storage_path('app/public/arsip')),
];