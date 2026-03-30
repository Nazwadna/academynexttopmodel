<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $table = 'portfolios';
    protected $primaryKey = 'id_portfolios';
    public $timestamps = false;

    protected $fillable = [
        'id_model',
        'nama_model',
        'photo',
        'description',
    ];

    public function model()
    {
        return $this->belongsTo(Talent::class, 'id_model', 'id_model');
    }
} 