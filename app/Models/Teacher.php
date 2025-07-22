<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Teacher extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name', 'father_name', 'phone_number', 'email', 'address', 'subject', 'class', 'profile_pic', 'password'
    ];
    protected $hidden = ['password', 'remember_token'];
}