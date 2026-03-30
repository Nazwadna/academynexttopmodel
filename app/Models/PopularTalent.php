<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopularTalent extends Model
{
    protected $table = 'popular_talents';

    protected $fillable = ['image', 'order'];
}
