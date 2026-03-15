<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestOrder extends Command
{
    protected $signature = 'test:order
                            {--count=1 : Nombre de commandes à créer}
                            {--status=paid : Statut des commandes (pending, paid, shipped, delivered, cancelled)}
                            {--user= : ID utilisateur spécifique (defaut: premier utilisateur)}';

    protected $description = 'Créer des commandes de test pour le développement';

    public function handle()
    {
        $count = (int) $this->option('count');
        $status = $this->option('status');
        $userId = $this->option('user');

        // Valider le statut
        $validStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled', 'en_attente', 'paye', 'expediee', 'livree', 'annulee'];
        if (!in_array($status, $validStatuses)) {
            $this->error("Statut invalide. Utilisez: " . implode(', ', $validStatuses));
            return 1;
        }

        // Normaliser le statut
        $statusMap = [
            'pending' => 'en_attente',
            'paid' => 'en_attente',
            'shipped' => 'en_preparation',
            'ready' => 'prete',
            'delivered' => 'livree',
            'cancelled' => 'annulee',
        ];
        $dbStatus = $statusMap[$status] ?? $status;

        // Récupérer l'utilisateur
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Utilisateur ID $userId non trouvé");
                return 1;
            }
        } else {
            $user = User::first();
            if (!$user) {
                $this->error("Aucun utilisateur trouvé. Créez un utilisateur d'abord.");
                return 1;
            }
        }

        // Récupérer des vinyles disponibles
        $vinyles = Vinyle::where('quantite', '>', 0)->take(3)->get();
        if ($vinyles->isEmpty()) {
            $this->error("Aucun vinyle en stock trouvé. Ajoutez des vinyles d'abord.");
            return 1;
        }

        $this->info("Création de $count commande(s) avec statut: $status (=$dbStatus)");
        $this->info("Utilisateur: {$user->name} ({$user->email})");

        DB::beginTransaction();

        try {
            for ($i = 1; $i <= $count; $i++) {
                // Générer un numéro unique basé sur timestamp + index
                $orderNumber = 'CMD-' . date('Y') . '-' . str_pad((Order::max('id') ?? 0) + $i, 4, '0', STR_PAD_LEFT);
                
                // Sélectionner aléatoirement 1-2 vinyles
                $selectedVinyles = $vinyles->random(min(2, $vinyles->count()));
                if (!is_iterable($selectedVinyles)) {
                    $selectedVinyles = [$selectedVinyles];
                }

                $total = 0;
                $items = [];

                foreach ($selectedVinyles as $vinyle) {
                    $quantity = rand(1, 2);
                    $itemTotal = $vinyle->prix * $quantity;
                    $total += $itemTotal;
                    $items[] = [
                        'vinyle' => $vinyle,
                        'quantity' => $quantity,
                        'prix' => $vinyle->prix,
                    ];
                }

                // Créer la commande
                $order = Order::create([
                    'numero_commande' => $orderNumber,
                    'user_id' => $user->id,
                    'statut' => $dbStatus,
                    'total' => $total,
                    'nom' => $user->name,
                    'prenom' => $user->name,
                    'email' => $user->email,
                    'telephone' => '0612345678',
                    'adresse' => '123 Rue de Test',
                    'code_postal' => '75000',
                    'ville' => 'Paris',
                    'shipping_nom' => $user->name,
                    'shipping_prenom' => $user->name,
                    'shipping_email' => $user->email,
                    'shipping_telephone' => '0612345678',
                    'shipping_adresse' => '123 Rue de Test',
                    'shipping_code_postal' => '75000',
                    'shipping_ville' => 'Paris',
                    'shipping_pays' => 'FR',
                    'billing_nom' => $user->name,
                    'billing_prenom' => $user->name,
                    'billing_email' => $user->email,
                    'billing_telephone' => '0612345678',
                    'billing_adresse' => '123 Rue de Test',
                    'billing_code_postal' => '75000',
                    'billing_ville' => 'Paris',
                    'billing_pays' => 'FR',
                ]);

                // Créer les items
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'vinyle_id' => $item['vinyle']->id,
                        'titre_vinyle' => $item['vinyle']->nom,
                        'artiste_vinyle' => $item['vinyle']->nom,
                        'reference_vinyle' => $item['vinyle']->modele ?: $item['vinyle']->nom,
                        'quantite' => $item['quantity'],
                        'prix_unitaire' => $item['prix'],
                        'total' => $item['prix'] * $item['quantity'],
                    ]);
                }

                $this->info("  ✓ Commande créée: $orderNumber - Total: €" . number_format($total, 2));
            }

            DB::commit();
            $this->newLine();
            $this->info("✅ $count commande(s) créée(s) avec succès !");
            
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erreur lors de la création: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
