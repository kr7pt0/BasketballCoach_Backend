<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Game;
use App\Shot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


define ('DASHBOARD_RECENT_GAME_COUNT', 10);


class GameController extends Controller
{
    public function gameCheck(Request $request)
    {
        $gaming = User::whereNotNull('gaming')->first();

        if ($gaming) {
            return response()->json([
                'status'    => 'occupied',
                'user'      => $gaming->name
            ]);
        }
        return response()->json(['status' => 'free']);
    }

    public function saveGame(Request $request)
    {
        $gameMode = $request->input('gameMode', null);
        $releaseAngle = $request->input('releaseAngle', null);
        $releaseTime = $request->input('releaseTime', null);
        $elbowAngle = $request->input('elbowAngle', null);
        $legAngle = $request->input('legAngle', null);
        $tryCount = $request->input('tryCount', null);
        $score = $request->input('score', null);
        $positions = $request->input('positions', null);

        if (is_null($releaseAngle) ||
            is_null($releaseTime) ||
            is_null($elbowAngle) ||
            is_null($legAngle) ||
            is_null($tryCount) ||
            is_null($score) ||
            is_null($positions)) {
            return response()->json(['status' => 'invalid parameters'], 400);
        }

        $user = $request->user();

//      if ($user->gaming)
        {
            $shots = [];
            foreach ($positions as $p) {
                $shots[] = [
                    'x' => $p['x'],
                    'y' => $p['y'],
                    'success' => $p['success'] ? 1 : 0
                ];
            }

            $game = $user->games()->create([
                'mode'          => $gameMode,
                'release_angle' => $releaseAngle,
                'release_time'  => $releaseTime,
                'elbow_angle'   => $elbowAngle,
                'leg_angle'     => $legAngle,
                'try_count'     => $tryCount,
                'score'         => $score
            ]);

            $game->shots()->createMany($shots);
            $user->gaming = null;
            $user->save();

            return response()->json(['status' => 'ok']);
        }
//        return response()->json(['status' => 'not started'], 403);
    }

    public function gameStart(Request $request)
    {
        $mode = $request->input('mode', 'FREE_THROW');
        $user = $request->user();

        if ($mode != 'FREE_THROW' && $mode != 'DRILLS') {
            return response()->json(['status' => 'invalid parameters'], 400);
        }

        $gaming = User::whereNotNull('gaming')->first();

        if ($gaming) {
            return response()->json(['status' => 'occupied'], 403);
        }

        User::where('id', '>', 0)->update(['gaming' => null]);
        $user->gaming = $mode;
        $user->save();

        return response()->json(['status' => 'ok']);

    }

    public function gameCancel(Request $request)
    {
        $user = $request->user();

        if ($user->gaming) {
            $user->gaming = null;
            $user->save();
    
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'not started'], 403);
    }
}
