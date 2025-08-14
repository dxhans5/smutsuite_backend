<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentitySwitchLog extends Model
{
    use HasFactory;

    protected $table = 'identity_switch_logs';

    protected $fillable = [
        'user_id',
        'from_id',
        'to_id',
        'switched_at',
        'context',
    ];

    protected $casts = [
        'user_id'     => 'string',
        'from_id'     => 'string',
        'to_id'       => 'string',
        'switched_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
