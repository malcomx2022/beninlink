<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperAdminPermission extends Model
{
    use HasFactory;
    protected $casts =
    [
        'keywords'=>'array'
    ];
}
