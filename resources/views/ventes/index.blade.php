@extends('layouts.admin')

@section('title', "Ventes d'or")

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
    $statutBadges = ['validee' => 'bg-success', 'annulee' => 'bg-danger'];
    $badgePaiement = function ($operation) {
        if ((float) $operation->montant_paye <= 0) {
            return ['bg-danger', 'Non encaissé'];
        }
        if (bccomp((string) $operation->montant_paye, (string) $operation->montant_total, 2) < 0) {
            return ['bg-warning text-dark', 'Partiellement encaissé'];
        }
        return ['bg-success', 'Encaissé'];
    };
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Ventes d'or</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des ventes</li>
                </ol>
            </nav>
        </div>
        @can('ventes.creer')
            <div class="ms-auto">
                <a href="{{ route('ventes.create') }}" class="btn btn-primary">
                    <i class='bx bxs-plus-square'></i> Nouvelle vente
                </a>
            </div>
        @endcan
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-transfer-alt me-2'></i>LISTE DES VENTES</h6>
        </div>
        <div class="card-body">
            <table id="ventes-table" class="table">
                <thead>
                    <tr>
                        <th>N° OPÉRATION</th>
                        <th>DATE</th>
                        <th>CLIENT (ACHETEUR)</th>
                        <th>BARRES</th>
                        <th>MONTANT TOTAL</th>
                        <th>PAIEMENT</th>
                        <th>STATUT</th>
                        <th width="10%">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($operations as $operation)
                        <tr>
                            <td>{{ $operation->numero }}</td>
                            <td>{{ $operation->date_operation->format('d/m/Y') }}</td>
                            <td>{{ $operation->client->nom_complet }}</td>
                            <td>{{ $operation->barres_count }}</td>
                            <td>{{ $fmt($operation->montant_total) }}</td>
                            <td>
                                @php [$classe, $libelle] = $badgePaiement($operation); @endphp
                                <span class="badge {{ $classe }}">{{ $libelle }}</span>
                            </td>
                            <td><span class="badge {{ $statutBadges[$operation->statut] }}">{{ $operation->statut_libelle }}</span></td>
                            <td class="text-nowrap">
                                <a href="{{ route('ventes.show', $operation) }}" class="btn btn-primary btn-sm" title="Voir">
                                    <i class='bx bx-show'></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#ventes-table').DataTable({ order: [[1, 'desc']] });
        document.querySelectorAll('#ventes-table [title]').forEach(el => new bootstrap.Tooltip(el));
    </script>
@endpush
