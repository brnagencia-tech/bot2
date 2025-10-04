<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceSetting extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'tenant_id', 'key', 'value'];
    protected $casts = ['value' => 'array'];
}

