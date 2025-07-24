<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAttendance extends Model
{
    protected $fillable = ['teacher_name', 'email', 'date', 'status', 'percentage'];
}