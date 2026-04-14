<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchHistory extends Model
{
    protected $table = 'watch_histories';

    protected $fillable = [
        'video_id',
        'user_id',
        'watched_at',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
