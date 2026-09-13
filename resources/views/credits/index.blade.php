@extends('layouts.admin')

@section('title', 'Cahier de crédit')

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Cahier de crédit</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cahier de crédit</li>
                </ol>
            </nav>
        </div>
        @can('credits.creer')
            <div class="ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCreditModal">
                    <i class='bx bxs-plus-square'></i> Nouveau crédit
                </button>
            </div>
        @endcan
    </div>
    <hr />

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 mb-3">
        <div class="col mb-3">
            <div class="card radius-10 bg-danger bg-gradient h-100">
                <div class="card-body">
                    <p class="mb-0 text-white">Total des crédits accordés</p>
                    <h5 class="my-1 text-white">{{ $fmt($stats['totalAccorde']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card radius-10 bg-success bg-gradient h-100">
                <div class="card-body">
                    <p class="mb-0 text-white">Total remboursé</p>
                    <h5 class="my-1 text-white">{{ $fmt($stats['totalRembourse']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card radius-10 bg-info bg-gradient h-100">
                <div class="card-body">
                    <p class="mb-0 text-white">Total restant à récupérer</p>
                    <h5 class="my-1 text-white">{{ $fmt($stats['totalAccorde'] - $stats['totalRembourse']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="mb-0 text-muted">Crédits en cours</p>
                    <h5 class="my-1">{{ $stats['nbEnCours'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="mb-0 text-muted">Crédits soldés</p>
                    <h5 class="my-1">{{ $stats['nbSoldes'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="mb-0 text-muted">Remboursements (espèces / or / mixte)</p>
                    <h5 class="my-1">{{ $stats['remboursementsEspeces'] }} / {{ $stats['remboursementsOr'] }} / {{ $stats['remboursementsMixtes'] }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('credits.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="single-select form-select">
                        <option value="">Tous</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->nom_complet }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Tous</option>
                        <option value="en_cours" @selected(request('statut') === 'en_cours')>En cours</option>
                        <option value="partiel" @selected(request('statut') === 'partiel')>Partiellement remboursé</option>
                        <option value="solde" @selected(request('statut') === 'solde')>Soldé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" name="debut" class="form-control" value="{{ request('debut') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" name="fin" class="form-control" value="{{ request('fin') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class='bx bx-filter-alt'></i> Filtrer</button>
                    <a href="{{ route('credits.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-wallet me-2'></i>CAHIER DES CRÉDITS</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="credits-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>RÉFÉRENCE</th>
                            <th>CLIENT</th>
                            <th>DATE</th>
                            <th>MONTANT REMIS</th>
                            <th>REMBOURSÉ</th>
                            <th>SOLDE</th>
                            <th>STATUT</th>
                            <th width="8%">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($credits as $credit)
                            <tr>
                                <td>{{ $credit->numero }}</td>
                                <td>{{ $credit->client->nom_complet }}</td>
                                <td>{{ $credit->date_credit->format('d/m/Y') }}</td>
                                <td>{{ $fmt($credit->montant_remis) }}</td>
                                <td>{{ $fmt($credit->montant_rembourse) }}</td>
                                <td>{{ $fmt($credit->solde()) }}</td>
                                <td><span class="badge {{ $credit->statutBadge() }}">{{ $credit->statutLibelle() }}</span></td>
                                <td>
                                    <a href="{{ route('credits.show', $credit) }}" class="btn btn-primary btn-sm" title="Voir">
                                        <i class='bx bx-show'></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('credits.creer')
        <div class="modal fade" id="addCreditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('credits.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Nouveau crédit</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Client <span class="text-danger">*</span></label>
                                <select class="single-select form-select" name="client_id">
                                    <option value="">-- Choisir --</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->nom_complet }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date du crédit <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="date_credit" value="{{ old('date_credit', now()->format('Y-m-d\TH:i')) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mode de remise</label>
                                    <select name="mode_paiement" class="form-select">
                                        <option value="">Sélectionner…</option>
                                        @foreach (\App\Models\MouvementFinancier::MODES_PAIEMENT as $valeur => $libelle)
                                            <option value="{{ $valeur }}">{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Montant accordé <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" inputmode="numeric" data-montant class="form-control" name="montant_accorde" value="{{ old('montant_accorde') }}">
                                        <span class="input-group-text">{{ $devise }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Montant effectivement remis <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" inputmode="numeric" data-montant class="form-control" name="montant_remis" value="{{ old('montant_remis') }}">
                                        <span class="input-group-text">{{ $devise }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-1">
                                <label class="form-label">Observations</label>
                                <textarea class="form-control" name="observations" rows="2">{{ old('observations') }}</textarea>
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger mt-3 py-2 mb-0">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif
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
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addCreditModal')).show());
            </script>
        @endif
    @endcan
@endsection

@push('scripts')
    <script>
        $('#credits-table').DataTable({ order: [[2, 'desc']] });
        document.querySelectorAll('#credits-table [title]').forEach(el => new bootstrap.Tooltip(el));
    </script>
@endpush
