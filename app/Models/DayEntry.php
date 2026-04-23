<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayEntry extends Model
{
    protected $fillable = ['fiche_id', 'day', 'tasks', 'commits', 'meetings'];
    protected $casts    = ['day' => 'date', 'tasks' => 'array', 'commits' => 'array', 'meetings' => 'array'];

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
