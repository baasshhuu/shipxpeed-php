<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Library\Database;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Jobs\SendEmailNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    protected $signature    = 'db:backup';

    protected $description  = 'Take Database Backup';

    public function handle()
    {
        // BackUp Database
        Database::backup();

        // Delete Backups Older then 1 Day.
        $this->deleteOldFiles();

        // Email Backup Sql File (May Not Work due to Large Size of Database)
        // $this->sendFileOnMail($fileStorePath);

        Log::info("Database Backup Cron Run Successfully." . Carbon::now());
        return Command::SUCCESS;
    }

    protected function deleteOldFiles()
    {
        $disk   = "local";
        $files  = Storage::disk($disk)->files("backup");

        foreach ($files as $file) {
            try {
                $time                   = Storage::disk($disk)->lastModified($file);
                $fileModifiedDateTime   = Carbon::parse($time);

                if (Carbon::now()->gt($fileModifiedDateTime->addMinutes(5))) {
                    Storage::disk($disk)->delete($file);
                }
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
            }
        }
    }

    protected function sendFileOnMail($fileStorePath)
    {
        $site_settings      = Setting::select('setting_name', 'filed_value')
            ->whereIn('setting_type', [1, 3])
            ->get()
            ->pluck('filed_value', 'setting_name')
            ->toArray();

        $data = [
            'name'      => $site_settings['application_name'],
            'email'     => $site_settings['email'],
            'title'     => $site_settings['application_name'] . " Databse Backup :: " . Carbon::now(),
            'message'   => "The purpose of the backup is to create a copy of data that can be recovered in the event of a primary data failure. Primary data failures can be the result of hardware or software failure, data corruption, or a human-caused event, such as a malicious attack (virus or malware), or accidental deletion of data. Backup copies allow data to be restored from an earlier point in time to help the business recover from an unplanned event.",
            'file'      => $fileStorePath
        ];

        SendEmailNotification::dispatch($data, $site_settings);
    }
}
