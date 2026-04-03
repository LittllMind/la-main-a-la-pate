<?php

namespace App\Observers;

use App\Models\Fond;
use App\Services\StockMovementService;

class FondObserver
{
    private array $oldQuantities = [];

    /**
     * Handle the Fond "retrieved" event.
     * Capture les valeurs avant modification
     */
    public function retrieved(Fond $fond): void
    {
        // On ne capture pas ici car retrieved est appelé à chaque chargement
    }

    /**
     * Handle the Fond "saving" event.
     * Capture les anciennes valeurs
     */
    public function saving(Fond $fond): void
    {
        if ($fond->exists) {
            $this->oldQuantities = [
                'miroir' => $fond->getOriginal('miroir', $fond->miroir),
                'dore' => $fond->getOriginal('dore', $fond->dore),
                'standard' => $fond->getOriginal('standard', $fond->standard),
            ];
        }
    }

    /**
     * Handle the Fond "saved" event.
     */
    public function saved(Fond $fond): void
    {
        if (empty($this->oldQuantities)) {
            return;
        }

        $types = ['miroir', 'dore', 'standard'];
        
        foreach ($types as $type) {
            $oldQty = $this->oldQuantities[$type] ?? 0;
            $newQty = $fond->$type;

            if ($oldQty !== $newQty) {
                StockMovementService::traceFondStockChanged($fond, $type, $oldQty, $newQty);
            }
        }

        // Reset après traitement
        $this->oldQuantities = [];
    }
}
