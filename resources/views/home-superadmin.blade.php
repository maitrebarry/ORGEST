@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Tableau de bord</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><i class="bx bx-home-alt"></i></li>
                    <li class="breadcrumb-item active" aria-current="page">Supervision plateforme</li>
                </ol>
            </nav>
        </div>
    </div>

    <h6 class="mb-0 text-uppercase">Vue d'ensemble de la plateforme</h6>
    <hr />
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3">
        <div class="col mb-3">
            <div class="card radius-10 bg-primary bg-gradient">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-white">Bureaux</p>
                            <h4 class="my-1 text-white">{{ $nbBureaux }}</h4>
                            <p class="mb-0 text-white small">{{ $nbBureauxActifs }} actif(s)</p>
                        </div>
                        <div class="text-white ms-auto font-35"><i class='bx bx-buildings'></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col mb-3">
            <div class="card radius-10 bg-warning bg-gradient">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-dark">Propriétaires</p>
                            <h4 class="text-dark my-1">{{ $nbProprietaires }}</h4>
                        </div>
                        <div class="text-dark ms-auto font-35"><i class='bx bx-user-pin'></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col mb-3">
            <div class="card radius-10 bg-info bg-gradient">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-white">Gérants</p>
                            <h4 class="my-1 text-white">{{ $nbGerants }}</h4>
                        </div>
                        <div class="text-white ms-auto font-35"><i class='bx bx-group'></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col mb-3">
            <div class="card radius-10 bg-success bg-gradient">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-white">Clients actifs</p>
                            <h4 class="my-1 text-white">{{ $nbClientsTotal }}</h4>
                            <p class="mb-0 text-white small">Tous bureaux confondus</p>
                        </div>
                        <div class="text-white ms-auto font-35"><i class='bx bx-user-check'></i></div>
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
                            <h4 class="my-1 text-white">{{ $bureaux->sum('achatsJour') }}</h4>
                            <p class="mb-0 text-white small">Tous bureaux confondus</p>
                        </div>
                        <div class="text-white ms-auto font-35"><i class='bx bx-coin-stack'></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col mb-3">
            <div class="card radius-10 bg-dark bg-gradient">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-0 text-white">Ventes aujourd'hui</p>
                            <h4 class="my-1 text-white">{{ $bureaux->sum('ventesJour') }}</h4>
                            <p class="mb-0 text-white small">Tous bureaux confondus</p>
                        </div>
                        <div class="text-white ms-auto font-35"><i class='bx bx-transfer-alt'></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('bureaux.gerer')
        <div class="card">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-bolt-circle me-2'></i>ACCÈS RAPIDES</h6>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('bureaux.index') }}" class="btn btn-outline-primary"><i class='bx bx-buildings me-1'></i>Gérer les bureaux</a>
            </div>
        </div>
    @endcan

    <h6 class="mb-0 text-uppercase mt-2">Bureaux</h6>
    <hr />
    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-buildings me-2'></i>LISTE DES BUREAUX</h6>
        </div>
        <div class="card-body">
            @if ($bureaux->isEmpty())
                <p class="text-muted text-center py-4 mb-0">Aucun bureau créé pour le moment.</p>
            @else
                <div class="table-responsive">
                    <table id="bureaux-dashboard-table" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>BUREAU</th>
                                <th>PROPRIÉTAIRE</th>
                                <th>PAYS</th>
                                <th>DEVISE</th>
                                <th>UTILISATEURS</th>
                                <th>CLIENTS ACTIFS</th>
                                <th>ACHATS AUJOURD'HUI</th>
                                <th>VENTES AUJOURD'HUI</th>
                                <th>STATUT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bureaux as $bureau)
                                <tr>
                                    <td>
                                        @if ($bureau->logo_url)
                                            <img src="{{ $bureau->logo_url }}" alt="" width="24" height="24" class="rounded-circle me-1" style="object-fit: cover;">
                                        @endif
                                        {{ $bureau->nom }}
                                    </td>
                                    <td>{{ $bureau->proprietaire?->name ?? '—' }}</td>
                                    <td>{{ $bureau->pays ?? '—' }}</td>
                                    <td>{{ $bureau->devise_symbole }}</td>
                                    <td>{{ $bureau->utilisateurs_count }}</td>
                                    <td>{{ $bureau->clientsActifs }}</td>
                                    <td>{{ $bureau->achatsJour }}</td>
                                    <td>{{ $bureau->ventesJour }}</td>
                                    <td><span class="badge {{ $bureau->actif ? 'bg-success' : 'bg-secondary' }}">{{ $bureau->actif ? 'Actif' : 'Désactivé' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        if ($('#bureaux-dashboard-table').length) {
            $('#bureaux-dashboard-table').DataTable({ order: [[0, 'asc']] });
        }
    </script>
@endpush
