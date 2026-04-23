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

        $filename = "fiche_{$fiche->matricule}_{$fiche->period_start->format('Y_m')}.xlsx";

        return Excel::download(new FicheExport($fiche), $filename);
    }
}
