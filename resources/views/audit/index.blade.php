@extends('layouts.admin')

@section('title', 'Audit')

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
    $badgesType = ['achat' => 'bg-danger', 'vente' => 'bg-success', 'mouvement' => 'bg-secondary'];
    $labelsType = ['achat' => 'Achat', 'vente' => 'Vente', 'mouvement' => 'Trésorerie'];
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Audit</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Journal d'activité</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('audit.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Du</label>
                    <input type="date" class="form-control" name="debut" value="{{ $debut->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Au</label>
                    <input type="date" class="form-control" name="fin" value="{{ $fin->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class='bx bx-filter-alt'></i> Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-6 mb-3">
        <div class="col mb-2">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Achats validés</small>
                    <div class="fw-bold">{{ $resume['nombreAchats'] }}</div>
                </div>
            </div>
        </div>
        <div class="col mb-2">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Poids acheté</small>
                    <div class="fw-bold">{{ number_format($resume['poidsAchete'], 3, ',', ' ') }} g</div>
                </div>
            </div>
        </div>
        <div class="col mb-2">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Montant achats</small>
                    <div class="fw-bold">{{ $fmt($resume['montantAchats']) }}</div>
                </div>
            </div>
        </div>
        <div class="col mb-2">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Ventes validées</small>
                    <div class="fw-bold">{{ $resume['nombreVentes'] }}</div>
                </div>
            </div>
        </div>
        <div class="col mb-2">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Poids vendu</small>
                    <div class="fw-bold">{{ number_format($resume['poidsVendu'], 3, ',', ' ') }} g</div>
                </div>
            </div>
        </div>
        <div class="col mb-2">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Montant ventes</small>
                    <div class="fw-bold">{{ $fmt($resume['montantVentes']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-list-check me-2'></i>JOURNAL D'ACTIVITÉ</h6>
        </div>
        <div class="card-body">
            @if ($evenements->isEmpty())
                <p class="text-muted text-center py-4 mb-0">Aucun événement sur cette période.</p>
            @else
                <table id="audit-table" class="table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>TYPE</th>
                            <th>DESCRIPTION</th>
                            <th>STATUT</th>
                            <th class="text-end">MONTANT</th>
                            <th>PAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($evenements as $evenement)
                            <tr>
                                <td>{{ $evenement['date']->format('d/m/Y H:i') }}</td>
                                <td><span class="badge {{ $badgesType[$evenement['type']] }}">{{ $labelsType[$evenement['type']] }}</span></td>
                                <td>{{ $evenement['libelle'] }}</td>
                                <td>{{ $evenement['statut'] ?? '—' }}</td>
                                <td class="text-end {{ $evenement['sens'] === 'entree' ? 'text-success' : 'text-danger' }}">
                                    {{ $evenement['sens'] === 'entree' ? '+' : '−' }}{{ $fmt($evenement['montant']) }}
                                </td>
                                <td>{{ $evenement['user'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-lock-alt me-2'></i>JOURNÉES CLÔTURÉES SUR LA PÉRIODE</h6>
        </div>
        <div class="card-body">
            @if ($journeesFermees->isEmpty())
                <p class="text-muted text-center py-4 mb-0">Aucune clôture sur cette période.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>OUVERTURE</th>
                            <th>FERMETURE</th>
                            <th>SOLDE THÉORIQUE</th>
                            <th>SOLDE PHYSIQUE</th>
                            <th>ÉCART</th>
                            <th>CLÔTURÉE PAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journeesFermees as $journee)
                            <tr>
                                <td>{{ $journee->date_ouverture->format('d/m/Y H:i') }}</td>
                                <td>{{ $journee->date_fermeture->format('d/m/Y H:i') }}</td>
                                <td>{{ $fmt($journee->solde_theorique_fermeture) }}</td>
                                <td>{{ $fmt($journee->solde_physique_fermeture) }}</td>
                                <td class="{{ (float) $journee->ecart_fermeture != 0 ? 'text-danger fw-bold' : '' }}">{{ $fmt($journee->ecart_fermeture) }}</td>
                                <td>{{ $journee->fermePar?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        if ($('#audit-table').length) {
            $('#audit-table').DataTable({ order: [[0, 'desc']] });
        }
    </script>
@endpush
