@php
    $bureau = $client->bureau;
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
    $mouvementsActifs = $mouvements->whereNull('annule_at');
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
        table.data td:first-child, table.data td:nth-child(2), table.data td:nth-child(3), table.data td:nth-child(4) { text-align: left; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data tfoot td { font-weight: bold; background: #eef2f7 !important; border-top: 2px solid {{ $couleur }}; }
        .reglement { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .reglement td { padding: 7px 10px; border: 1px solid #e5e7eb; }
        .reglement .label { background: #f3f4f6; font-weight: bold; width: 25%; color: {{ $couleurSombre }}; }
        .reglement .valeur { width: 25%; }
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

    <div class="doc-title">Relevé de compte client</div>
    <div class="numero">{{ $client->nom_complet }} — {{ $client->identifiant }}</div>

    <table class="info-table">
        <tr>
            <td class="label">Téléphone</td><td>{{ $client->telephone ?? '—' }}</td>
            <td class="label">Adresse</td><td>{{ $client->adresse ?? '—' }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Réf.</th>
                <th>Type</th>
                <th>Libellé / observations</th>
                <th>Sortie</th>
                <th>Entrée</th>
                <th>Solde</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mouvementsActifs as $mouvement)
                <tr>
                    <td>{{ $mouvement->date_mouvement->format('d/m/Y') }}</td>
                    <td>{{ $mouvement->numero }}</td>
                    <td>{{ $mouvement->type_libelle }}</td>
                    <td>{{ $mouvement->observations ?? '—' }}</td>
                    <td>{{ $mouvement->sens === 'sortie' ? $fmt($mouvement->montant) : '—' }}</td>
                    <td>{{ $mouvement->sens === 'entree' ? $fmt($mouvement->montant) : '—' }}</td>
                    <td>{{ $fmt($mouvement->solde_apres) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;">Aucun mouvement enregistré.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">TOTAL</td>
                <td>{{ $fmt($mouvementsActifs->where('sens', 'sortie')->sum('montant')) }}</td>
                <td>{{ $fmt($mouvementsActifs->where('sens', 'entree')->sum('montant')) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <table class="reglement">
        <tr>
            <td class="label">Solde actuel</td>
            <td class="valeur" colspan="3">
                {{ $fmt(abs($solde)) }}
                @if ($solde > 0) (le client doit) @elseif ($solde < 0) (l'entreprise doit) @else (soldé) @endif
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="sig-line">Le client</div></td>
            <td><div class="sig-line">{{ $entreprise['nom'] }}</div></td>
        </tr>
    </table>

    <div class="footer">Relevé de compte — {{ $client->nom_complet }} — {{ $entreprise['nom'] }}</div>
</body>
</html>
