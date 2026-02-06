<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;

class SessionController extends Controller
{
    public function validateToken($token)
    {
        $session = Session::where('guest_token', $token)
                          ->where('status', 'ongoing')
                          ->with('table')
                          ->first();

        if (!$session) {
            return response()->json([
                'valid' => false,
                'message' => 'Session expired or invalid'
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'session' => $session,
            'table' => $session->table
        ]);
    }
}
