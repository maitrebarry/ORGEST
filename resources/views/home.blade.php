@extends('layouts.admin')

@section('title', 'Tableau de bord')

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
    $journeeOuverte = $journeeOuverte ?? null;
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Tableau de bord</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><i class="bx bx-home-alt"></i></li>
                    <li class="breadcrumb-item active" aria-current="page">Accueil</li>
                </ol>
            </nav>
        </div>
    </div>

    @if ($journeeOuverte?->estEnRupture())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class='bx bx-error-circle fs-4'></i>
            <div>
                <strong>Fonds épuisé.</strong> Plus aucun paiement d'achat ne pourra être honoré tant qu'un
                approvisionnement n'aura pas été enregistré.
                <a href="{{ route('tresorerie.index') }}" class="alert-link">Aller à la Trésorerie</a>
            </div>
        </div>
    @elseif ($journeeOuverte?->soldeFaible())
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class='bx bx-error fs-4'></i>
            <div>
                <strong>Solde faible.</strong> Le fonds d'achat approche de la rupture — pensez à demander un
                approvisionnement.
                <a href="{{ route('tresorerie.index') }}" class="alert-link">Aller à la Trésorerie</a>
            </div>
        </div>
    @elseif (auth()->user()->can('fonds.voir') && ! $journeeOuverte)
        <div class="alert alert-secondary d-flex align-items-center gap-2">
            <i class='bx bx-lock-open-alt fs-4'></i>
            <div>
                Aucune journée financière n'est ouverte.
                <a href="{{ route('tresorerie.index') }}" class="alert-link">Ouvrir la journée</a>
            </div>
        </div>
    @endif

    <h6 class="mb-0 text-uppercase">Mon tableau de bord</h6>
    <hr />
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3">
        @can('clients.voir')
            <div class="col mb-3">
                <div class="card radius-10 bg-primary bg-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-white">Clients actifs</p>
                                <h4 class="my-1 text-white">{{ $clientsCount }}</h4>
                            </div>
                            <div class="text-white ms-auto font-35"><i class='bx bx-group'></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('achats.voir')
            <div class="col mb-3">
                <div class="card radius-10 bg-warning bg-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-dark">Barres en stock</p>
                                <h4 class="text-dark my-1">{{ $stockCount }}</h4>
                                <p class="mb-0 text-dark small">{{ number_format($stockPoids, 3, ',', ' ') }} g</p>
                            </div>
                            <div class="text-dark ms-auto font-35"><i class='bx bx-cube'></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col mb-3">
                <div class="card radius-10 bg-danger bg-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-white">Achats aujourd'hui</p>
                                <h4 class="my-1 text-white">{{ $achatsJourCount }}</h4>
                                <p class="mb-0 text-white small">{{ $fmt($achatsJourMontant) }}</p>
                            </div>
                            <div class="text-white ms-auto font-35"><i class='bx bx-coin-stack'></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('ventes.voir')
            <div class="col mb-3">
                <div class="card radius-10 bg-success bg-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-white">Ventes aujourd'hui</p>
                                <h4 class="my-1 text-white">{{ $ventesJourCount }}</h4>
                                <p class="mb-0 text-white small">{{ $fmt($ventesJourMontant) }}</p>
                            </div>
                            <div class="text-white ms-auto font-35"><i class='bx bx-transfer-alt'></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('fonds.voir')
            <div class="col mb-3">
                <div class="card radius-10 bg-info bg-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-white">Solde disponible</p>
                                <h4 class="my-1 text-white">{{ $journeeOuverte ? $fmt($journeeOuverte->soldeDisponible()) : '—' }}</h4>
                                <p class="mb-0 text-white small">{{ $journeeOuverte ? 'Journée ouverte' : 'Aucune journée ouverte' }}</p>
                            </div>
                            <div class="text-white ms-auto font-35"><i class='bx bx-money-withdraw'></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('utilisateurs.voir')
            <div class="col mb-3">
                <div class="card radius-10 bg-dark bg-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-white">Utilisateurs actifs</p>
                                <h4 class="my-1 text-white">{{ $usersCount }}</h4>
                            </div>
                            <div class="text-white ms-auto font-35"><i class='bx bx-user-check'></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    @if (auth()->user()->canAny(['achats.creer', 'ventes.creer', 'clients.creer']))
        <div class="card">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-bolt-circle me-2'></i>ACCÈS RAPIDES</h6>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                @can('achats.creer')
                    <a href="{{ route('achats.create') }}" class="btn btn-outline-primary"><i class='bx bx-coin-stack me-1'></i>Nouvel achat</a>
                @endcan
                @can('ventes.creer')
                    <a href="{{ route('ventes.create') }}" class="btn btn-outline-primary"><i class='bx bx-transfer-alt me-1'></i>Nouvelle vente</a>
                @endcan
                @can('clients.voir')
                    <a href="{{ route('clients.index') }}" class="btn btn-outline-primary"><i class='bx bx-group me-1'></i>Clients</a>
                @endcan
                @can('achats.voir')
                    <a href="{{ route('stock.index') }}" class="btn btn-outline-primary"><i class='bx bx-cube me-1'></i>Stock</a>
                @endcan
                @can('audit.voir')
                    <a href="{{ route('audit.index') }}" class="btn btn-outline-primary"><i class='bx bx-list-check me-1'></i>Audit</a>
                @endcan
            </div>
        </div>
    @endif

    @if (auth()->user()->canAny(['achats.voir', 'ventes.voir']))
        <h6 class="mb-0 text-uppercase mt-2">Aperçu rapide</h6>
        <hr />
        <div class="row">
            @can('achats.voir')
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header card-header-brand d-flex align-items-center">
                            <h6 class="text-white mb-0"><i class='bx bx-coin-stack me-2'></i>DERNIERS ACHATS</h6>
                            <a href="{{ route('achats.index') }}" class="ms-auto btn btn-sm btn-light">Tout voir</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm dash-table">
                                <thead><tr><th>Date</th><th>Client</th><th class="text-end">Montant</th></tr></thead>
                                <tbody>
                                    @foreach ($derniersAchats as $a)
                                        <tr>
                                            <td>{{ $a->date_operation->format('d/m/Y') }}</td>
                                            <td>{{ $a->client->nom_complet ?? '—' }}</td>
                                            <td class="text-end">{{ $fmt($a->montant_total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endcan

            @can('ventes.voir')
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header card-header-brand d-flex align-items-center">
                            <h6 class="text-white mb-0"><i class='bx bx-transfer-alt me-2'></i>DERNIÈRES VENTES</h6>
                            <a href="{{ route('ventes.index') }}" class="ms-auto btn btn-sm btn-light">Tout voir</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm dash-table">
                                <thead><tr><th>Date</th><th>Client</th><th class="text-end">Montant</th></tr></thead>
                                <tbody>
                                    @foreach ($dernieresVentes as $v)
                                        <tr>
                                            <td>{{ $v->date_operation->format('d/m/Y') }}</td>
                                            <td>{{ $v->client->nom_complet ?? '—' }}</td>
                                            <td class="text-end">{{ $fmt($v->montant_total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $('.dash-table').DataTable({
            pageLength: 5,
            lengthChange: false,
            info: false,
            language: { search: 'Filtrer :', paginate: { previous: '‹', next: '›' }, zeroRecords: 'Rien à afficher', emptyTable: 'Aucune donnée' }
        });
    </script>
@endpush
