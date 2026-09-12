@php
    $fmt2 = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $bureau = $version->bureau;
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
        .doc-title { text-align: center; font-size: 15px; font-weight: bold; color: {{ $couleurSombre }}; margin: 4px 0 2px; text-transform: uppercase; }
        .doc-subtitle { text-align: center; color: #6b7280; margin-bottom: 16px; font-size: 10px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: {{ $couleur }}; color: #fff; padding: 6px 8px; text-align: left;
            text-transform: uppercase; font-size: 9px; letter-spacing: .03em;
        }
        table.data td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data td.carat { font-weight: bold; color: {{ $couleurSombre }}; }
        .footer { margin-top: 24px; text-align: center; font-size: 9px; color: #9ca3af; }
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

    <div class="doc-title">Barème Densité → Carat</div>
    <div class="doc-subtitle">
        {{ $version->libelle }}
        @if ($version->actif) (version active) @endif
        — {{ $version->lignes->count() }} plage(s)
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Densité min</th>
                <th>Densité max</th>
                <th>Carat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($version->lignes as $ligne)
                <tr>
                    <td>{{ $fmt2($ligne->densite_min) }}</td>
                    <td>{{ $fmt2($ligne->densite_max) }}</td>
                    <td class="carat">{{ $fmt2($ligne->carat) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré par ORGEST — {{ $entreprise['nom'] }}
    </div>
</body>
</html>
