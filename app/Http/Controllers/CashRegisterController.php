<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\ProductSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashRegisterController extends Controller
{
    // Obtenir la caisse du jour (ou la créer si elle n'existe pas)
    public function today()
    {
        $today = Carbon::today()->toDateString();

        $register = CashRegister::where('date', $today)->first();

        if (!$register) {
            // Récupérer le fond de caisse du jour précédent
            $previousRegister = CashRegister::where('date', '<', $today)
                ->orderBy('date', 'desc')
                ->first();

            $openingBalance = $previousRegister
                ? $previousRegister->closing_balance ?? $previousRegister->current_balance
                : 0;

            $register = CashRegister::create([
                'date' => $today,
                'opening_balance' => $openingBalance,
                'total_cash_in' => 0,
                'total_change_out' => 0,
                'opened_by' => Auth::id(),
                'opened_at' => now(),
            ]);
        }

        // Calculer les totaux réels depuis les paiements du jour
        $this->syncWithPayments($register);

        return response()->json([
            'register' => $this->formatRegister($register),
        ]);
    }

    // Synchroniser avec les paiements réels
    private function syncWithPayments(CashRegister $register)
    {
        $date = $register->date;

        // Total cash reçu des sessions
        $sessionsCash = Payment::whereDate('created_at', $date)
            ->sum('cash_received');

        // Total monnaie rendue des sessions
        $sessionsChange = Payment::whereDate('created_at', $date)
            ->sum('change_given');

        // Total cash des ventes de produits (si paiement cash)
        $productsCash = ProductSale::whereDate('sale_date', $date)
            ->where('payment_method', 'cash')
            ->sum('total_price');

        $register->total_cash_in = $sessionsCash + $productsCash;
        $register->total_change_out = $sessionsChange;
        $register->save();
    }

    // Formater la réponse
    private function formatRegister(CashRegister $register)
    {
        return [
            'id' => $register->id,
            'date' => $register->date->format('Y-m-d'),
            'date_formatted' => $register->date->format('d/m/Y'),
            'opening_balance' => (float) $register->opening_balance,
            'total_cash_in' => (float) $register->total_cash_in,
            'total_change_out' => (float) $register->total_change_out,
            'withdrawn_amount' => (float) $register->withdrawn_amount,
            'current_balance' => (float) $register->current_balance,
            'net_profit' => (float) $register->net_profit,
            'closing_balance' => $register->closing_balance ? (float) $register->closing_balance : null,
            'is_open' => $register->is_open,
            'opened_at' => $register->opened_at?->format('H:i'),
            'closed_at' => $register->closed_at?->format('H:i'),
            'notes' => $register->notes,
        ];
    }

    // Définir le fond de caisse initial
    public function setOpeningBalance(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $today = Carbon::today()->toDateString();
        $register = CashRegister::where('date', $today)->first();

        if (!$register) {
            $register = CashRegister::create([
                'date' => $today,
                'opening_balance' => $request->amount,
                'opened_by' => Auth::id(),
                'opened_at' => now(),
            ]);
        } else {
            $register->opening_balance = $request->amount;
            $register->save();
        }

        $this->syncWithPayments($register);

        return response()->json([
            'message' => 'Fond de caisse mis à jour',
            'register' => $this->formatRegister($register),
        ]);
    }

    // Retirer de l'argent (profit)
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $today = Carbon::today()->toDateString();
        $register = CashRegister::where('date', $today)->first();

        if (!$register) {
            return response()->json(['message' => 'Caisse non ouverte'], 400);
        }

        $this->syncWithPayments($register);

        if ($request->amount > $register->current_balance) {
            return response()->json([
                'message' => 'Montant insuffisant dans la caisse',
                'available' => $register->current_balance,
            ], 400);
        }

        $register->withdrawn_amount += $request->amount;
        if ($request->notes) {
            $register->notes = ($register->notes ? $register->notes . "\n" : '') .
                now()->format('H:i') . " - Retrait: {$request->amount} DH" .
                ($request->notes ? " ({$request->notes})" : '');
        }
        $register->save();

        return response()->json([
            'message' => "Retrait de {$request->amount} DH effectué",
            'register' => $this->formatRegister($register),
        ]);
    }

    // Fermer la caisse du jour
    public function close(Request $request)
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $today = Carbon::today()->toDateString();
        $register = CashRegister::where('date', $today)->first();

        if (!$register) {
            return response()->json(['message' => 'Caisse non ouverte'], 400);
        }

        $this->syncWithPayments($register);

        $register->closing_balance = $request->closing_balance;
        $register->closed_by = Auth::id();
        $register->closed_at = now();
        if ($request->notes) {
            $register->notes = ($register->notes ? $register->notes . "\n" : '') .
                "Fermeture: " . $request->notes;
        }
        $register->save();

        // Calculer la différence
        $expectedBalance = $register->current_balance;
        $difference = $request->closing_balance - $expectedBalance;

        return response()->json([
            'message' => 'Caisse fermée',
            'register' => $this->formatRegister($register),
            'expected_balance' => $expectedBalance,
            'actual_balance' => $request->closing_balance,
            'difference' => $difference,
        ]);
    }

    // Historique des caisses
    public function history(Request $request)
    {
        $limit = $request->get('limit', 30);

        $registers = CashRegister::orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($r) => $this->formatRegister($r));

        return response()->json([
            'registers' => $registers,
        ]);
    }
}
