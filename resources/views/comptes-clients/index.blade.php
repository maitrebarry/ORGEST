@extends('layouts.admin')

@section('title', 'Comptes clients')

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Comptes clients</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Comptes clients</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <p class="text-muted small">
        <i class='bx bx-info-circle'></i> Relevé continu par client (retraits remis, règlements en or, transport…),
        distinct du <a href="{{ route('credits.index') }}">Cahier de crédit</a>. Les mouvements qui touchent la
        caisse alimentent automatiquement la journée financière du jour et exigent qu'une journée soit ouverte avec
        des fonds suffisants.
    </p>

    <div class="row row-cols-1 row-cols-md-3 mb-3">
        <div class="col mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="mb-0 text-muted">Clients suivis</p>
                    <h5 class="my-1">{{ $stats['nbClientsSuivis'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card radius-10 bg-danger bg-gradient h-100">
                <div class="card-body">
                    <p class="mb-0 text-white">Total dû par les clients</p>
                    <h5 class="my-1 text-white">{{ $fmt($stats['totalDettes']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col mb-3">
            <div class="card radius-10 bg-success bg-gradient h-100">
                <div class="card-body">
                    <p class="mb-0 text-white">Total dû aux clients</p>
                    <h5 class="my-1 text-white">{{ $fmt($stats['totalCrediteurs']) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('comptes-clients.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Client</label>
                    <input type="text" name="recherche" class="form-control" value="{{ $recherche }}" placeholder="Nom ou prénom">
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input type="checkbox" class="form-check-input" id="avecMouvements" name="avec_mouvements_seulement" value="1" @checked(request()->boolean('avec_mouvements_seulement', true))>
                        <label class="form-check-label" for="avecMouvements">N'afficher que les clients ayant déjà un mouvement</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary"><i class='bx bx-filter-alt'></i> Filtrer</button>
                    <a href="{{ route('comptes-clients.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-notepad me-2'></i>COMPTES CLIENTS</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="comptes-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>CLIENT</th>
                            <th>TÉLÉPHONE</th>
                            <th>MOUVEMENTS</th>
                            <th>DERNIER MOUVEMENT</th>
                            <th>SOLDE</th>
                            <th width="8%">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td>{{ $client->nom_complet }}</td>
                                <td>{{ $client->telephone ?? '—' }}</td>
                                <td>{{ $client->nb_mouvements }}</td>
                                <td>{{ $client->dernier_mouvement?->date_mouvement->format('d/m/Y') ?? '—' }}</td>
                                <td class="{{ $client->solde_courant > 0 ? 'text-danger fw-bold' : ($client->solde_courant < 0 ? 'text-success fw-bold' : '') }}">
                                    {{ $fmt(abs($client->solde_courant)) }}
                                    @if ($client->solde_courant > 0) (doit) @elseif ($client->solde_courant < 0) (créditeur) @endif
                                </td>
                                <td>
                                    <a href="{{ route('comptes-clients.show', $client) }}" class="btn btn-primary btn-sm" title="Voir le relevé">
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
@endsection

@push('scripts')
    <script>
        $('#comptes-table').DataTable({ order: [[0, 'asc']] });
        document.querySelectorAll('#comptes-table [title]').forEach(el => new bootstrap.Tooltip(el));
    </script>
@endpush
