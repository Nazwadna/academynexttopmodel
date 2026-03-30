<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedTalent extends Model
{
    protected $table = 'featured_talents';
    protected $fillable = ['id_model', 'order'];

    public function talent()
    {
        return $this->belongsTo(Talent::class, 'id_model', 'id_model');
    }
} 