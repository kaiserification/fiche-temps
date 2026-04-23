<?php

namespace App\Exports;

use App\Models\Fiche;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping};

class FicheExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Fiche $fiche) {}

    public function headings(): array
    {
        return ['Date', 'Projet', 'Business Unit', 'Liste des tâches', 'Commentaire'];
    }

    public function collection()
    {
        return $this->fiche->dayEntries()
            ->orderBy('day')
            ->get()
            ->filter(fn($e) => !empty($e->tasks));
    }

    public function map($entry): array
    {
        $tasks = collect($entry->tasks)
            ->map(fn($t, $i) => ($i + 1) . ". $t")
            ->implode("\n");

        return [
            Carbon::parse($entry->day)->format('d/m/Y'),
            $this->fiche->projet,
            $this->fiche->business_unit,
            $tasks,
            '',
        ];
    }
}
