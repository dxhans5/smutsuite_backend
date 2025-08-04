<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('test.ping', function ( $user) {
    return true; // or auth logic
});
