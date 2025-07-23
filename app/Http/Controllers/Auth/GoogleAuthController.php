<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function handle(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Google auth not implemented yet.',
        ], 501);
    }
}
