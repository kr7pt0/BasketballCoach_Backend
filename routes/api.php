<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth::routes();

Route::post('/register', 'Auth\RegisterController@register');

Route::post('/login', 'Auth\LoginController@login');

Route::middleware('auth:api')->group(function () {
    
    Route::post('/logout', 'Auth\LoginController@logout');

    Route::namespace('Api')->group(function () {
        
        // Rest for App

        Route::get('/dashboard', 'HomeController@dashboard');

        Route::get('/statistics', 'HomeController@statistics');
        
        Route::get('/history', 'HomeController@history');
        
        Route::get('/leaderboard', 'HomeController@leaderboard');

        // Rest for python

        /*
         *  Check if other user is in game session
         *  This is the endpoint for frontend, no need to be called by python
         *  as on-coming game start endpoint will check legitimacy for game creation
         * 
         *  { 'status': 'free' }
         *  { 'status': 'occupied, 'user': 'Jack' }
         * 
         */
        Route::get('/game/check', 'GameController@gameCheck');

        /*
         *  Start game if nobody plays game
         * 
         *  @params
         *      mode: FREE_THROW | DRILLS
         * 
         *  { 'status': 'ok' }
         *  { 'status': 'invalid parameters' }, 400
         *  { 'status': 'occupied, 'user': 'Jack' }, 403
         * 
         */
        Route::post('/game/start', 'GameController@gameStart');

        /*
         *  Cancel game if use has started game
         * 
         *  { 'status': 'ok' }
         *  { 'status': 'not started' }, 403
         * 
         */
        Route::post('/game/cancel', 'GameController@gameCancel');

        /*
         *  Save game into db if python submits game data user has just played
         *  JSON Request should be made for this endpoint
         * 
         *  @header
         *      Content-Type:   application/json
         * 
         *  @params
         *    {
         *      releaseAngle
         *      releaseTime
         *      elbowAngle
         *      legAngle
         *      tryCount
         *      score
         *      positions [
         *          {
         *              x
         *              y
         *              success: 0 | 1
         *          }
         *      ]
         *    }
         * 
         *  { 'status': 'ok' }
         *  { 'status': 'invalid parameters' }, 400
         *  { 'status': 'not started' }, 403
         * 
         */
        Route::post('/game', 'GameController@saveGame');
    
    });
});