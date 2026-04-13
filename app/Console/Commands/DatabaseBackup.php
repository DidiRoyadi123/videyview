<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database to the desktop (standard location)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dbConfig = config('database.connections.mysql');
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $database = $dbConfig['database'];
        
        $userProfile = getenv('USERPROFILE');
        $desktopPath = $userProfile . '\Desktop';
        
        $filename = 'videyview_auto_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $outputPath = $desktopPath . '\\' . $filename;
        
        $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        
        if (empty($password)) {
            $command = "\"{$mysqldumpPath}\" --user={$username} {$database} > \"{$outputPath}\" 2>&1";
        } else {
            $command = "\"{$mysqldumpPath}\" --user={$username} --password={$password} {$database} > \"{$outputPath}\" 2>&1";
        }

        $output = [];
        $resultCode = null;
        exec($command, $output, $resultCode);

        if ($resultCode === 0) {
            $this->info("Backup successful: {$filename}");
            Log::info("Autonomous Backup: Successfully secured database at {$outputPath}");
        } else {
            $this->error("Backup failed.");
            Log::error("Autonomous Backup: Failed to build fortress. Error: " . implode("\n", $output));
        }
    }
}
