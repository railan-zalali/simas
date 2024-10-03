<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class payrolls extends Model
{
    use HasFactory;

    protected $fillable = [
        'linmas_id',
        'periode',
        'gaji_pokok',
        'lembur',
        'pengurangan',
        'total_gaji'
    ];

    public function linmas()
    {
        return $this->belongsTo(Linmas::class);
    }
}
