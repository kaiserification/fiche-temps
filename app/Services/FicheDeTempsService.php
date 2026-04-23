<?php

namespace App\Services;

use Carbon\Carbon;

class FicheDeTempsService
{
    public function getPeriod(): array
    {
        $startEnv = config('fiche.period_start');
        $endEnv   = config('fiche.period_end');

        if ($startEnv && $endEnv) {
            return [Carbon::parse($startEnv), Carbon::parse($endEnv)];
        }

        // Après le 19 : période en cours = 20 mois courant → 19 mois suivant
        // Avant/le 19 : période en cours = 20 mois précédent → 19 mois courant
        $now = Carbon::now();
        if ($now->day > 19) {
            return [$now->copy()->day(20), $now->copy()->addMonth()->day(19)];
        }

        return [$now->copy()->subMonth()->day(20), $now->copy()->day(19)];
    }
}
