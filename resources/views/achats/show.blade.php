@extends('layouts.admin')

@section('title', 'Achat '.$achat->numero)

@php
    $devise = $achat->bureau?->devise_symbole ?? auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 2, ',', ' ').' '.$devise;
    $statutBadges = ['validee' => 'bg-success', 'annulee' => 'bg-danger'];
    // Bambara = Total ÷ 5 (formule confirmée par l'entreprise ; affiché ici
    // dès l'enregistrement pour que l'opérateur puisse le communiquer
    // directement au client, sans devoir ouvrir la facture PDF). Usage
    // propre au marché malien : ne s'affiche pas pour les autres pays.
    $afficherBambara = $achat->bureau?->estAuMali() ?? true;
    $bambara = \App\Support\CalculOr::arrondir(bcdiv((string) $achat->montant_total, '5', 4));
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Achats d'or</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('achats.index') }}">Achats d'or</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $achat->numero }}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('achats.pdf', $achat) }}" target="_blank" class="btn btn-secondary">
                <i class='bx bxs-file-pdf'></i> Facture PDF
            </a>
            @can('achats.annuler')
                @if ($achat->estValidee())
                    <form method="POST" action="{{ route('achats.annuler', $achat) }}" class="confirm-form" data-title="Annuler cette opération validée ? Cette action est tracée.">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-outline-danger"><i class='bx bx-block'></i> Annuler</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
    <hr />

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center flex-wrap gap-2">
            <h6 class="text-white mb-0"><i class='bx bx-coin-stack me-2'></i>ACHAT {{ $achat->numero }}</h6>
            <span class="ms-auto badge {{ $statutBadges[$achat->statut] }}">{{ $achat->statut_libelle }}</span>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><small class="text-muted">Client</small><div>{{ $achat->client->nom_complet }}</div></div>
                <div class="col-md-3"><small class="text-muted">Date</small><div>{{ $achat->date_operation->format('d/m/Y à H:i') }}</div></div>
                <div class="col-md-3"><small class="text-muted">Prix de base</small><div>{{ number_format($achat->prix_base, 0, ',', ' ') }} {{ $devise }}</div></div>
                <div class="col-md-3"><small class="text-muted">Opérateur</small><div>{{ $achat->user?->name ?? '—' }}</div></div>
            </div>
            @if ($achat->estValidee())
                <p class="text-muted small mb-3">
                    <i class='bx bx-check-double'></i> Validée par <strong>{{ $achat->valideur?->name }}</strong>
                    le {{ $achat->validee_at?->format('d/m/Y à H:i') }}
                </p>
            @endif
            @if ($achat->observations)
                <p><strong>Observations :</strong> {!! nl2br(e($achat->observations)) !!}</p>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>POIDS (g)</th>
                            <th>EAU</th>
                            <th>DENSITÉ</th>
                            <th>CARAT</th>
                            <th class="text-end">PRIX UNITAIRE</th>
                            <th class="text-end">MONTANT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($achat->barres->sortByDesc('poids') as $barre)
                            <tr>
                                <td>{{ $barre->numero_barre }}</td>
                                <td>{{ number_format($barre->poids, 3, ',', ' ') }}</td>
                                <td>{{ number_format($barre->eau, 4, ',', ' ') }}</td>
                                <td>{{ number_format($barre->densite_tronquee, 2, ',', ' ') }}</td>
                                <td><strong>{{ number_format($barre->carat, 2, ',', ' ') }}</strong></td>
                                <td class="text-end">{{ $fmt($barre->prix_unitaire) }}</td>
                                <td class="text-end">{{ $fmt($barre->montant) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-active fw-bold">
                            <td>{{ $achat->barres->count() }} barre(s)</td>
                            <td>{{ number_format($achat->poids_total, 3, ',', ' ') }}</td>
                            <td>{{ number_format($achat->eau_total, 4, ',', ' ') }}</td>
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td class="text-end">{{ $fmt($achat->montant_total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row mt-3 align-items-stretch">
                <div class="{{ $afficherBambara ? 'col-md-6' : 'col-md-12' }}">
                    <div class="card bg-light h-100">
                        <div class="card-body py-2">
                            <small class="text-muted">Montant total</small>
                            <div class="fw-bold">{{ $fmt($achat->montant_total) }}</div>
                        </div>
                    </div>
                </div>
                @if ($afficherBambara)
                    <div class="col-md-6">
                        <div class="card text-white h-100" style="background-color: #b8860b;">
                            <div class="card-body py-2">
                                <small class="text-white-50">Bambara</small>
                                <div class="fw-bold">{{ $fmt($bambara) }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($achat->reste() > 0 && $achat->statut !== 'annulee')
                @if (is_null($soldeDisponible))
                    <div class="alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0">
                        <i class='bx bx-error-circle fs-4'></i>
                        <div>
                            <strong>Aucune journée financière ouverte.</strong> Aucun paiement ne pourra être
                            enregistré tant qu'une journée n'aura pas été ouverte (fonds initial).
                            <a href="{{ route('tresorerie.index') }}" class="alert-link">Aller à la Trésorerie</a>
                        </div>
                    </div>
                @elseif ($soldeDisponible < $achat->reste())
                    <div class="alert alert-warning d-flex align-items-center gap-2 mt-3 mb-0">
                        <i class='bx bx-error fs-4'></i>
                        <div>
                            <strong>Fonds insuffisant.</strong> Solde disponible : {{ $fmt($soldeDisponible) }} —
                            reste à payer : {{ $fmt($achat->reste()) }}. Un approvisionnement sera nécessaire pour
                            régler la totalité.
                            <a href="{{ route('tresorerie.index') }}" class="alert-link">Aller à la Trésorerie</a>
                        </div>
                    </div>
                @endif
            @endif

            <div class="row mt-3 align-items-stretch">
                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body py-2">
                            <small class="text-muted">Montant payé</small>
                            <div class="fw-bold">{{ $fmt($achat->montant_paye) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card {{ $achat->reste() > 0 ? 'bg-warning-subtle' : 'bg-light' }} h-100">
                        <div class="card-body py-2">
                            <small class="text-muted">Reste à payer au client (crédit)</small>
                            <div class="fw-bold">{{ $fmt($achat->reste()) }}</div>
                        </div>
                    </div>
                </div>
                @can('achats.valider')
                    @if ($achat->reste() > 0 && $achat->statut !== 'annulee')
                        <div class="col-md-4">
                            <form method="POST" action="{{ route('achats.paiement', $achat) }}" class="card h-100"
                                  id="formPaiementAchat" data-solde-disponible="{{ $soldeDisponible ?? '' }}">
                                @csrf @method('PATCH')
                                <div class="card-body py-2">
                                    <small class="text-muted">Enregistrer un paiement</small>
                                    <input type="text" inputmode="numeric" data-montant name="montant" class="form-control form-control-sm mt-1" placeholder="Montant" required>
                                    <select name="mode_paiement" class="form-select form-select-sm mt-1" required>
                                        <option value="">Mode de paiement…</option>
                                        @foreach (\App\Models\MouvementFinancier::MODES_PAIEMENT as $valeur => $libelle)
                                            <option value="{{ $valeur }}">{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-1">Payé</button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('formPaiementAchat')?.addEventListener('submit', function (e) {
            const solde = this.dataset.soldeDisponible;
            const montant = parseFloat(String(this.querySelector('[name="montant"]').value).replace(/\s/g, '').replace(',', '.'));

            if (solde === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Aucune journée ouverte',
                    text: "Ouvrez d'abord une journée financière (fonds initial) avant d'enregistrer un paiement.",
                });
                return;
            }

            const soldeNum = parseFloat(solde);
            if (!isNaN(montant) && montant > soldeNum) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Fonds insuffisant',
                    text: 'Solde disponible : ' + soldeNum.toLocaleString('fr-FR') + ' — ce paiement de ' + montant.toLocaleString('fr-FR') + ' dépasse le fonds disponible.',
                });
            }
        });

        $(document).on('submit', '.confirm-form', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: $(this).data('title') || 'Confirmer ?',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                confirmButtonText: 'Oui', cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });
    </script>
@endpush
