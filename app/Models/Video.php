<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use \App\Traits\LogsCrudActivity;

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
        'last_check_at',
        'health_report',
        'category_id',
    ];

    protected $casts = [
        'mirror_links' => 'array',
        'hosting_status' => 'array',
        'health_report' => 'array',
        'is_premium' => 'boolean',
        'is_free_to_all' => 'boolean',
        'last_check_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Sitemap trigger removed to improve content creation performance.
        // Run 'php artisan video:sitemap' manually or via scheduler instead.
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function likes()
    {
        return $this->hasMany(VideoLike::class)->where('is_like', true);
    }

    public function dislikes()
    {
        return $this->hasMany(VideoLike::class)->where('is_like', false);
    }

    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }
}
