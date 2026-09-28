@php
    $bureau = $journee->bureau;
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
    $soldeTheorique = $journee->estOuverte() ? $journee->soldeDisponible() : $journee->solde_theorique_fermeture;
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
        .badge-auto { font-size: 7.5px; color: #6b7280; }
        .reglement { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .reglement td { padding: 7px 10px; border: 1px solid #e5e7eb; }
        .reglement .label { background: #f3f4f6; font-weight: bold; width: 25%; color: {{ $couleurSombre }}; }
        .reglement .valeur { width: 25%; }
        .reglement td.ecart { background: #fff7ed; }
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

    <div class="doc-title">{{ $journee->estOuverte() ? 'Journée financière en cours' : 'Clôture de journée financière' }}</div>
    <div class="numero">
        Ouverte le {{ $journee->date_ouverture->format('d/m/Y à H:i') }}
        @if ($journee->date_fermeture) — Fermée le {{ $journee->date_fermeture->format('d/m/Y à H:i') }} @endif
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Statut</td><td>{{ $journee->estOuverte() ? 'Ouverte' : 'Fermée' }}</td>
            <td class="label">Ouverte par</td><td>{{ $journee->ouvrePar?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Fermée par</td><td>{{ $journee->fermePar?->name ?? '—' }}</td>
            <td class="label">Nombre de transactions</td><td>{{ $journee->mouvements->count() }}</td>
        </tr>
        @if ($journee->observations)
            <tr>
                <td class="label">Observations</td><td colspan="3">{{ $journee->observations }}</td>
            </tr>
        @endif
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Heure</th>
                <th>Nature</th>
                <th>Libellé</th>
                <th>Par</th>
                <th>Mode</th>
                <th>Entrée</th>
                <th>Sortie</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($journee->mouvements as $mouvement)
                <tr>
                    <td>{{ $mouvement->date_mouvement->format('d/m/Y H:i') }}</td>
                    <td>{{ $mouvement->nature_libelle }}</td>
                    <td>
                        {{ $mouvement->libelle }}
                        @if ($mouvement->estAutomatique()) <span class="badge-auto">(auto)</span> @endif
                    </td>
                    <td>{{ $mouvement->user?->name ?? '—' }}</td>
                    <td>{{ $mouvement->mode_paiement_libelle ?? '—' }}</td>
                    <td>{{ $mouvement->sens === 'entree' ? $fmt($mouvement->montant) : '—' }}</td>
                    <td>{{ $mouvement->sens === 'sortie' ? $fmt($mouvement->montant) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;">Aucune transaction enregistrée sur cette journée.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">TOTAL</td>
                <td>{{ $fmt($journee->totalEntrees()) }}</td>
                <td>{{ $fmt($journee->totalSorties()) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="reglement">
        <tr>
            <td class="label">Fonds initial</td><td class="valeur">{{ $fmt($journee->fondsInitial()) }}</td>
            <td class="label">Solde {{ $journee->estOuverte() ? 'théorique actuel' : 'théorique' }}</td>
            <td class="valeur">{{ $fmt($soldeTheorique) }}</td>
        </tr>
        @unless ($journee->estOuverte())
            <tr>
                <td class="label">Solde physique compté</td><td class="valeur">{{ $fmt($journee->solde_physique_fermeture) }}</td>
                <td class="label ecart">Écart</td><td class="valeur ecart">{{ $fmt($journee->ecart_fermeture) }}</td>
            </tr>
        @endunless
    </table>

    <table class="signatures">
        <tr>
            <td><div class="sig-line">Le caissier — {{ $journee->ouvrePar?->name ?? '—' }}</div></td>
            <td><div class="sig-line">Le responsable — {{ $journee->fermePar?->name ?? '—' }}</div></td>
        </tr>
    </table>

    <div class="footer">Journée financière n° {{ $journee->id }} — {{ $entreprise['nom'] }}</div>
</body>
</html>
