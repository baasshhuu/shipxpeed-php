<?php

namespace App\Library;

use Illuminate\Support\Facades\File;

class Database
{
    public static function backup()
    {
        try {

            File::ensureDirectoryExists(storage_path("app/backup"));

            $host       = config('database.connections.mysql.host');
            $userName   = config('database.connections.mysql.username');
            $password   = config('database.connections.mysql.password');
            $database   = config('database.connections.mysql.database');
            $date       = date('Y-m-d-H-i-s');
            $path       = storage_path("app/backup/backup-$date.gz");

            $output = [];
            if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $password)) {
                $password = "'" . $password . "'";
            }

            $cmd = "mysqldump --user=$userName --password=$password --host=$host --single-transaction --routines $database | gzip > $path";
            // $cmd = "mysqldump --routines -u $userName -p$password -h $host --single-transaction $database | gzip > $path";
            exec($cmd, $output);

            return $path;
        } catch (\Throwable $th) {
            return "-- Oops.. !! There is some error.";
        }
    }
}
