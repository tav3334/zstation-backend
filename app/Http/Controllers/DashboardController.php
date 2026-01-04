<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameSession;
use App\Models\Payment;
use App\Models\Machine;
use App\Models\Game;
use App\Models\ProductSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Statistiques générales du dashboard
     * GET /api/dashboard/stats
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', 'today'); // today, week, month, all

        // Si des dates personnalisées sont fournies, les utiliser
        if ($request->has('start_date') && $request->has('end_date')) {
            $dateFrom = Carbon::parse($request->start_date)->startOfDay();
            $dateTo = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $dateFrom = $this->getDateFrom($period);
            $dateTo = null;
        }

        // Revenue total (sessions)
        $totalRevenue = Payment::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('payment_date', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('payment_date', '<=', $dateTo);
            }
            return $query;
        })->sum('amount');

        // Revenue produits
        $productRevenue = ProductSale::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('sale_date', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('sale_date', '<=', $dateTo);
            }
            return $query;
        })->sum('total_price');

        // Revenue total combiné
        $totalCombinedRevenue = $totalRevenue + $productRevenue;

        // Nombre de ventes de produits
        $totalProductSales = ProductSale::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('sale_date', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('sale_date', '<=', $dateTo);
            }
            return $query;
        })->count();

        // Nombre de sessions
        $totalSessions = GameSession::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('start_time', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('start_time', '<=', $dateTo);
            }
            return $query;
        })->count();

        // Sessions actives
        $activeSessions = GameSession::whereNull('ended_at')->count();

        // Machines disponibles
        $availableMachines = Machine::whereDoesntHave('activeSession')->count();
        $totalMachines = Machine::count();

        // Paiements aujourd'hui
        $paymentsToday = Payment::whereDate('payment_date', Carbon::today())->count();

        // Revenue par méthode de paiement
        $revenueByMethod = Payment::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('payment_date', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('payment_date', '<=', $dateTo);
            }
            return $query;
        })
        ->select('payment_method', DB::raw('SUM(amount) as total'))
        ->groupBy('payment_method')
        ->get();

        // Top jeux (les plus joués)
        $topGames = GameSession::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('start_time', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('start_time', '<=', $dateTo);
            }
            return $query;
        })
        ->select('game_id', DB::raw('COUNT(*) as sessions_count'), DB::raw('SUM(computed_price) as total_revenue'))
        ->with('game:id,name')
        ->groupBy('game_id')
        ->orderByDesc('sessions_count')
        ->limit(5)
        ->get()
        ->map(function ($item) {
            return [
                'game_id' => $item->game_id,
                'game_name' => $item->game->name ?? 'N/A',
                'sessions_count' => $item->sessions_count,
                'total_revenue' => (float) $item->total_revenue
            ];
        });

        // Revenue par jour (7 derniers jours)
        $dailyRevenue = Payment::where('payment_date', '>=', Carbon::now()->subDays(7))
            ->select(DB::raw('DATE(payment_date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total' => (float) $item->total
                ];
            });

        // Durée moyenne de session
        $avgSessionDuration = GameSession::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('start_time', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('start_time', '<=', $dateTo);
            }
            return $query;
        })
        ->whereNotNull('ended_at')
        ->get()
        ->map(function ($session) {
            return $session->start_time->diffInMinutes($session->ended_at);
        })
        ->avg();

        // Top produits vendus
        $topProducts = ProductSale::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('sale_date', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('sale_date', '<=', $dateTo);
            }
            return $query;
        })
        ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_revenue'))
        ->with('product:id,name,size,category')
        ->groupBy('product_id')
        ->orderByDesc('total_quantity')
        ->limit(5)
        ->get()
        ->map(function ($item) {
            $productName = $item->product->name ?? 'N/A';
            if ($item->product->size) {
                $productName .= ' (' . $item->product->size . ')';
            }
            return [
                'product_id' => $item->product_id,
                'product_name' => $productName,
                'category' => $item->product->category ?? 'N/A',
                'total_quantity' => $item->total_quantity,
                'total_revenue' => (float) $item->total_revenue
            ];
        });

        // Revenue produits par méthode de paiement
        $productRevenueByMethod = ProductSale::when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('sale_date', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('sale_date', '<=', $dateTo);
            }
            return $query;
        })
        ->select('payment_method', DB::raw('SUM(total_price) as total'))
        ->groupBy('payment_method')
        ->get();

        return response()->json([
            'period' => $period,
            'stats' => [
                'total_revenue' => (float) $totalRevenue,
                'product_revenue' => (float) $productRevenue,
                'total_combined_revenue' => (float) $totalCombinedRevenue,
                'total_sessions' => $totalSessions,
                'total_product_sales' => $totalProductSales,
                'active_sessions' => $activeSessions,
                'available_machines' => $availableMachines,
                'total_machines' => $totalMachines,
                'payments_today' => $paymentsToday,
                'avg_session_duration' => round($avgSessionDuration ?? 0, 2),
            ],
            'revenue_by_method' => $revenueByMethod->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'total' => (float) $item->total
                ];
            }),
            'product_revenue_by_method' => $productRevenueByMethod->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'total' => (float) $item->total
                ];
            }),
            'top_games' => $topGames,
            'top_products' => $topProducts,
            'daily_revenue' => $dailyRevenue
        ]);
    }

    /**
     * Liste des paiements récents
     * GET /api/dashboard/payments
     */
    public function payments(Request $request)
    {
        $limit = $request->get('limit', 20);
        $period = $request->get('period', 'today');

        // Si des dates personnalisées sont fournies, les utiliser
        if ($request->has('start_date') && $request->has('end_date')) {
            $dateFrom = Carbon::parse($request->start_date)->startOfDay();
            $dateTo = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $dateFrom = $this->getDateFrom($period);
            $dateTo = null;
        }

        $payments = Payment::with([
            'session.game:id,name',
            'session.machine:id,name',
            'staff:id,name'
        ])
        ->when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('payment_date', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('payment_date', '<=', $dateTo);
            }
            return $query;
        })
        ->orderByDesc('payment_date')
        ->limit($limit)
        ->get()
        ->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'amount_given' => (float) $payment->amount_given,
                'change_given' => (float) $payment->change_given,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date->toISOString(),
                'game_name' => $payment->session->game->name ?? 'N/A',
                'machine_name' => $payment->session->machine->name ?? 'N/A',
                'staff_name' => $payment->staff->name ?? 'N/A',
                'notes' => $payment->notes
            ];
        });

        return response()->json([
            'payments' => $payments
        ]);
    }

    /**
     * Liste des sessions récentes
     * GET /api/dashboard/sessions
     */
    public function sessions(Request $request)
    {
        $limit = $request->get('limit', 20);
        $status = $request->get('status', 'all'); // all, active, completed
        $period = $request->get('period', 'today');

        // Si des dates personnalisées sont fournies, les utiliser
        if ($request->has('start_date') && $request->has('end_date')) {
            $dateFrom = Carbon::parse($request->start_date)->startOfDay();
            $dateTo = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $dateFrom = $this->getDateFrom($period);
            $dateTo = null;
        }

        $query = GameSession::with([
            'game:id,name',
            'machine:id,name',
            'customer:id,name,phone',
            'gamePricing:id,duration_minutes,price'
        ])
        ->when($dateFrom, function ($query) use ($dateFrom, $dateTo) {
            $query->where('start_time', '>=', $dateFrom);
            if ($dateTo) {
                $query->where('start_time', '<=', $dateTo);
            }
            return $query;
        });

        // Filtrer par status
        if ($status === 'active') {
            $query->whereNull('ended_at');
        } elseif ($status === 'completed') {
            $query->whereNotNull('ended_at');
        }

        $sessions = $query->orderByDesc('start_time')
            ->limit($limit)
            ->get()
            ->map(function ($session) {
                $duration = null;
                if ($session->ended_at) {
                    $duration = $session->start_time->diffInMinutes($session->ended_at);
                }

                return [
                    'id' => $session->id,
                    'game_name' => $session->game->name ?? 'N/A',
                    'machine_name' => $session->machine->name ?? 'N/A',
                    'customer_name' => $session->customer->name ?? 'N/A',
                    'customer_phone' => $session->customer->phone ?? null,
                    'start_time' => $session->start_time->toISOString(),
                    'ended_at' => $session->ended_at?->toISOString(),
                    'duration_minutes' => $duration,
                    'computed_price' => (float) $session->computed_price,
                    'status' => $session->status,
                    'is_active' => $session->ended_at === null
                ];
            });

        return response()->json([
            'sessions' => $sessions
        ]);
    }

    /**
     * Helper pour obtenir la date de début selon la période
     */
    private function getDateFrom($period)
    {
        return match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => null, // all time
        };
    }
}
