<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function backupDatabase()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('database:backup');
            return back()->with('success', "Database Fortress secured at Desktop!");
        } catch (\Exception $e) {
            return back()->with('error', "Fortress construction failed: " . $e->getMessage());
        }
    }
}
