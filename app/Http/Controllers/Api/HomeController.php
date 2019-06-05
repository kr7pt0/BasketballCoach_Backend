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
            'overall_goals'     => $tryCount,
            'recent_accuracy'   => $recentTryCount == 0 ? 0 : round($recentSuccessCount / $recentTryCount, 3),
            'recent_goals'      => $recentSuccessCount,
            'history'           => $recentGames,
            'positions'         => $positions
        ]);
    }

    public function statistics(Request $request)
    {
        $user = $request->user();
        $mode = $request->input('mode', 'weekly');
        $date = $request->input('date', date('Y-m-d'));

        if ($mode != 'weekly' && $mode != 'monthly' && $mode != 'yearly') {
            $mode = 'weekly';
        }

        $select = $groupby = '';
        $where = sprintf('user_id = \'%s\' AND ', $user->id);

        switch ($mode) {
            case 'weekly':
                $start = sprintf('SUBDATE(\'%s\', WEEKDAY(\'%s\'))', $date, $date);
                $end = sprintf('ADDDATE(\'%s\', 7 - WEEKDAY(\'%s\'))', $date, $date);
                $where .= sprintf('games.created_at >= %s AND games.created_at <= %s', $start, $end);
                $groupby = 'DATE_FORMAT(games.created_at, \'%Y/%m/%d\')';
                break;

            case 'monthly':
                $where .= sprintf('DATE_FORMAT(games.created_at, \'%s\') = DATE_FORMAT(\'%s\', \'%s\')', '%Y%m', $date, '%Y%m');
                $groupby = 'DATE_FORMAT(games.created_at, \'%Y/%m/%d\')';
                break;

            case 'yearly':
            default:
                $where .= sprintf('YEAR(games.created_at) = YEAR(\'%s\')', $date);
                $groupby = 'DATE_FORMAT(games.created_at, \'%Y/%m\')';
                break;
        }

        $select = 'COUNT(games.id) AS game_times,';
        $select .= 'SUM(games.try_count) AS shots,';
        $select .= 'SUM(games.score) AS success,';
        $select .= 'ROUND(AVG(games.release_time), 2) AS release_time,';
        $select .= 'ROUND(AVG(games.release_angle), 2) AS release_angle,';
        $select .= 'ROUND(AVG(games.leg_angle), 2) AS leg_angle,';
        $select .= 'ROUND(AVG(games.elbow_angle), 2) AS elbow_angle,';
        $select .= $groupby . ' AS xkey';

        $query = sprintf('SELECT %s FROM %s WHERE %s GROUP BY xkey ORDER BY xkey',
                        $select, 'games', $where);
        $list = collect(DB::select($query))->map(function ($value) {
            $value->shots = intval($value->shots);
            $value->success = intval($value->success);
            return $value;
        });
        
        $select = 'ROUND(AVG(release_time), 2) AS release_time,';
        $select .= 'ROUND(AVG(release_angle), 2) AS release_angle,';
        $select .= 'ROUND(AVG(elbow_angle), 2) AS elbow_angle,';
        $select .= 'ROUND(AVG(leg_angle), 2) AS leg_angle ';
        $average = Game::selectRaw($select)->whereRaw($where)->first();
        
        $query = sprintf('SELECT x, y, success FROM shots' .
                        ' LEFT JOIN games ON shots.game_id = games.id' .
                        ' WHERE %s', $where);
        $positions = collect(DB::select($query));
        
        return response()->json([
            'list'      => $list,
            'average'   => $average,
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
