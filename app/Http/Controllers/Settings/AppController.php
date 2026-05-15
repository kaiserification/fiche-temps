<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\AppSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Application', [
            'settings' => [
                'sites_dir' => AppSettings::get('sites_dir', realpath(dirname(base_path()))),
            ],
            'user' => [
                'name'      => $request->user()->name,
                'matricule' => $request->user()->matricule ?? '',
                'profil'    => $request->user()->profil ?? '',
                'signature' => $request->user()->signature ?? '',
            ],
        ]);
    }

    public function updateUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'matricule' => ['nullable', 'string', 'max:50'],
            'profil'    => ['nullable', 'string', 'max:100'],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'user-saved');
    }

    public function listDirectories(Request $request): JsonResponse
    {
        $path = $request->input('path', realpath(dirname(base_path())));
        $path = realpath($path);

        if (! $path || ! is_dir($path)) {
            return response()->json(['current' => null, 'parent' => null, 'dirs' => []]);
        }

        $parent = dirname($path);

        $dirs = collect(scandir($path))
            ->filter(fn ($item) => $item !== '.' && $item !== '..' && is_dir($path . DIRECTORY_SEPARATOR . $item))
            ->map(fn ($item) => ['name' => $item, 'path' => $path . DIRECTORY_SEPARATOR . $item])
            ->values();

        return response()->json([
            'current' => $path,
            'parent'  => $parent !== $path ? $parent : null,
            'dirs'    => $dirs,
        ]);
    }

    public function updateSignature(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'signature' => ['nullable', 'string'],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'signature-saved');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sites_dir' => ['required', 'string', 'max:500'],
        ]);

        if (! is_dir($validated['sites_dir'])) {
            return back()->withErrors(['sites_dir' => 'Ce répertoire n\'existe pas.']);
        }

        AppSettings::set('sites_dir', realpath($validated['sites_dir']));

        return back()->with('status', 'app-settings-saved');
    }
}
