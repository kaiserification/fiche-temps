<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class GitCommitController extends Controller
{
    private function sitesDir(): string
    {
        return realpath(dirname(base_path()));
    }

    public function projects()
    {
        $sitesDir = $this->sitesDir();
        $projects = [];

        foreach (scandir($sitesDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full = $sitesDir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($full) && is_dir($full . DIRECTORY_SEPARATOR . '.git')) {
                $projects[] = $entry;
            }
        }

        sort($projects);

        return response()->json(['projects' => $projects]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'date'    => 'required|date_format:Y-m-d',
            'project' => 'required|string|max:100',
        ]);

        $date  = $request->input('date');
        $since = $date . ' 00:00:00';
        $until = $date . ' 23:59:59';

        $sitesDir    = $this->sitesDir();
        $projectPath = realpath($sitesDir . DIRECTORY_SEPARATOR . $request->input('project'));

        if (! $projectPath
            || ! str_starts_with($projectPath, $sitesDir)
            || ! is_dir($projectPath . DIRECTORY_SEPARATOR . '.git')
        ) {
            return response()->json(['error' => 'Projet invalide.'], 422);
        }

        $result = Process::path($projectPath)->run([
            'git', 'log',
            '--since=' . $since,
            '--until=' . $until,
            '--pretty=format:%s',
        ]);

        if (! $result->successful()) {
            return response()->json(['error' => 'Impossible de lire les commits git.'], 500);
        }

        $commits = trim($result->output());

        if (empty($commits)) {
            return response()->json(['empty' => true, 'tasks' => null]);
        }

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 1024,
            'system'     => implode("\n", [
                'Tu es un assistant qui aide les développeurs à remplir leur fiche de temps.',
                'Tu reçois des messages de commits Git et tu les transformes en tâches claires,',
                'compréhensibles par un manager non-technique.',
                'Réponds uniquement avec une liste ordonnée. Chaque tâche doit :',
                '- Être formulée en français, de manière professionnelle',
                '- Commencer par un verbe d\'action',
                '- Être compréhensible sans connaissance technique',
                '- Être concise (max 1 phrase)',
                'Ajoute à la fin un résumé d\'une ligne de la journée.',
            ]),
            'messages' => [
                ['role' => 'user', 'content' => $commits],
            ],
        ]);

        if (! $response->successful()) {
            return response()->json([
                'error' => 'Erreur lors de la génération avec l\'IA (' . $response->status() . ').',
            ], 500);
        }

        return response()->json(['tasks' => $response->json('content.0.text')]);
    }
}
