<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRequest extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'creator_identity_id',
        'client_identity_id',
        'requested_at',
        'booking_type',
        'status',
        'notes',
        'timezone',
    ];

    public function creatorIdentity(): BelongsTo
    { return $this->belongsTo(Identity::class, 'creator_identity_id'); }
    public function clientIdentity(): BelongsTo
    { return $this->belongsTo(Identity::class, 'client_identity_id'); }
}
