<?php

namespace App\Http\Controllers;

use App\Support\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class GitCommitController extends Controller
{
    private function sitesDir(): string
    {
        return realpath(AppSettings::get('sites_dir', env('SITES_DIR', dirname(base_path()))));
    }

    public function projects()
    {
        $sitesDir = $this->sitesDir();
        $projects = [];

        foreach (scandir($sitesDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

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

        if (
            ! $projectPath
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

        return $this->buildTasksResponse($result->output());
    }

    public function formatFromCommits(Request $request)
    {
        $request->validate([
            'date'    => 'required|date_format:Y-m-d',
            'commits' => 'required|string|max:20000',
        ]);

        return $this->buildTasksResponse($request->input('commits'));
    }

    private function buildTasksResponse(string $rawCommitLog)
    {
        $commits = $this->filterCommits($rawCommitLog);

        if (empty($commits)) {
            return response()->json(['empty' => true, 'tasks' => null]);
        }

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 2048,
            'system'     => $this->systemPrompt(),
            'messages'   => [
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

    private function filterCommits(string $output): string
    {
        $lines = array_filter(
            explode("\n", trim($output)),
            fn(string $line) => ! preg_match(
                '/^(Merge (branch|pull request)|fix pint|fix lint|fix job lint|delete unuse|wip\b)/i',
                trim($line)
            ) && trim($line) !== ''
        );

        return trim(implode("\n", $lines));
    }

    private function systemPrompt(): string
    {
        return implode("\n", [
            'Tu es un assistant spécialisé dans la rédaction de fiches de temps pour des développeurs fullstack.',
            'Tu reçois une liste de messages de commits Git d\'une journée et tu les transformes en tâches',
            'professionnelles pour une fiche de temps officielle, lisible par un manager ou un auditeur non-technique.',
            '',
            'RÈGLES DE RÉDACTION :',
            '- Rédige chaque tâche en français professionnel, à l\'infinitif (Développer, Corriger, Ajouter, Améliorer...)',
            '- Chaque tâche doit décrire l\'impact métier ou fonctionnel, pas l\'action technique interne',
            '- Maximum 1 phrase par tâche, concise et claire',
            '- Ne mentionne jamais de noms de fichiers, de classes, de fonctions ou de variables',
            '',
            'REGROUPEMENT :',
            '- Regroupe les commits qui traitent du même sujet fonctionnel en une seule tâche',
            '- Plusieurs commits de correction sur le même bug = 1 seule tâche',
            '- Ignore les commits de fusion de branches, de formatage automatique ou d\'ajustement mineur de style',
            '',
            'TRADUCTION DES TERMES TECHNIQUES - remplace systématiquement :',
            '- "MFA", "OTP", "2FA"            → "authentification à deux facteurs"',
            '- "CORS"                          → "sécurité inter-domaines" ou "communication entre domaines"',
            '- "API", "endpoint"               → "service web" ou "point de connexion"',
            '- "pipeline CI/CD", "pipeline"    → "système de déploiement automatique"',
            '- "branch" (git)                  → "environnement" ou ne pas mentionner',
            '- "migration" (base de données)   → décris l\'effet métier (ex: "mettre à jour les données existantes")',
            '- "enum", "énumération"           → "référentiel de valeurs standardisées"',
            '- "log", "logs"                   → "journaux" ou "journaux d\'activité"',
            '- "refactoring", "refactoriser"   → "restructurer" ou "améliorer l\'organisation du code"',
            '- "mapping", "mappé"              → "correspondance" ou "association"',
            '- "hardcodé", "codé en dur"       → "valeur fixe non configurable"',
            '- "async", "asynchrone"           → "en arrière-plan" ou "sans bloquer l\'interface"',
            '- LDAP                            → "serveur d\'annuaire d\'entreprise" (sauf si déjà connu du lecteur)',
            '- CBS                             → garder tel quel (système métier connu)',
            '- Tout autre terme anglais non traduit est interdit',
            '',
            'FORMAT DE SORTIE :',
            '- Liste numérotée uniquement, sans titre, sans introduction, sans commentaire',
            '- Une tâche par ligne, numérotée à partir de 1',
            '- Après la liste, saute une ligne puis écris : "Résumé : [résumé de la journée en une phrase]"',
        ]);
    }
}
