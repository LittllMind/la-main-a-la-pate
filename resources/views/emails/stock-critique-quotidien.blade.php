<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; }
        .alert-section { margin: 20px 0; }
        .alert-item { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #ef4444; border-radius: 4px; }
        .alert-item.critique { border-left-color: #f59e0b; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-rupture { background: #ef4444; color: white; }
        .badge-critique { background: #f59e0b; color: white; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">📊 Rapport Stock Quotidien</h1>
            <p style="margin: 10px 0 0;">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }} - 9h00</p>
        </div>

        <div class="content">
            @if($ruptures > 0)
                <div class="alert-section">
                    <h2 style="color: #ef4444;">🚨 Ruptures de stock ({{ $ruptures }})</h2>
                    @foreach($alertes->where('quantite_actuelle', 0) as $alerte)
                        <div class="alert-item">
                            <span class="badge badge-rupture">RUPTURE</span>
                            <strong>
                                @if($alerte->alertable_type === 'App\Models\Vinyle')
                                    {{ $alerte->alertable->nom }} ({{ $alerte->alertable->modele }})
                                @else
                                    Fond {{ ucfirst($alerte->alertable->type) }}
                                @endif
                            </strong>
                            <p style="margin: 5px 0 0; color: #6b7280;">Stock actuel : 0 unité</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($critiques > 0)
                <div class="alert-section">
                    <h2 style="color: #f59e0b;">⚠️ Stocks critiques ({{ $critiques }})</h2>
                    @foreach($alertes->where('quantite_actuelle', '>', 0) as $alerte)
                        <div class="alert-item critique">
                            <span class="badge badge-critique">CRITIQUE</span>
                            <strong>
                                @if($alerte->alertable_type === 'App\Models\Vinyle')
                                    {{ $alerte->alertable->nom }} ({{ $alerte->alertable->modele }})
                                @else
                                    Fond {{ ucfirst($alerte->alertable->type) }}
                                @endif
                            </strong>
                            <p style="margin: 5px 0 0; color: #6b7280;">
                                Stock actuel : {{ $alerte->quantite_actuelle }} / Seuil : {{ $alerte->seuil_alerte }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div style="background: #e0e7ff; padding: 15px; border-radius: 4px; margin-top: 20px;">
                <strong>💡 Action recommandée :</strong>
                <p style="margin: 5px 0 0;">Connectez-vous à l'admin pour gérer les réapprovisionnements</p>
            </div>
        </div>

        <div class="footer">
            <p>Email automatique - Stock Vinyles App</p>
            <p>Pour modifier ces alertes : <a href="{{ url('/admin/stock-alerts') }}">Gérer les seuils</a></p>
        </div>
    </div>
</body>
</html>
