<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Class12Student extends Model
{
    protected $table = 'class_12_students';
    protected $fillable = ['name', 'roll_number'];
}