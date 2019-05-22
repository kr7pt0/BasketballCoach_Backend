<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;


define ('DASHBOARD_RECENT_GAME_COUNT', 10);


class HomeController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $games = $user->games();
        $tryCount = $games->sum('try_count');
        $successCount = $games->sum('score');

        $recentGames = $games->latest()->take(DASHBOARD_RECENT_GAME_COUNT)->get();
        $recentTryCount = $recentGames->sum('try_count');
        $recentSuccessCount = $recentGames->sum('score');

        $positions = $recentGames->load('shots')->map->shots->flatten(1);
        $recentGames = $recentGames->reverse()->values();

        return response()->json([
            'total_game_plays'  => $games->count(),
            'overall_accuracy'  => round($successCount / $tryCount, 3),
            'recent_accuracy'   => round($recentSuccessCount / $recentTryCount, 3),
            'history'           => $recentGames,
            'positions'         => $positions
        ]);
    }

    public function statistics(Request $request)
    {
        $user = $request->user();
        $recentMode = $request->input('mode', 'tries');
        $recentPeriod = $request->input('period', 1);
        // default period 1 is 'all' for tries mode, and 'today' for day mode

        $games = $user->games()->latest();
        if ($recentMode == 'tries') {
            if ($recentPeriod > 1) {
                $games = $games->take($recentPeriod);
            }
        } else { // day
            $since = Carbon::now()->subDays($recentPeriod);
            $games = $games->where('created_at', '>', $since);
        }
        $games = $games->get()->reverse()->values();

        $positions = $games->load('shots')->map->shots->flatten(1);

        return response()->json([
            'history'   => $games,
            'positions' => $positions
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $date = $request->input('date', date('Y-m-d'));

        $games = $user->games()
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'asc')
                ->get();

        return response()->json([
            'history'   => $games
        ]);
    }

    public function saveGame(Request $request)
    {
        $user = $request->user();

        return response()->json(['status' => 'ok']);
    }
}
