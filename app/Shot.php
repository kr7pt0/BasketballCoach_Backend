<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shot extends Model
{
    protected $visible = ['x', 'y', 'success'];

    protected $fillable = ['x', 'y', 'success'];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
