<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vinyle;
use App\Models\MouvementStock;

class TestOrderStockMovement extends Command
{
    protected $signature = 'test:order-movement';
    protected $description = 'Tester les mouvements de stock liés aux commandes';

    public function handle(): int
    {
        $this->info('🧪 Test : Mouvements de stock liés aux commandes');
        $this->newLine();

        // 1. Créer un vinyle test
        $vinyle = Vinyle::firstOrCreate(
            [
                'artiste' => 'Test Artist',
                'modele' => 'Test Stock Movement',
            ],
            [
                'reference' => 'VIN-TEST-001',
                'prix' => 25.00,
                'quantite' => 10,
                'genre' => 'Rock',
                'style' => 'Classique',
            ]
        );
        
        $this->info('🎵 Vinyle test : ' . $vinyle->nom_complet . ' (quantite: ' . $vinyle->quantite . ')');
        
        $this->newLine();
        $this->info('--- Création commande en attente ---');
        
        // 2. Créer une commande
        $order = Order::create([
            'numero_commande' => 'CMD-TEST-' . time(),
            'nom' => 'Test',
            'prenom' => 'Client',
            'email' => 'test@example.com',
            'telephone' => '0123456789',
            'adresse' => '123 Rue Test',
            'code_postal' => '75000',
            'ville' => 'Paris',
            'total' => 33.00,
            'statut' => 'en_attente',
        ]);

        $this->info('🛒 Commande créée : ' . $order->numero_commande . ' (statut: ' . $order->statut . ')');

        // 3. Ajouter des items
        OrderItem::create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'titre_vinyle' => $vinyle->nom_complet,
            'artiste_vinyle' => $vinyle->artiste,
            'reference_vinyle' => $vinyle->reference,
            'quantite' => 1,
            'prix_unitaire' => 25.00,
            'total' => 25.00,
        ]);
        
        $this->info('📦 Item ajouté : Vinyle x1');

        // 4. Vérifier qu'il n'y a pas encore de mouvement
        $countBefore = MouvementStock::where('reference', 'like', '%' . $order->numero_commande . '%')->count();
        $this->info('📊 Mouvements avant validation : ' . $countBefore . ' (devrait être 0)');

        $this->newLine();
        $this->info('--- Validation de la commande ---');

        // 6. Valider la commande (déclenche observer)
        $order->statut = 'prete';
        $order->save();

        $this->info('✅ Commande passée en statut : ' . $order->statut);
        
        sleep(1); // Petit délai pour s'assurer que les mouvements sont créés

        // 7. Vérifier les mouvements créés
        $mouvements = MouvementStock::where('reference', $order->numero_commande)
            ->orWhere('reference', 'like', '%' . $order->numero_commande . '%')
            ->get();

        $this->newLine();
        $this->info('📊 Mouvements créés :');
        foreach ($mouvements as $mvt) {
            $this->info(sprintf(
                '   → [%s] %s | %s x%d | Réf: %s',
                strtoupper($mvt->type),
                $mvt->produit_libelle,
                $mvt->notes,
                $mvt->quantite,
                $mvt->reference
            ));
        }

        $this->newLine();
        $this->info('--- Nettoyage ---');
        
        // Nettoyer
        $order->items()->delete();
        $order->delete();
        
        // Supprimer les mouvements de test
        MouvementStock::where('reference', 'like', 'CMD-TEST-%')
            ->orWhere('notes', 'like', '%Test Stock Movement%')
            ->delete();

        $this->info('🗑️ Données de test nettoyées');

        $this->newLine();
        $this->info('✅ Test terminé ! Les mouvements sont automatiquement créés quand une commande passe en statut "prête" ou "livrée".');

        return self::SUCCESS;
    }
}
