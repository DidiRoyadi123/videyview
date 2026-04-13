<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class LogController extends Controller
{
    /**
     * Display the log viewer.
     */
    public function index()
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logPath)) {
            $file = fopen($logPath, "r");
            $lineCount = 0;
            $maxLines = 100;
            $chunkSize = 4096;
            
            fseek($file, 0, SEEK_END);
            $pos = ftell($file);
            $buffer = "";
            
            while ($pos > 0 && $lineCount < $maxLines) {
                $readSize = min($pos, $chunkSize);
                $pos -= $readSize;
                fseek($file, $pos);
                $chunk = fread($file, $readSize);
                $buffer = $chunk . $buffer;
                $lineCount = substr_count($buffer, "\n");
            }
            
            fclose($file);
            $lines = explode("\n", trim($buffer));
            $logs = array_slice(array_reverse(array_filter($lines)), 0, $maxLines);
        }

        return Inertia::render('Admin/Logs', [
            'logs' => $logs
        ]);
    }

    /**
     * Clear the log file.
     */
    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }
        return back()->with('success', 'Logs cleared successfully.');
    }
}
