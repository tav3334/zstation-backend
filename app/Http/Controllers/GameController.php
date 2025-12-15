<?php
namespace App\Http\Controllers;

use App\Models\Game;

class GameController extends Controller
{
    public function index()
    {
        return response()->json(
            Game::select('id', 'name')->get()
        );
    }
}
