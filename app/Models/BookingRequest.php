<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'client_id',
        'requested_at',
        'booking_type',
        'status',
        'notes',
        'timezone',
    ];

    public function creator() {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }
}
