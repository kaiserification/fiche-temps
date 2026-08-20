<?php

namespace App\Http\Controllers;

use App\Models\{Fiche, DayEntry};
use Carbon\Carbon;
use Illuminate\Http\Request;

class DayEntryController extends Controller
{
    public function store(Request $req, Fiche $fiche)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $req->validate(['day' => 'required|date', 'tasks' => 'array', 'comment' => 'nullable|string', 'projet' => 'nullable|string|max:255']);

        $entry = $fiche->dayEntries()->updateOrCreate(
            ['day' => Carbon::parse($req->day)->toDateString()],
            ['tasks' => $req->tasks ?? [], 'comment' => $req->comment, 'projet' => $req->projet]
        );

        return response()->json(['entry' => $entry, 'tasks' => $entry->tasks]);
    }

    public function update(Request $req, Fiche $fiche, DayEntry $entry)
    {
        abort_if($fiche->user_id !== $req->user()->id, 403);

        $req->validate(['tasks' => 'nullable|array', 'comment' => 'nullable|string', 'projet' => 'nullable|string|max:255']);
        $entry->update(['tasks' => $req->tasks, 'comment' => $req->comment, 'projet' => $req->projet]);

        return response()->json(['entry' => $entry]);
    }
}
