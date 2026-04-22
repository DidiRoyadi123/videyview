<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use \App\Traits\LogsCrudActivity;

    protected $fillable = ['name', 'slug', 'icon', 'order'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cat) {
            if (empty($cat->slug)) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
