<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrivateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notes',
        'journal',
        'favorite_kinks',
        'mood',
        'emotional_state',
        'timezone',
        'custom_fields',
    ];

    protected $casts = [
        'notes' => 'array',
        'journal' => 'array',
        'favorite_kinks' => 'array',
        'custom_fields' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
