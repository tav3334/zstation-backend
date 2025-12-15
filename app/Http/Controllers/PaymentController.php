<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request)
{
    $data = $request->validate([
        'session_id' => 'required|exists:game_sessions,id',
        'amount' => 'required|numeric',
        'method' => 'required|in:cash,card,other'
    ]);

    $payment = Payment::create([
        'session_id' => $data['session_id'],
        'amount' => $data['amount'],
        'method' => $data['method'],
        'status' => 'paid',
        'paid_at' => now(),
        'created_by' => null
    ]);

    return $payment;
}

}
