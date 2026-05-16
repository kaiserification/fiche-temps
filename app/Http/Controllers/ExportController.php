<?php

namespace App\Http\Controllers;

use App\Exports\FicheExport;
use App\Models\Fiche;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function export(Request $req, Fiche $fiche)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $user     = $req->user();
        $name     = $user->name;
        $start    = $fiche->period_start->locale('fr')->isoFormat('D MMMM');
        $end      = $fiche->period_end->locale('fr')->isoFormat('D MMMM');
        $filename = "Fiche de temps de {$name} - ({$start} - {$end}).xlsx";

        return Excel::download(new FicheExport($fiche, $user), $filename);
    }

    public function exportPdf(Request $req, Fiche $fiche)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $user    = $req->user();
        $entries = $fiche->dayEntries()
            ->orderBy('day')
            ->get()
            ->filter(fn($e) => !empty($e->tasks) || !empty($e->comment))
            ->values();

        $name     = $user->name;
        $start    = $fiche->period_start->locale('fr')->isoFormat('D MMMM');
        $end      = $fiche->period_end->locale('fr')->isoFormat('D MMMM');
        $filename = "Fiche de temps de {$name} - ({$start} - {$end}).pdf";

        $pdf = Pdf::loadView('exports.fiche-pdf', compact('fiche', 'user', 'entries'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
