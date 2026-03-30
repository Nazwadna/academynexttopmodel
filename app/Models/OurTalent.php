<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OurTalent extends Model
{
    protected $table = 'ourtalents';

    protected $fillable = ['image', 'order'];
}
