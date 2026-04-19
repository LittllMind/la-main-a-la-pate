<?php
/**
 * Migration : Ajout des soft deletes sur les tables principales
 * Mission Abacus MAT-4
 * 
 * Tables modifiées:
 * - vinyles -> bougies (définitivement remplacés)
 * - ventes
 * - orders
 * - fonds
 * - users (déjà dans Laravel par défaut? Vérifier)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== VINYLES ====================
        if (Schema::hasTable('vinyles') && !Schema::hasColumn('vinyles', 'deleted_at')) {
            Schema::table('vinyles', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // ==================== BOUGIES (anciennement vinyles) ====================
        if (Schema::hasTable('bougies') && !Schema::hasColumn('bougies', 'deleted_at')) {
            Schema::table('bougies', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // ==================== VENTES ====================
        if (!Schema::hasColumn('ventes', 'deleted_at')) {
            Schema::table('ventes', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // ==================== ORDERS ====================
        if (!Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // ==================== FONDS ====================
        if (!Schema::hasColumn('fonds', 'deleted_at')) {
            Schema::table('fonds', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // ==================== ADDRESSES ====================
        if (!Schema::hasColumn('addresses', 'deleted_at')) {
            Schema::table('addresses', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('bougies', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('ventes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('fonds', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
