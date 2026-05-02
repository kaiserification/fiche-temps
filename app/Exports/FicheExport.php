<?php

namespace App\Exports;

use App\Models\Fiche;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FicheExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            ->map(fn($t, $i) => ($i + 1) . '. ' . $t)
            ->implode("\n");

        return [
            Carbon::parse($entry->day)->format('d/m/Y'),
            $this->fiche->projet,
            $this->fiche->business_unit,
            $tasks,
            '',
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestRow();

        // Header: bold + light indigo background
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE8EAF6'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Data rows: wrap text + align top so multi-line tasks read naturally
        $sheet->getStyle('A2:E' . $lastRow)->applyFromArray([
            'alignment' => [
                'wrapText' => true,
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ]);

        // Date column: center horizontally
        $sheet->getStyle('A2:A' . $lastRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }
}
