<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ModeMarcheApiController;

/*
|--------------------------------------------------------------------------
| API Routes pour Mode Marché
|--------------------------------------------------------------------------
|
| Ces routes retournent TOUJOURS du JSON. Elles sont séparées des routes
| web (Blade) pour éviter la confusion API/View.
|
| Middleware: 'auth:sanctum' + 'role:admin,employe'
|
*/

Route::middleware(['auth', 'role:admin,employe'])->prefix('marche')->name('api.marche.')->group(function () {
    // Liste des ventes du jour
    Route::get('/ventes-jour', [ModeMarcheApiController::class, 'ventesJour'])->name('ventes-jour');
    
    // Annuler une vente
    Route::post('/{order}/cancel', [ModeMarcheApiController::class, 'cancel'])->name('cancel');
    
    // Export CSV
    Route::get('/export', [ModeMarcheApiController::class, 'export'])->name('export');
});
