<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Archetype extends Model
{
    protected $fillable = ['name', 'description', 'slug'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($archetype) {
            if (empty($archetype->slug)) {
                $archetype->slug = Str::slug($archetype->name);
            }
        });
    }

    public function comments()
    {
        return $this->hasMany(ArchetypeComment::class);
    }

    public function celebrities()
    {
        return $this->belongsToMany(CelebrityReport::class, 'archetype_celebrity', 'archetype_id', 'celebrity_report_id');
    }

    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->first();
    }
}