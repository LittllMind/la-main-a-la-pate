<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Vinyle;
use App\Models\Order;
use App\Models\Address;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTestOrder extends Command
{
    protected $signature = 'test:order
                            {--count=1 : Nombre de commandes à créer}
                            {--status=paid : Statut (pending, paid, processing, shipped, delivered, cancelled)}
                            {--user= : ID utilisateur spécifique}';

    protected $description = 'Crée des commandes de test avec statut configurable';

    public function handle()
    {
        $count = $this->option('count');
        $status = $this->option('status');
        $userId = $this->option('user');

        $this->info("Création de {$count} commande(s) en statut '{$status}'...");

        // Récupérer ou créer un utilisateur
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Utilisateur #{$userId} non trouvé");
                return 1;
            }
        } else {
            $user = User::first();
            if (!$user) {
                $this->error("Aucun utilisateur trouvé. Créez-en un d'abord.");
                return 1;
            }
        }

        // Récupérer ou créer une adresse
        $address = $user->addresses()->first();
        if (!$address) {
            $address = Address::create([
                'user_id' => $user->id,
                'type' => 'shipping',
                'label' => 'Adresse Test',
                'first_name' => $user->first_name ?? 'Test',
                'last_name' => $user->last_name ?? 'User',
                'address_line_1' => '123 Rue de Test',
                'city' => 'Paris',
                'postal_code' => '75000',
                'country' => 'FR',
                'is_default' => true,
            ]);
        }

        // Récupérer des vinyles disponibles
        $vinyles = Vinyle::inStock()->take(3)->get();
        if ($vinyles->isEmpty()) {
            $this->error("Aucun vinyle en stock pour créer des commandes");
            return 1;
        }

        $created = 0;
        $amounts = [35.00, 45.00, 55.00, 65.00, 75.00, 85.00, 95.00, 105.00, 125.00, 150.00];

        for ($i = 0; $i < $count; $i++) {
            // Créer la commande
            $orderNumber = 'ORD-' . strtoupper(Str::random(8));
            $amount = $amounts[array_rand($amounts)];
            $shippingCost = $amount >= 100 ? 0 : 8.50;
            $totalAmount = $amount + $shippingCost;

            $orderData = [
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'status' => $status,
                'payment_status' => in_array($status, ['paid', 'processing', 'shipped', 'delivered']) ? 'paid' : 'pending',
                'shipping_status' => $status === 'delivered' ? 'delivered' : ($status === 'shipped' ? 'shipped' : 'pending'),
                'subtotal' => $amount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'shipping_address_id' => $address->id,
                'billing_address_id' => $address->id,
                'created_at' => now()->subDays(rand(1, 30)),
            ];

            // Ajouter dates selon le statut
            if (in_array($status, ['paid', 'processing', 'shipped', 'delivered'])) {
                $orderData['paid_at'] = now()->subDays(rand(1, 20));
            }
            if (in_array($status, ['shipped', 'delivered'])) {
                $orderData['shipped_at'] = now()->subDays(rand(0, 10));
            }
            if ($status === 'delivered') {
                $orderData['delivered_at'] = now()->subDays(rand(0, 5));
            }

            $order = Order::create($orderData);

            // Ajouter des lignes
            $vinyle = $vinyles->random();
            $order->items()->create([
                'vinyle_id' => $vinyle->id,
                'quantity' => 1,
                'unit_price' => $amount,
                'total' => $amount,
            ]);

            $created++;
            $this->info("✓ Commande {$orderNumber} créée ({$amount}€)");
        }

        $this->info("\n✅ {$created} commande(s) créée(s) avec succès !");
        $this->table(
            ['Détail', 'Valeur'],
            [
                ['Utilisateur', $user->email],
                ['Statut', $status],
                ['Total commandes', $created],
            ]
        );

        return 0;
    }
}