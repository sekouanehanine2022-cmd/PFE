<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

$basePath = $argv[2] ?? dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
$app = require $basePath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$destination = $argv[1] ?? '';
if (! preg_match('#^/home/[a-zA-Z0-9_-]+/deployment-backups/[a-zA-Z0-9_-]+\.sql$#D', $destination)
    || file_exists($destination) || file_exists($destination.'.part')) {
    fwrite(STDERR, "Destination de sauvegarde invalide ou deja utilisee.\n");
    exit(1);
}

$connection = config('database.default');
$config = $app['db']->connection($connection)->getConfig();
$finder = new ExecutableFinder();
$binary = $finder->find('mariadb-dump') ?? $finder->find('mysqldump');
if (($config['driver'] ?? '') !== 'mysql' || ! $binary) {
    fwrite(STDERR, "Sauvegarde MySQL indisponible : deploiement annule.\n");
    exit(1);
}

umask(0077);
$file = fopen($destination.'.part', 'xb');
if (! $file) {
    exit(1);
}

try {
    // Le mot de passe n'apparait ni dans la commande ni dans les journaux GitHub.
    $process = new Process([
        $binary, '--single-transaction', '--quick', '--skip-lock-tables', '--hex-blob',
        '--host='.$config['host'], '--port='.($config['port'] ?? 3306),
        '--user='.$config['username'], '--', $config['database'],
    ], null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
    $process->setTimeout(120);
    $process->run(function (string $type, string $buffer) use ($file): void {
        if ($type === Process::OUT) {
            if (fwrite($file, $buffer) !== strlen($buffer)) {
                throw new RuntimeException('Ecriture incomplete.');
            }
        }
    });
    $size = ftell($file);
    fclose($file);
    $file = null;
    if (! $process->isSuccessful() || ! $size || ! rename($destination.'.part', $destination)) {
        throw new RuntimeException('Sauvegarde incomplete.');
    }
    echo "Sauvegarde MySQL creee hors du dossier public.\n";
} catch (Throwable $exception) {
    if (is_resource($file)) {
        fclose($file);
    }
    // Ne pas afficher les erreurs du client SQL : elles peuvent contenir des informations confidentielles.
    fwrite(STDERR, "Echec de sauvegarde MySQL : deploiement annule.\n");
    exit(1);
}
