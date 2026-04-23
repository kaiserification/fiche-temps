<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\User;
use App\Services\FicheDeTempsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FicheController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Fiche/Index', [
            'fiches' => $request->user()->fiches()->withCount('dayEntries')->latest()->get(),
        ]);
    }

    public function create(Request $req, FicheDeTempsService $svc)
    {
        [$start, $end] = $svc->getPeriod();
        $user = $req->user();

        return Inertia::render('Fiche/Builder', [
            'periodStart' => $start->toDateString(),
            'periodEnd'   => $end->toDateString(),
            'authorName'  => $user->name,
            'matricule'   => $user->matricule,
            'profil'      => $user->profil,
            'fiche'       => null,
        ]);
    }

    public function store(Request $req, FicheDeTempsService $svc)
    {
        $req->validate([
            'projet'        => 'required|string|max:255',
            'business_unit' => 'nullable|string|max:255',
            'period_start'  => 'nullable|date',
            'period_end'    => 'nullable|date|after_or_equal:period_start',
        ]);

        if ($req->filled('period_start') && $req->filled('period_end')) {
            $start = \Carbon\Carbon::parse($req->period_start);
            $end   = \Carbon\Carbon::parse($req->period_end);
        } else {
            [$start, $end] = $svc->getPeriod();
        }

        $fiche = $req->user()->fiches()->create([
            'matricule'     => $req->user()->matricule,
            'period_start'  => $start,
            'period_end'    => $end,
            'projet'        => $req->projet,
            'business_unit' => $req->business_unit,
        ]);

        return redirect()->route('fiche.show', $fiche);
    }

    public function update(Request $req, Fiche $fiche)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $req->validate([
            'projet'        => 'required|string|max:255',
            'business_unit' => 'nullable|string|max:255',
            'period_start'  => 'required|date',
            'period_end'    => 'required|date|after_or_equal:period_start',
        ]);

        $fiche->update([
            'projet'        => $req->projet,
            'business_unit' => $req->business_unit,
            'period_start'  => $req->period_start,
            'period_end'    => $req->period_end,
        ]);

        return response()->json(['fiche' => $fiche]);
    }

    public function destroy(Request $req, Fiche $fiche)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $fiche->delete();

        return redirect()->route('fiche.index');
    }

    public function show(Request $req, Fiche $fiche)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $user = $req->user();

        return Inertia::render('Fiche/Builder', [
            'periodStart' => $fiche->period_start->toDateString(),
            'periodEnd'   => $fiche->period_end->toDateString(),
            'authorName'  => $user->name,
            'matricule'   => $user->matricule,
            'profil'      => $user->profil,
            'fiche'       => $fiche->load('dayEntries'),
        ]);
    }
}
