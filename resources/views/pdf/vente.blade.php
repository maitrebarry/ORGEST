@php
    $bureau = $vente->bureau;
    $devise = $bureau?->devise_symbole ?? config('pays_devises.Mali.symbole');
    $fmt = fn ($m) => number_format((float) $m, 2, ',', ' ').' '.$devise;
    $entreprise = [
        'nom' => $bureau->nom ?? config('entreprise.nom'),
        'complement' => $bureau ? null : config('entreprise.complement'),
        'adresse' => $bureau->adresse ?? config('entreprise.adresse'),
        'telephone' => $bureau->telephone ?? config('entreprise.telephone'),
    ];
    $logoPath = $bureau?->logo ? public_path('storage/'.$bureau->logo) : null;
    $logoPath = $logoPath && file_exists($logoPath) ? $logoPath : null;
    $couleur = $bureau?->couleur ?? '#1d4e89';
    $couleurSombre = $bureau?->couleur_sombre ?? '#123a63';
    // Bambara = Total ÷ 5 (formule confirmée par l'entreprise ; sens
    // métier exact non confirmé, cf. §17 — affiché tel quel, non déduit).
    $bambara = \App\Support\CalculOr::arrondir(bcdiv((string) $vente->montant_total, '5', 4));
    $afficherBambara = $bureau?->estAuMali() ?? true;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; margin: 0; }
        .logo-banner { width: 100%; line-height: 0; margin-bottom: 0; }
        .logo-banner img { width: 100%; max-height: 160px; display: block; }
        .header { border-bottom: 3px solid {{ $couleur }}; padding: 10px 0; margin-bottom: 16px; }
        .header table { width: 100%; }
        .company { font-size: 15px; font-weight: bold; color: {{ $couleur }}; }
        .company small { display: block; font-size: 9px; color: #6b7280; font-weight: normal; margin-top: 2px; }
        .doc-title { text-align: center; font-size: 16px; font-weight: bold; color: {{ $couleurSombre }}; margin: 4px 0 2px; text-transform: uppercase; }
        .numero { text-align: center; color: #6b7280; margin-bottom: 14px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-table td { padding: 5px 8px; border: 1px solid #e5e7eb; }
        .info-table .label { background: #f3f4f6; font-weight: bold; width: 22%; }
        table.data { width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; }
        table.data th {
            background: {{ $couleur }}; color: #fff; padding: 6px 6px; text-align: left;
            text-transform: uppercase; font-size: 8.5px; letter-spacing: .02em;
            border: 1px solid {{ $couleur }};
        }
        table.data td { padding: 4px 6px; border: 1px solid #e5e7eb; text-align: right; }
        table.data td:first-child { text-align: center; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data tfoot td { font-weight: bold; background: #eef2f7 !important; border-top: 2px solid {{ $couleur }}; }
        .reglement { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .reglement td { padding: 7px 10px; border: 1px solid #e5e7eb; }
        .reglement .label { background: #f3f4f6; font-weight: bold; width: 25%; color: {{ $couleurSombre }}; }
        .reglement .valeur { width: 25%; }
        .reglement td.reste { background: #fff7ed; }
        .footer { margin-top: 24px; text-align: center; font-size: 9px; color: #9ca3af; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; padding-top: 30px; }
        .sig-line { border-top: 1px solid #9ca3af; width: 70%; margin: 0 auto; padding-top: 4px; color: #6b7280; }
    </style>
</head>
<body>
    @if ($logoPath)
        <div class="logo-banner"><img src="{{ $logoPath }}" alt="Logo"></div>
    @endif
    <div class="header">
        <table>
            <tr>
                <td>
                    @unless ($logoPath)
                        <div class="company">
                            {{ $entreprise['nom'] }}
                            <small>
                                {{ $entreprise['complement'] }}<br>
                                {{ $entreprise['adresse'] }}<br>
                                @if ($entreprise['telephone']) Tél : {{ $entreprise['telephone'] }} @endif
                            </small>
                        </div>
                    @endunless
                </td>
                <td style="text-align: right; color:#6b7280; vertical-align: top;">
                    Édité le {{ now()->format('d/m/Y à H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">Facture de vente d'or</div>
    <div class="numero">N° {{ $vente->numero }} — {{ $vente->date_operation->format('d/m/Y à H:i') }}</div>

    <table class="info-table">
        <tr>
            <td class="label">Client</td><td>{{ $vente->client->nom_complet }}</td>
            <td class="label">Téléphone</td><td>{{ $vente->client->telephone ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Prix de base</td><td>{{ number_format($vente->prix_base, 0, ',', ' ') }} {{ $devise }}</td>
            <td class="label">Nombre de barres</td><td>{{ $vente->barres->count() }}</td>
        </tr>
        <tr>
            <td class="label">Opérateur</td><td>{{ $vente->user?->name ?? '—' }}</td>
            <td class="label">Statut</td><td>{{ $vente->statut_libelle }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Prix de base</th>
                <th>Poids (g)</th>
                <th>Eau</th>
                <th>Densité</th>
                <th>Carat</th>
                <th>Prix unitaire</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vente->barres->sortByDesc('poids') as $barre)
                <tr>
                    <td>{{ $fmt($vente->prix_base) }}</td>
                    <td>{{ number_format($barre->poids, 3, ',', ' ') }}</td>
                    <td>{{ number_format($barre->eau, 4, ',', ' ') }}</td>
                    <td>{{ number_format($barre->densite_tronquee, 2, ',', ' ') }}</td>
                    <td>{{ number_format($barre->carat, 2, ',', ' ') }}</td>
                    <td>{{ $fmt($barre->prix_unitaire_vente) }}</td>
                    <td>{{ $fmt($barre->montant_vente) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="1">TOTAL</td>
                <td>{{ number_format($vente->barres->sum('poids'), 3, ',', ' ') }}</td>
                <td>{{ number_format($vente->barres->sum('eau'), 4, ',', ' ') }}</td>
                <td colspan="2"></td>
                <td colspan="2">{{ $fmt($vente->montant_total) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="reglement">
        <tr>
            @if ($afficherBambara)
                <td class="label">Montant total</td><td class="valeur">{{ $fmt($vente->montant_total) }}</td>
                <td class="label">Bambara</td><td class="valeur">{{ $fmt($bambara) }}</td>
            @else
                <td class="label">Montant total</td><td class="valeur" colspan="3">{{ $fmt($vente->montant_total) }}</td>
            @endif
        </tr>
        <tr>
            <td class="label">Montant encaissé</td><td class="valeur">{{ $fmt($vente->montant_paye) }}</td>
            <td class="label reste">Reste à encaisser</td><td class="valeur reste">{{ $fmt($vente->reste()) }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="sig-line">Le client</div></td>
            <td><div class="sig-line">{{ $vente->user?->name ?? "L'opérateur" }}</div></td>
        </tr>
    </table>

    <div class="footer">Facture {{ $vente->numero }} — {{ $entreprise['nom'] }}</div>
</body>
</html>
