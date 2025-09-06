<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AvailabilityRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'identity_id',
        'day_of_week',
        'start_time',
        'end_time',
        'booking_type',
        'is_available',  // Fixed this field name
    ];

    protected $casts = [
        'is_available' => 'bool',  // Fixed this field name
    ];

    public function identity()
    {
        return $this->belongsTo(\App\Models\Identity::class);
    }
}
