<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Game;
use App\Shot;
use App\IdealValue;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;


define ('DASHBOARD_RECENT_GAME_COUNT', 10);
define ('MAX_SCORE', 1000);


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
            'overall_accuracy'  => $tryCount == 0 ? 0 : round($successCount / $tryCount, 3),
            'recent_accuracy'   => $recentTryCount == 0 ? 0 : round($recentSuccessCount / $recentTryCount, 3),
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

    public function leaderboard(Request $request)
    {
        $user = $request->user();
        $rank = [];
        $ideals = [
            'GOALS'         => [ 'field' => 'score', 'value' => -2 ],
            'ACCURACY'      => [ 'field' => 'score / try_count', 'value' => -1 ],
            'RELEASE_ANGLE' => [ 'field' => 'release_angle', 'value' => 0],
            'RELEASE_TIME'  => [ 'field' => 'release_time', 'value' => 0],
            'ELBOW_ANGLE'   => [ 'field' => 'elbow_angle', 'value' => 0],
            'LEG_ANGLE'     => [ 'field' => 'leg_angle', 'value' => 0]
        ];

        IdealValue::all()->each(function ($item, $key) use (&$ideals) {
            $ideals[$item->key]['value'] = $item->val;
        });


        foreach ($ideals as $key => $ideal) {
            $query = sprintf(
                'SELECT t.data, users.name, t.user_id'.
                ' FROM ('.
                '   SELECT'.
                '       ROUND(%s(%s), 2) AS data,'.
                '       created_at,'.
                '       user_id'.
                '   FROM'.
                '       games'.
                '   WHERE created_at >= \'%s\''.
                '   GROUP BY user_id'.
                ' ) AS t'.
                ' RIGHT JOIN users ON t.user_id = users.id'.
                ' ORDER BY t.data IS NULL, t.data %s',
                $ideal['value'] < -1 ? 'SUM' : 'AVG',
                $ideal['field'],
                Carbon::now()->subDays(30),
                $ideal['value'] >= 0 ? 'ASC' : 'DESC'
            );

            $list = collect(DB::select($query));

            $user_rank = 0;
            $list = $list->map(function ($item, $key) use ($user, &$user_rank) {
                if ($item->user_id == $user->id) {
                    $user_rank = $key + 1;
                }
                return ['data' => floatVal($item->data), 'name' => $item->name ];
            });

            $rank[$key] = [
                "list" => $list,
                "rank" => $user_rank,
            ];
        }

        return response()->json($rank);
    }
}
