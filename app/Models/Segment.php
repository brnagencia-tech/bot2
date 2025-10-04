<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'rule_json', 'tenant_id'];
    protected $casts = ['rule_json' => 'array'];
}

