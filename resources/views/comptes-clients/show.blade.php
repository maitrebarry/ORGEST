@extends('layouts.admin')

@section('title', 'Compte client — '.$client->nom_complet)

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Compte client</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('comptes-clients.index') }}">Comptes clients</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $client->nom_complet }}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('comptes-clients.pdf', $client) }}" target="_blank" class="btn btn-outline-secondary">
                <i class='bx bx-printer'></i> Imprimer le relevé
            </a>
            @can('comptes_clients.creer')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMouvementModal">
                    <i class='bx bxs-plus-square'></i> Nouveau mouvement
                </button>
            @endcan
        </div>
    </div>
    <hr />

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <div class="row row-cols-1 row-cols-md-3 mb-3">
        <div class="col mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="mb-0 text-muted">Client</p>
                    <h5 class="my-1">{{ $client->nom_complet }}</h5>
                    <p class="text-muted small mb-0">{{ $client->telephone ?? '—' }} — {{ $client->identifiant }}</p>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card radius-10 {{ $solde > 0 ? 'bg-danger' : ($solde < 0 ? 'bg-success' : 'bg-secondary') }} bg-gradient h-100">
                <div class="card-body">
                    <p class="mb-0 text-white">Solde actuel</p>
                    <h5 class="my-1 text-white">
                        {{ $fmt(abs($solde)) }}
                        @if ($solde > 0) — le client doit @elseif ($solde < 0) — l'entreprise doit @else — soldé @endif
                    </h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="mb-0 text-muted">Mouvements enregistrés</p>
                    <h5 class="my-1">{{ $mouvements->whereNull('annule_at')->count() }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-notepad me-2'></i>RELEVÉ DU COMPTE</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <i class='bx bx-info-circle'></i> Seul le dernier mouvement actif peut être annulé, pour ne jamais
                fausser l'historique des soldes déjà enregistrés.
            </p>
            <div class="table-responsive">
                <table id="mouvements-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>RÉFÉRENCE</th>
                            <th>DATE</th>
                            <th>TYPE</th>
                            <th>LIBELLÉ / OBS.</th>
                            <th>CAISSE</th>
                            <th>SORTIE (client reçoit)</th>
                            <th>ENTRÉE (client rembourse)</th>
                            <th>SOLDE</th>
                            <th>PAR</th>
                            <th width="6%">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mouvements as $mouvement)
                            <tr class="{{ $mouvement->estAnnule() ? 'text-decoration-line-through text-muted' : '' }}">
                                <td>{{ $mouvement->numero }}</td>
                                <td>{{ $mouvement->date_mouvement->format('d/m/Y H:i') }}</td>
                                <td>{{ $mouvement->type_libelle }}</td>
                                <td>{{ $mouvement->observations ?? '—' }}</td>
                                <td>
                                    @if ($mouvement->impacte_caisse)
                                        <span class="badge bg-light text-dark border">{{ $mouvement->mode_paiement_libelle ?? 'Oui' }}</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Non (nature)</span>
                                    @endif
                                </td>
                                <td>{{ $mouvement->sens === 'sortie' ? $fmt($mouvement->montant) : '—' }}</td>
                                <td>{{ $mouvement->sens === 'entree' ? $fmt($mouvement->montant) : '—' }}</td>
                                <td class="fw-bold">{{ $fmt($mouvement->solde_apres) }}</td>
                                <td>{{ $mouvement->user?->name ?? '—' }}</td>
                                <td>
                                    @can('comptes_clients.annuler')
                                        @if (! $mouvement->estAnnule() && $mouvement->id === $dernierMouvementId)
                                            <form method="POST" action="{{ route('comptes-clients.mouvements.annuler', $mouvement) }}" class="d-inline confirm-form" data-title="Annuler ce mouvement ?">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Annuler"><i class='bx bx-undo'></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('comptes_clients.creer')
        <div class="modal fade" id="addMouvementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('comptes-clients.mouvements.store', $client) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Nouveau mouvement — {{ $client->nom_complet }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" id="typeMouvement">
                                        @foreach (\App\Models\MouvementCompteClient::TYPES as $valeur => $libelle)
                                            <option value="{{ $valeur }}" @selected(old('type') == $valeur)>{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sens <span class="text-danger">*</span></label>
                                    <select class="form-select" name="sens" id="sensMouvement">
                                        @foreach (\App\Models\MouvementCompteClient::SENS as $valeur => $libelle)
                                            <option value="{{ $valeur }}" @selected(old('sens') == $valeur)>{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Montant ({{ $devise }}) <span class="text-danger">*</span></label>
                                    <input type="text" inputmode="numeric" data-montant class="form-control" name="montant" value="{{ old('montant') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="date_mouvement" value="{{ old('date_mouvement', now()->format('Y-m-d\TH:i')) }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="impacteCaisse" name="impacte_caisse" value="1" @checked(old('impacte_caisse', true))>
                                    <label class="form-check-label" for="impacteCaisse">
                                        Ce mouvement touche la caisse du jour (journée financière)
                                    </label>
                                </div>
                                <small class="text-muted">Décochez pour un règlement en nature (or) qui ne fait pas bouger la caisse.</small>
                            </div>
                            <div class="mb-3" id="modePaiementWrapper">
                                <label class="form-label">Mode (caisse)</label>
                                <select name="mode_paiement" class="form-select">
                                    <option value="">Sélectionner…</option>
                                    @foreach (\App\Models\MouvementFinancier::MODES_PAIEMENT as $valeur => $libelle)
                                        <option value="{{ $valeur }}" @selected(old('mode_paiement') == $valeur)>{{ $libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-1">
                                <label class="form-label">Observations</label>
                                <textarea class="form-control" name="observations" rows="2">{{ old('observations') }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addMouvementModal')).show());
            </script>
        @endif
    @endcan
@endsection

@push('scripts')
    <script>
        $('#mouvements-table').DataTable({ order: [[1, 'asc']] });
        document.querySelectorAll('#mouvements-table [title]').forEach(el => new bootstrap.Tooltip(el));

        $(document).on('submit', '.confirm-form', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: $(this).data('title') || 'Confirmer ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });

        // Valeurs par défaut suggérées selon le type — l'opérateur reste
        // libre de les changer (ex. un règlement en or payé en espèces).
        const defautsSens = { retrait: 'sortie', achat_or: 'entree', transport: 'sortie', autre: null };
        const defautsCaisse = { retrait: true, achat_or: false, transport: true, autre: true };

        document.getElementById('typeMouvement')?.addEventListener('change', function () {
            const sens = defautsSens[this.value];
            if (sens) document.getElementById('sensMouvement').value = sens;
            document.getElementById('impacteCaisse').checked = defautsCaisse[this.value] ?? true;
        });
    </script>
@endpush
