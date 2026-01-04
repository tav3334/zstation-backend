<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\GameSession;
use App\Models\Machine;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // 📊 Statistiques complètes
    public function stats(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : today();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : today();

        // Paiements de la période
        $payments = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->with(['session.machine', 'session.game'])
            ->get();

        // Sessions de la période
        $sessions = GameSession::whereBetween('start_time', [$startDate, $endDate])
            ->with(['machine', 'game'])
            ->get();

        return response()->json([
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'label' => $startDate->isSameDay($endDate) 
                    ? $startDate->format('d/m/Y')
                    : $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y')
            ],
            'summary' => [
                'total_revenue' => $payments->sum('amount'),
                'total_sessions' => $sessions->count(),
                'active_sessions' => GameSession::whereNull('ended_at')->count(),
                'average_per_session' => $payments->count() > 0 
                    ? round($payments->sum('amount') / $payments->count(), 2) 
                    : 0,
                'total_cash_received' => $payments->sum('amount_given'),
                'total_change_given' => $payments->sum('change_given'),
            ],
            'by_machine' => $this->getStatsByMachine($payments),
            'by_game' => $this->getStatsByGame($payments),
            'by_hour' => $this->getStatsByHour($payments),
            'by_day' => $this->getStatsByDay($sessions, $startDate, $endDate),
        ]);
    }

    // 🖥️ Stats par machine
    private function getStatsByMachine($payments)
    {
        return $payments->groupBy(fn($p) => $p->session->machine->name)
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('amount')
            ])
            ->sortByDesc('revenue');
    }

    // 🎮 Stats par jeu
    private function getStatsByGame($payments)
    {
        return $payments->groupBy(fn($p) => $p->session->game->name)
            ->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('amount')
            ])
            ->sortByDesc('revenue');
    }

    // ⏰ Stats par heure
    private function getStatsByHour($payments)
    {
        return $payments->groupBy(fn($p) => $p->payment_date->format('H'))
            ->map(fn($group) => [
                'hour' => $group->first()->payment_date->format('H') . 'h',
                'count' => $group->count(),
                'revenue' => $group->sum('amount')
            ])
            ->sortBy('hour')
            ->values();
    }

    // 📅 Stats par jour (pour graphique mensuel)
    private function getStatsByDay($sessions, $startDate, $endDate)
    {
        $days = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $daySessions = $sessions->filter(fn($s) => 
                Carbon::parse($s->start_time)->isSameDay($currentDate)
            );

            $days[] = [
                'date' => $currentDate->format('d/m'),
                'count' => $daySessions->count(),
                'revenue' => $daySessions->sum('computed_price')
            ];

            $currentDate->addDay();
        }

        return $days;
    }

    // 📋 Historique des paiements
    public function payments(Request $request)
    {
        $query = Payment::with(['session.machine', 'session.game', 'staff'])
            ->orderBy('payment_date', 'desc');

        // Filtrer par date
        if ($request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $payments = $query->paginate(50);

        return response()->json($payments);
    }

    // 📋 Historique des sessions
    public function sessions(Request $request)
    {
        $query = GameSession::with(['machine', 'game', 'gamePricing'])
            ->orderBy('start_time', 'desc');

        // Filtrer par date
        if ($request->start_date) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Filtrer par statut
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $sessions = $query->paginate(50);

        return response()->json($sessions);
    }
}