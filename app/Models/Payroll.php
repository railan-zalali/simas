<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;
    protected $fillable = [
        'nik',
        'nama',
        'total_days_worked',
        'total_overtime',
        'total_wage',
        'start_date',
        'end_date',
    ];
}
