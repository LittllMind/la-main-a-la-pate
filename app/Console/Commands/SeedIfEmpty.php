<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SeedIfEmpty extends Command
{
    protected $signature = 'db:seed-if-empty 
                            {--class=DatabaseSeeder : Classe seeder à utiliser}
                            {--force : Forcer l\'exécution sans confirmation}';
    
    protected $description = 'Seed la BDD uniquement si elle est vide (tables users, vinyles, fonds, bougies)';

    public function handle(): int
    {
        $this->info('🔍 Vérification du contenu de la base de données...');

        // Tables à vérifier (tables principales métier)
        $tablesToCheck = [
            'users' => 'utilisateurs',
            'vinyles' => 'vinyles', 
            'fonds' => 'fonds',
            'bougies' => 'bougies',
        ];

        $totalRecords = 0;
        $tableCounts = [];

        foreach ($tablesToCheck as $table => $label) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $count = DB::table($table)->count();
                $tableCounts[$label] = $count;
                $totalRecords += $count;
                $this->line("  → {$label}: {$count} enregistrements");
            } else {
                $this->warn("  ⚠ Table {$label} inexistante (migrations pas faites ?)");
                return self::FAILURE;
            }
        }

        $this->newLine();

        // Si BDD vide ou quasi-vide (< 5 records total)
        if ($totalRecords === 0) {
            $this->info('📭 Base de données VIDE détectée');
            
            if (!$this->option('force') && !$this->confirm('Voulez-vous lancer le seed ?')) {
                $this->warn('❌ Seed annulé par l\'utilisateur');
                return self::SUCCESS;
            }

            $seederClass = $this->option('class');
            $this->info("🌱 Lancement du seed : {$seederClass}...");
            
            Artisan::call('db:seed', [
                '--class' => $seederClass,
                '--force' => true,
            ], $this->output);

            $this->newLine();
            $this->info('✅ Seed terminé avec succès !');
            
            // Afficher les nouveaux counts
            $this->info('📊 Nouveaux enregistrements :');
            foreach ($tablesToCheck as $table => $label) {
                $count = DB::table($table)->count();
                $this->line("  → {$label}: {$count} enregistrements");
            }
            
            return self::SUCCESS;
            
        } else {
            $this->info('✅ Base de données déjà peuplée (' . $totalRecords . ' records)');
            $this->info('⏭️  Seed ignoré - données préservées');
            
            return self::SUCCESS;
        }
    }
}
