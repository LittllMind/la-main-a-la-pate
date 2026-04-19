<?php
/**
 * Migration d'optimisation Database - Mission Abacus MAT-4
 * 
 * Objectifs:
 * 1. Créer indexes manquants sur colonnes WHERE/JOIN fréquemment utilisées
 * 2. Ajouter LIMIT/pagination aux requêtes sans pagination (code review)
 * 3. Transactions sur opérations multi-étapes (déjà implémenté dans VenteController)
 * 4. Compound indexes pour les requêtes WHERE + ORDER BY
 * 
 * Tables optimisées:
 * - ventes: index(date, created_at) pour ORDER BY desc + WHERE date
 * - ligne_ventes: index vinyle_id pour JOIN
 * - cart_items: unique existe déjà, ajout index vinyle_id
 * - users: index email (login)
 * - payments: compound index order_id + status
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== VENTES ====================
        // VenteController::index fait: WHERE date = X ORDER BY created_at desc
        // Compound index pour couvrir cette requête
        Schema::table('ventes', function (Blueprint $table) {
            $table->index(['date', 'created_at'], 'idx_ventes_date_created');
            $table->index('mode_paiement', 'idx_ventes_mode_paiement');
        });

        // ==================== LIGNE_VENTES ====================
        // JOIN fréquent sur vinyle_id dans les stats par artiste
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->index('vinyle_id', 'idx_ligne_ventes_vinyle');
            $table->index(['vente_id', 'vinyle_id'], 'idx_ligne_ventes_vente_vinyle');
        });

        // ==================== CART_ITEMS ====================
        // Recherche par vinyle_id pour vérifier si dans panier
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('vinyle_id', 'idx_cart_items_vinyle');
        });

        // ==================== USERS ====================
        // Login fréquent par email
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
        });

        // ==================== PAYMENTS ====================
        // Recherche payment par order avec status
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['order_id', 'status'], 'idx_payments_order_status');
            $table->index('status', 'idx_payments_status');
        });

        // ==================== ORDER_ITEMS ====================
        // Stats par vinyle_id
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('vinyle_id', 'idx_order_items_vinyle');
        });

        // ==================== FONDS ====================
        // Recherche par type pour stock alerts
        Schema::table('fonds', function (Blueprint $table) {
            $table->index('type', 'idx_fonds_type');
            // quantite déjà indexée implicitement via FK? Non, on ajoute
            $table->index('quantite', 'idx_fonds_quantite');
        });

        // ==================== BOUGIES (vinyles) ====================
        // Migration 2026_03_20 a créé bougies sans indexes 
        // Ajout des indexes standard pour recherche
        Schema::table('bougies', function (Blueprint $table) {
            $table->index('reference', 'idx_bougies_reference');
            $table->index('parfum', 'idx_bougies_parfum');
            $table->index('quantite', 'idx_bougies_quantite');
            $table->index(['quantite', 'seuil_alerte'], 'idx_bougies_stock_alert'); // WHERE quantite <= seuil_alerte
        });
    }

    public function down(): void
    {
        // ==================== VENTES ====================
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropIndex('idx_ventes_date_created');
            $table->dropIndex('idx_ventes_mode_paiement');
        });

        // ==================== LIGNE_VENTES ====================
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->dropIndex('idx_ligne_ventes_vinyle');
            $table->dropIndex('idx_ligne_ventes_vente_vinyle');
        });

        // ==================== CART_ITEMS ====================
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_vinyle');
        });

        // ==================== USERS ====================
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
        });

        // ==================== PAYMENTS ====================
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_order_status');
            $table->dropIndex('idx_payments_status');
        });

        // ==================== ORDER_ITEMS ====================
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_vinyle');
        });

        // ==================== FONDS ====================
        Schema::table('fonds', function (Blueprint $table) {
            $table->dropIndex('idx_fonds_type');
            $table->dropIndex('idx_fonds_quantite');
        });

        // ==================== BOUGIES ====================
        Schema::table('bougies', function (Blueprint $table) {
            $table->dropIndex('idx_bougies_reference');
            $table->dropIndex('idx_bougies_parfum');
            $table->dropIndex('idx_bougies_quantite');
            $table->dropIndex('idx_bougies_stock_alert');
        });
    }
};
