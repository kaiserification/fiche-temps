<?php

namespace App\Models;

use App\Models\DayEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fiche extends Model
{
    protected $fillable = ['user_id', 'matricule', 'period_start', 'period_end', 'projet', 'business_unit'];
    protected $casts    = ['period_start' => 'date', 'period_end' => 'date'];

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dayEntries(): HasMany
    {
        return $this->hasMany(DayEntry::class);
    }
}
