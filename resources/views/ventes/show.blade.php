@extends('layouts.admin')

@section('title', 'Vente '.$vente->numero)

@php
    $devise = $vente->bureau?->devise_symbole ?? auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 2, ',', ' ').' '.$devise;
    $statutBadges = ['validee' => 'bg-success', 'annulee' => 'bg-danger'];
    // Bambara : usage propre au marché malien, ne s'affiche pas pour les autres pays.
    $afficherBambara = $vente->bureau?->estAuMali() ?? true;
    $bambara = \App\Support\CalculOr::arrondir(bcdiv((string) $vente->montant_total, '5', 4));
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Ventes d'or</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ventes.index') }}">Ventes d'or</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $vente->numero }}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('ventes.pdf', $vente) }}" target="_blank" class="btn btn-secondary">
                <i class='bx bxs-file-pdf'></i> Facture PDF
            </a>
            @can('ventes.annuler')
                @if ($vente->estValidee())
                    <form method="POST" action="{{ route('ventes.annuler', $vente) }}" class="confirm-form" data-title="Annuler cette vente ? Les barres retourneront en stock.">
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
            <h6 class="text-white mb-0"><i class='bx bx-transfer-alt me-2'></i>VENTE {{ $vente->numero }}</h6>
            <span class="ms-auto badge {{ $statutBadges[$vente->statut] }}">{{ $vente->statut_libelle }}</span>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><small class="text-muted">Client (acheteur)</small><div>{{ $vente->client->nom_complet }}</div></div>
                <div class="col-md-3"><small class="text-muted">Date</small><div>{{ $vente->date_operation->format('d/m/Y à H:i') }}</div></div>
                <div class="col-md-3"><small class="text-muted">Prix de base</small><div>{{ number_format($vente->prix_base, 0, ',', ' ') }} {{ $devise }}</div></div>
                <div class="col-md-3"><small class="text-muted">Opérateur</small><div>{{ $vente->user?->name ?? '—' }}</div></div>
            </div>
            @if ($vente->estValidee())
                <p class="text-muted small mb-3">
                    <i class='bx bx-check-double'></i> Validée par <strong>{{ $vente->valideur?->name }}</strong>
                    le {{ $vente->validee_at?->format('d/m/Y à H:i') }}
                </p>
            @endif
            @if ($vente->observations)
                <p><strong>Observations :</strong> {!! nl2br(e($vente->observations)) !!}</p>
            @endif

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>POIDS (g)</th>
                            <th>CARAT</th>
                            <th class="text-end">PRIX UNITAIRE</th>
                            <th class="text-end">MONTANT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vente->barres as $barre)
                            <tr>
                                <td>{{ $barre->numero_barre }}</td>
                                <td>{{ number_format($barre->poids, 3, ',', ' ') }}</td>
                                <td><strong>{{ number_format($barre->carat, 2, ',', ' ') }}</strong></td>
                                <td class="text-end">{{ $fmt($barre->prix_unitaire_vente) }}</td>
                                <td class="text-end">{{ $fmt($barre->montant_vente) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-active fw-bold">
                            <td>{{ $vente->barres->count() }} barre(s)</td>
                            <td>{{ number_format($vente->barres->sum('poids'), 3, ',', ' ') }}</td>
                            <td colspan="2" class="text-end">TOTAL</td>
                            <td class="text-end">{{ $fmt($vente->montant_total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row mt-3 align-items-stretch">
                <div class="{{ $afficherBambara ? 'col-md-6' : 'col-md-12' }}">
                    <div class="card bg-light h-100">
                        <div class="card-body py-2">
                            <small class="text-muted">Montant total</small>
                            <div class="fw-bold">{{ $fmt($vente->montant_total) }}</div>
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

            <div class="row mt-3 align-items-stretch">
                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body py-2">
                            <small class="text-muted">Montant encaissé</small>
                            <div class="fw-bold">{{ $fmt($vente->montant_paye) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card {{ $vente->reste() > 0 ? 'bg-warning-subtle' : 'bg-light' }} h-100">
                        <div class="card-body py-2">
                            <small class="text-muted">Reste à encaisser (crédit accordé)</small>
                            <div class="fw-bold">{{ $fmt($vente->reste()) }}</div>
                        </div>
                    </div>
                </div>
                @can('ventes.valider')
                    @if ($vente->reste() > 0 && $vente->statut !== 'annulee')
                        <div class="col-md-4">
                            <form method="POST" action="{{ route('ventes.paiement', $vente) }}" class="card h-100">
                                @csrf @method('PATCH')
                                <div class="card-body py-2">
                                    <small class="text-muted">Enregistrer un encaissement</small>
                                    <input type="text" inputmode="numeric" data-montant name="montant" class="form-control form-control-sm mt-1" placeholder="Montant" required>
                                    <select name="mode_paiement" class="form-select form-select-sm mt-1" required>
                                        <option value="">Mode de paiement…</option>
                                        @foreach (\App\Models\MouvementFinancier::MODES_PAIEMENT as $valeur => $libelle)
                                            <option value="{{ $valeur }}">{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-1">Encaissé</button>
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
