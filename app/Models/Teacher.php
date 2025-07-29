<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['name', 'father_name', 'phone_number', 'email', 'address', 'class', 'subjects', 'password', 'profile_pic'];
    protected $casts = ['subjects' => 'array'];
}