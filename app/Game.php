<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    //
    protected $visible = ['release_angle', 'release_time', 'elbow_angle', 'leg_angle'];
    
    public function shots()
    {
        return $this->hasMany(Shot::class);
    } 
}
