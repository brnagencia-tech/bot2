<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRun extends Model
{
    use HasFactory;
    protected $fillable = ['flow_id', 'contact_id', 'status', 'last_error'];
}

