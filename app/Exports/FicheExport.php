<?php

namespace App\Exports;

use App\Models\Fiche;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FicheExport implements WithEvents
{
    public function __construct(
        private Fiche $fiche,
        private User $user
    ) {}

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->buildHeader($sheet);
                $this->buildData($sheet);
                $this->applyColumnWidths($sheet);
            },
        ];
    }

    private function buildHeader(Worksheet $sheet): void
    {
        // Row 1: Title
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Fiche de temps');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Rows 2-4: User info
        $infoRows = [
            2 => ['label' => 'Prénom(s) & Nom', 'value' => $this->user->name],
            3 => ['label' => 'Matricule',        'value' => $this->user->matricule ?? $this->fiche->matricule ?? ''],
            4 => ['label' => 'Profil',           'value' => $this->user->profil ?? ''],
        ];

        foreach ($infoRows as $row => $info) {
            $sheet->setCellValue("A{$row}", $info['label']);
            $sheet->mergeCells("B{$row}:E{$row}");
            $sheet->setCellValue("B{$row}", $info['value']);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font'      => ['size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("B{$row}:E{$row}")->applyFromArray([
                'font'      => ['size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Row 5: Période
        $sheet->setCellValue('A5', 'Période');
        $sheet->setCellValue('B5', 'DU');
        $sheet->setCellValue('C5', $this->fiche->period_start->locale('fr')->isoFormat('D MMMM YYYY'));
        $sheet->setCellValue('D5', 'AU');
        $sheet->setCellValue('E5', $this->fiche->period_end->locale('fr')->isoFormat('D MMMM YYYY'));
        $sheet->getStyle('A5')->applyFromArray([
            'font'      => ['size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('B5:E5')->applyFromArray([
            'font'      => ['size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Row 6: empty separator

        // Row 7: Column headers
        foreach (['A' => 'Date', 'B' => 'Projet', 'C' => 'Business Unit', 'D' => 'Liste des tâches', 'E' => 'Commentaire'] as $col => $label) {
            $sheet->setCellValue("{$col}7", $label);
        }
        $sheet->getStyle('A7:E7')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF123274'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(22);
    }

    private function buildData(Worksheet $sheet): void
    {
        $entries = $this->fiche->dayEntries()
            ->orderBy('day')
            ->get()
            ->filter(fn($e) => !empty($e->tasks));

        $currentRow = 8;

        foreach ($entries as $entry) {
            $tasks     = array_values((array) $entry->tasks);
            $taskCount = count($tasks);
            if ($taskCount === 0) continue;

            $startRow = $currentRow;
            $endRow   = $currentRow + $taskCount - 1;

            if ($taskCount > 1) {
                $sheet->mergeCells("A{$startRow}:A{$endRow}");
                $sheet->mergeCells("B{$startRow}:B{$endRow}");
                $sheet->mergeCells("C{$startRow}:C{$endRow}");
                $sheet->mergeCells("E{$startRow}:E{$endRow}");
            }

            $sheet->setCellValue("A{$startRow}", Carbon::parse($entry->day)->format('d/m/Y'));
            $sheet->setCellValue("B{$startRow}", $this->fiche->projet);
            $sheet->setCellValue("C{$startRow}", $this->fiche->business_unit);
            $sheet->setCellValue("E{$startRow}", $entry->comment ?? '');

            // Outline border on merged columns — allBorders dessinerait des traits À TRAVERS la fusion
            $outline = ['borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN]]];
            $sheet->getStyle("A{$startRow}:A{$endRow}")->applyFromArray(array_merge($outline, [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]));
            foreach (['B', 'C'] as $col) {
                $sheet->getStyle("{$col}{$startRow}:{$col}{$endRow}")->applyFromArray(array_merge($outline, [
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]));
            }
            $sheet->getStyle("E{$startRow}:E{$endRow}")->applyFromArray(array_merge($outline, [
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
            ]));

            // Une ligne par tâche : bordure individuelle + wrapText uniquement sur D
            foreach ($tasks as $i => $task) {
                $taskRow = $currentRow + $i;
                $sheet->setCellValue("D{$taskRow}", ($i + 1) . '. ' . $task);
                $sheet->getStyle("D{$taskRow}")->applyFromArray([
                    'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
            }

            $currentRow = $endRow + 1;
        }
    }

    private function applyColumnWidths(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(50);
        $sheet->getColumnDimension('E')->setWidth(25);
    }
}
