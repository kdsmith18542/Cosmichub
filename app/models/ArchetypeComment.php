<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchetypeComment extends Model
{
    protected $fillable = ['archetype_id', 'user_id', 'comment', 'is_moderated'];

    protected $casts = [
        'is_moderated' => 'boolean',
    ];

    public function archetype()
    {
        return $this->belongsTo(Archetype::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}