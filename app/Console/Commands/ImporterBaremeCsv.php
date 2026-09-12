<?php

namespace App\Console\Commands;

use App\Models\BaremeVersion;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Importe une nouvelle version du barème depuis un fichier CSV
 * (colonnes : densite_min,densite_max,carat[,note]).
 *
 * Sert à peupler rapidement une version brouillon (ex. transcription d'un
 * barème papier) que l'utilisateur pourra ensuite corriger/compléter en
 * créant une nouvelle version via l'application — jamais en réécrivant
 * celle-ci, conformément au cahier des charges §5.
 */
class ImporterBaremeCsv extends Command
{
    protected $signature = 'bareme:importer-csv
        {chemin : Chemin du fichier CSV (densite_min,densite_max,carat,note)}
        {--libelle= : Libellé de la version créée}
        {--utilisateur= : Téléphone de l\'utilisateur à associer comme auteur}';

    protected $description = "Importe une nouvelle version du barème (densité → carat) depuis un fichier CSV";

    public function handle(): int
    {
        $chemin = $this->argument('chemin');

        if (! is_readable($chemin)) {
            $this->error("Fichier introuvable ou illisible : {$chemin}");

            return self::FAILURE;
        }

        $lignes = [];
        $numeroLigne = 0;

        foreach (file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligneCsv) {
            $numeroLigne++;
            $champs = str_getcsv($ligneCsv);

            if ($numeroLigne === 1 && ! is_numeric($champs[0])) {
                continue; // en-tête
            }

            [$densiteMin, $densiteMax, $carat] = [$champs[0], $champs[1], $champs[2]];

            if (! is_numeric($densiteMin) || ! is_numeric($densiteMax) || ! is_numeric($carat)) {
                $this->warn("Ligne {$numeroLigne} ignorée (valeurs non numériques) : {$ligneCsv}");

                continue;
            }

            $lignes[] = ['densite_min' => $densiteMin, 'densite_max' => $densiteMax, 'carat' => $carat];
        }

        if (empty($lignes)) {
            $this->error('Aucune ligne exploitable trouvée dans le fichier.');

            return self::FAILURE;
        }

        $userId = null;
        if ($tel = $this->option('utilisateur')) {
            $userId = User::where('phone', $tel)->value('id');
        }

        $libelle = $this->option('libelle') ?: 'Barème importé le '.now()->format('d/m/Y à H:i');

        $version = BaremeVersion::creerNouvelleVersion($libelle, $lignes, $userId);

        $this->info("Version « {$version->libelle} » créée et activée avec ".count($lignes).' ligne(s).');

        return self::SUCCESS;
    }
}
