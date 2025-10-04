<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'json', 'tenant_id'];
    protected $casts = ['json' => 'array'];
}

