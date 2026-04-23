<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingTemplate extends Model
{
    protected $fillable = ['label', 'default_checked', 'sort_order'];
}
