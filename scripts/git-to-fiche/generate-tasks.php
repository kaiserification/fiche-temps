<?php

/**
 * Script local : lit les commits Git d'un projet pour une date donnée et
 * envoie uniquement les messages de commit (aucun chemin de fichier) à
 * l'API centrale de fiche-temps pour obtenir des tâches formatées.
 *
 * Usage:
 *   php generate-tasks.php --project=nom-du-projet --date=YYYY-MM-DD
 */

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

if (! is_file($configPath)) {
    fwrite(STDERR, "Config introuvable : copiez config.example.php en config.php et complétez-le.\n");
    exit(1);
}

$config = require $configPath;

$options = getopt('', ['project:', 'date:']);
$project = $options['project'] ?? null;
$date    = $options['date'] ?? null;

if (! $project || ! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    fwrite(STDERR, "Usage: php generate-tasks.php --project=nom-du-projet --date=YYYY-MM-DD\n");
    exit(1);
}

$sitesDir = realpath($config['sites_dir'] ?? '');
if (! $sitesDir) {
    fwrite(STDERR, "sites_dir invalide dans config.php.\n");
    exit(1);
}

$projectPath = realpath($sitesDir . DIRECTORY_SEPARATOR . $project);
if (
    ! $projectPath
    || ! str_starts_with($projectPath, $sitesDir)
    || ! is_dir($projectPath . DIRECTORY_SEPARATOR . '.git')
) {
    fwrite(STDERR, "Projet introuvable ou n'est pas un dépôt Git : {$project}\n");
    exit(1);
}

$since = $date . ' 00:00:00';
$until = $date . ' 23:59:59';

$cwd = getcwd();
chdir($projectPath);
$commits = shell_exec(sprintf(
    'git log --since=%s --until=%s --pretty=format:%%s',
    escapeshellarg($since),
    escapeshellarg($until)
));
chdir($cwd);

$commits = trim((string) $commits);

if ($commits === '') {
    fwrite(STDOUT, "Aucun commit trouvé pour {$project} le {$date}.\n");
    exit(0);
}

$apiUrl = rtrim($config['api_url'] ?? '', '/');
$token  = $config['token'] ?? '';

if (! $apiUrl || ! $token) {
    fwrite(STDERR, "api_url ou token manquant dans config.php.\n");
    exit(1);
}

$payload = json_encode(['date' => $date, 'commits' => $commits]);

$ch = curl_init($apiUrl . '/api/git-commits/format');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ],
]);

$body       = curl_exec($ch);
$curlError  = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false) {
    fwrite(STDERR, "Erreur réseau : {$curlError}\n");
    exit(1);
}

$data = json_decode($body, true);

if ($statusCode !== 200) {
    $error = $data['error'] ?? $data['message'] ?? "HTTP {$statusCode}";
    fwrite(STDERR, "Erreur API : {$error}\n");
    exit(1);
}

if (! empty($data['empty'])) {
    fwrite(STDOUT, "Aucune tâche générée (commits filtrés comme non pertinents).\n");
    exit(0);
}

$tasks = $data['tasks'] ?? '';

fwrite(STDOUT, $tasks . "\n");

// Copie best-effort dans le presse-papier (Windows uniquement, échec silencieux ailleurs)
if (stripos(PHP_OS, 'WIN') === 0) {
    $proc = @proc_open('clip', [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (is_resource($proc)) {
        fwrite($pipes[0], $tasks);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        fwrite(STDOUT, "\n(Copié dans le presse-papier — collez-le dans le panneau \"Liste\" de fiche-temps.)\n");
    }
}
