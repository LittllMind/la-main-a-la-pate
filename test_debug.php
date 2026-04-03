<?php
// Test simplifié pour voir l'erreur exacte
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Forcer l'environnement de test
putenv('APP_ENV=testing');

// Créer un vinyle et tenter un update
use App\Models\Vinyle;
use App\Models\User;

// Reset database (simulation facile)
try {
    // Test simple: créer un vinyle
    \Illuminate\Support\Facades\DB::table('vinyles')->delete();
    
    $vinyle = new Vinyle([
        'reference' => 'TEST-001',
        'nom' => 'Test Album',
        'artiste' => 'Test Artist',
        'modele' => 'Standard',
        'genre' => 'Rock',
        'style' => 'LP',
        'prix' => 25.00,
        'quantite' => 5,
    ]);
    $vinyle->save();
    
    echo "Vinyle créé: ID={$vinyle->id}\n";
    
    // Simuler un update comme le contrôleur
    $vinyle->update([
        'nom' => 'New Name',
        'artiste' => $vinyle->artiste,
        'modele' => $vinyle->modele,
        'prix' => 35.00,
        'quantite' => 10,
    ]);
    
    echo "Update réussi!\n";
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
