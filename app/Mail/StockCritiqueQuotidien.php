<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class StockCritiqueQuotidien extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $alertes;
    public int $ruptures;
    public int $critiques;

    public function __construct(Collection $alertes)
    {
        $this->alertes = $alertes;
        
        // Séparer ruptures (quantité = 0) et critiques (quantité > 0)
        $this->ruptures = $alertes->filter(fn($a) => $a->quantite_actuelle === 0)->count();
        $this->critiques = $alertes->filter(fn($a) => $a->quantite_actuelle > 0)->count();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Rapport Stock Quotidien - {$this->ruptures} rupture(s), {$this->critiques} critique(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stock-critique-quotidien',
        );
    }
}
