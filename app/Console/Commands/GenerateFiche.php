<?php

namespace App\Console\Commands;

use App\Services\FicheDeTempsService;
use Illuminate\Console\Command;

class GenerateFiche extends Command
{
    protected $signature   = 'fiche:generate {--projet=} {--bu=}';
    protected $description = 'Génère la fiche de temps du mois en cours';

    public function handle(FicheDeTempsService $svc)
    {
        $workdays = $svc->getWorkdays();
        $projet   = $this->option('projet') ?? '';
        $bu       = $this->option('bu') ?? '';

        foreach ($workdays as $day) {
            $this->info("→ " . $day->format('d/m/Y'));
            $tasks = $svc->generateForDay($day, [], $projet, $bu);
            foreach ($tasks as $i => $task) {
                $this->line("   " . ($i + 1) . ". $task");
            }
            $this->newLine();
        }
    }
}
