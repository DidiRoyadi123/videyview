<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'url',
        'thumbnail_url',
        'mirror_links',
        'hosting_status',
        'download_status',
        'local_path',
        'is_premium',
        'is_free_to_all',
        'views',
    ];

    protected $casts = [
        'mirror_links' => 'array',
        'hosting_status' => 'array',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
