<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastJob extends Model
{
    use HasFactory;
    protected $fillable = ['segment_id', 'text', 'status', 'progress', 'tenant_id'];
}

