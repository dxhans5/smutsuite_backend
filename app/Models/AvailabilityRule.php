<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AvailabilityRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'identity_id',
        'day_of_week',
        'start_time',
        'end_time',
        'booking_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function identity()
    {
        return $this->belongsTo(\App\Models\Identity::class);
    }
}
