<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $visible = ['mode', 'release_angle', 'release_time',
        'elbow_angle', 'leg_angle',
        'try_count', 'score', 'created_at'];
    
    public function shots()
    {
        return $this->hasMany(Shot::class);
    } 
}
