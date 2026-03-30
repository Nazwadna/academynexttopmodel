<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talent extends Model
{
    protected $table = 'models'; // ubah dari 'talents'
    protected $primaryKey = 'id_model';
    public $timestamps = false;

    protected $fillable = [
        'nama_model',
        'city',
        'age',
        'height',
        'weight',
        'shoes_size',
        'bust',
        'waist',
        'size',
        'experience_value', // TAMBAH
        'experience_unit',  // TAMBAH
        'categories',
        'status',
        'photo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
