<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\GameSession;
use App\Models\ProductSale;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // 💰 Enregistrer un paiement CASH
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:game_sessions,id',
            'amount_given' => 'required|numeric|min:0',
        ]);

        $session = GameSession::with('gamePricing')->findOrFail($request->session_id);

        // Vérifier que la session est terminée
        if (!$session->ended_at) {
            return response()->json([
                'message' => 'Cannot create payment for active session'
            ], 409);
        }

        // Vérifier qu'il n'y a pas déjà un paiement
        if (Payment::where('session_id', $session->id)->exists()) {
            return response()->json([
                'message' => 'Payment already exists for this session'
            ], 409);
        }

        $amount = $session->computed_price;
        $amountGiven = $request->amount_given;
        $change = $amountGiven - $amount;

        if ($change < 0) {
            return response()->json([
                'message' => 'Montant insuffisant',
                'required' => $amount,
                'given' => $amountGiven,
                'missing' => abs($change)
            ], 422);
        }

        // Créer le paiement
        $payment = Payment::create([
            'session_id' => $session->id,
            'amount' => $amount,
            'amount_given' => $amountGiven,
            'change_given' => $change,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'staff_id' => null, // À remplir si authentification
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Paiement enregistré avec succès',
            'payment' => $payment->load(['session.machine', 'session.game']),
            'receipt' => [
                'session_id' => $session->id,
                'machine' => $session->machine->name,
                'game' => $session->game->name,
                'duration' => now()->diffInMinutes($session->start_time) . ' min',
                'amount' => number_format($amount, 2) . ' DH',
                'amount_given' => number_format($amountGiven, 2) . ' DH',
                'change' => number_format($change, 2) . ' DH',
                'date' => now()->format('d/m/Y H:i')
            ]
        ], 201);
    }

    // 📊 Paiements du jour
    public function today()
    {
        $payments = Payment::whereDate('payment_date', today())
            ->with(['session.machine', 'session.game'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $total = $payments->sum('amount');
        $totalGiven = $payments->sum('amount_given');
        $totalChange = $payments->sum('change_given');

        // Ventes de produits du jour
        $productSales = ProductSale::whereDate('sale_date', today())
            ->with('product')
            ->get();

        $productRevenue = $productSales->sum('total_price');
        $productCount = $productSales->count();

        return response()->json([
            'date' => today()->format('d/m/Y'),
            'total_revenue' => number_format($total, 2) . ' DH',
            'total_cash_received' => number_format($totalGiven, 2) . ' DH',
            'total_change_given' => number_format($totalChange, 2) . ' DH',
            'count' => $payments->count(),
            'product_revenue' => number_format($productRevenue, 2) . ' DH',
            'product_sales_count' => $productCount,
            'total_combined_revenue' => number_format($total + $productRevenue, 2) . ' DH',
            'payments' => $payments->map(function($p) {
                return [
                    'id' => $p->id,
                    'time' => $p->payment_date->format('H:i'),
                    'machine' => $p->session->machine->name,
                    'game' => $p->session->game->name,
                    'amount' => $p->amount . ' DH',
                    'given' => $p->amount_given . ' DH',
                    'change' => $p->change_given . ' DH',
                ];
            })
        ]);
    }

    // 📈 Statistiques période
    public function stats(Request $request)
    {
        $startDate = $request->start_date ?? today();
        $endDate = $request->end_date ?? today();

        $payments = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->with(['session.machine', 'session.game'])
            ->get();

        $byMachine = $payments->groupBy(fn($p) => $p->session->machine->name)
            ->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ]);

        $byGame = $payments->groupBy(fn($p) => $p->session->game->name)
            ->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ]);

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'total_revenue' => $payments->sum('amount'),
            'total_sessions' => $payments->count(),
            'average_per_session' => $payments->count() > 0 ? round($payments->sum('amount') / $payments->count(), 2) : 0,
            'by_machine' => $byMachine,
            'by_game' => $byGame,
            'hourly' => $payments->groupBy(fn($p) => $p->payment_date->format('H'))
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'total' => $group->sum('amount')
                ])
        ]);
    }

    // 🔍 Détails d'un paiement
    public function show($id)
    {
        $payment = Payment::with(['session.machine', 'session.game'])->findOrFail($id);

        return response()->json($payment);
    }

    // 🗑️ Annuler un paiement (admin uniquement)
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        // Vérifier que le paiement date de moins de 24h
        if ($payment->payment_date->lt(now()->subDay())) {
            return response()->json([
                'message' => 'Cannot delete payment older than 24h'
            ], 403);
        }

        $payment->delete();

        return response()->json([
            'message' => 'Paiement annulé avec succès'
        ]);
    }
}