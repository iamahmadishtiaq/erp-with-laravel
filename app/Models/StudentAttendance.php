<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    protected $fillable = ['student_name', 'roll_number', 'date', 'status', 'percentage'];
}