<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


define ('DASHBOARD_RECENT_GAME_COUNT', 3);


class HomeController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $games = $user->games();
        $tryCount = $games->sum('try_count');
        $successCount = $games->sum('score');

        $recentGames = $games->oldest()->take(DASHBOARD_RECENT_GAME_COUNT)->get();
        $recentTryCount = $recentGames->sum('try_count');
        $recentSuccessCount = $recentGames->sum('score');

        $positions = $recentGames->load('shots')->map->shots->flatten(1);

        return response()->json([
            'total_game_plays'  => $games->count(),
            'overall_accuracy'  => round($successCount / $tryCount, 3),
            'recent_accuracy'   => round($recentSuccessCount / $recentTryCount, 3),
            'history'           => $recentGames,
            'positions'         => $positions
        ]);
    }
}
