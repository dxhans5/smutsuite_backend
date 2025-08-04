<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'avatar_url',
        'tagline',
        'pricing',
        'about',
        'is_visible',
        'hide_from_locals',
        'role',
        'location',
    ];

    protected $casts = [
        'pricing' => 'array',
        'is_visible' => 'boolean',
        'hide_from_locals' => 'boolean',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
