@extends('layouts.admin')

@section('title', "Achats d'or")

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
    $statutBadges = ['validee' => 'bg-success', 'annulee' => 'bg-danger'];
    $badgePaiement = function ($operation) {
        if ((float) $operation->montant_paye <= 0) {
            return ['bg-danger', 'Non payé'];
        }
        if (bccomp((string) $operation->montant_paye, (string) $operation->montant_total, 2) < 0) {
            return ['bg-warning text-dark', 'Partiellement payé'];
        }
        return ['bg-success', 'Payé'];
    };
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Achats d'or</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des achats</li>
                </ol>
            </nav>
        </div>
        @can('achats.creer')
            <div class="ms-auto">
                <a href="{{ route('achats.create') }}" class="btn btn-primary">
                    <i class='bx bxs-plus-square'></i> Nouvel achat
                </a>
            </div>
        @endcan
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-coin-stack me-2'></i>LISTE DES ACHATS</h6>
        </div>
        <div class="card-body">
            <table id="achats-table" class="table">
                <thead>
                    <tr>
                        <th>N° OPÉRATION</th>
                        <th>DATE</th>
                        <th>CLIENT</th>
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
                                <a href="{{ route('achats.show', $operation) }}" class="btn btn-primary btn-sm" title="Voir">
                                    <i class='bx bx-show'></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" title="Imprimer la facture" onclick="imprimerFacture('{{ route('achats.pdf', $operation) }}')">
                                    <i class='bx bx-printer'></i>
                                </button>
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
        $('#achats-table').DataTable({ order: [[1, 'desc']] });
        document.querySelectorAll('#achats-table [title]').forEach(el => new bootstrap.Tooltip(el));
    </script>
@endpush
