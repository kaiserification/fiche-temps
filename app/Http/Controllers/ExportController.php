<?php

namespace App\Http\Controllers;

use App\Exports\FicheExport;
use App\Models\Fiche;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function export(Request $req, Fiche $fiche)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $name  = $req->user()->name;
        $start = $fiche->period_start->locale('fr')->isoFormat('D MMMM');
        $end   = $fiche->period_end->locale('fr')->isoFormat('D MMMM');
        $filename = "Fiche de temps de {$name} - ({$start} - {$end}).xlsx";

        return Excel::download(new FicheExport($fiche), $filename);
    }
}
