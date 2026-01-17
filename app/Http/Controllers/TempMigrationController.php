<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TempMigrationController extends Controller
{
    /**
     * Execute match-based pricing migration and seeders
     * TEMPORARY ENDPOINT - DELETE AFTER USE
     */
    public function executeMatchPricingMigration()
    {
        try {
            $results = [];

            // 1. Run migration
            $results[] = "Running migration...";
            Artisan::call('migrate', ['--force' => true]);
            $results[] = "✓ Migration completed: " . Artisan::output();

            // 2. Verify pricing_modes table
            $pricingModes = DB::table('pricing_modes')->get();
            $results[] = "Pricing modes count: " . $pricingModes->count();
            foreach ($pricingModes as $mode) {
                $results[] = "  - {$mode->id}: {$mode->code} ({$mode->label})";
            }

            // 3. Run GamePricingSeeder
            $results[] = "\nRunning GamePricingSeeder...";
            Artisan::call('db:seed', [
                '--class' => 'GamePricingSeeder',
                '--force' => true
            ]);
            $results[] = "✓ GamePricingSeeder completed: " . Artisan::output();

            // 4. Run MatchBasedPricingSeeder
            $results[] = "\nRunning MatchBasedPricingSeeder...";
            Artisan::call('db:seed', [
                '--class' => 'MatchBasedPricingSeeder',
                '--force' => true
            ]);
            $results[] = "✓ MatchBasedPricingSeeder completed: " . Artisan::output();

            // 5. Verify FIFA pricing
            $fifa = DB::table('games')->where('name', 'FIFA 24')->first();
            if ($fifa) {
                $fifaPricings = DB::table('game_pricings')
                    ->where('game_id', $fifa->id)
                    ->join('pricing_modes', 'game_pricings.pricing_mode_id', '=', 'pricing_modes.id')
                    ->select('pricing_modes.code as mode', 'game_pricings.duration_minutes', 'game_pricings.matches_count', 'game_pricings.price')
                    ->get();

                $results[] = "\nFIFA 24 Pricings:";
                foreach ($fifaPricings as $p) {
                    $results[] = "  - Mode: {$p->mode}, Duration: {$p->duration_minutes}, Matches: {$p->matches_count}, Price: {$p->price} DH";
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Match-based pricing migration completed successfully',
                'details' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
