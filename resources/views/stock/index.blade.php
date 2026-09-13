@extends('layouts.admin')

@section('title', 'Stock')

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Stock</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Stock</li>
                </ol>
            </nav>
        </div>
        @can('ventes.creer')
            <div class="ms-auto">
                <a href="{{ route('ventes.create') }}" class="btn btn-primary" @disabled($barres->isEmpty())>
                    <i class='bx bxs-plus-square'></i> Nouvelle vente
                </a>
            </div>
        @endcan
    </div>
    <hr />

    <div class="row mb-3">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <small class="text-white-50">Barres en stock</small>
                    <div class="fs-4 fw-bold">{{ $barres->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Poids total</small>
                    <div class="fs-4 fw-bold">{{ number_format($poidsTotal, 3, ',', ' ') }} g</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Valeur d'achat du stock</small>
                    <div class="fs-4 fw-bold">{{ $fmt($montantTotal) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-cube me-2'></i>BARRES DISPONIBLES</h6>
        </div>
        <div class="card-body">
            @if ($barres->isEmpty())
                <p class="text-muted text-center py-4 mb-0">Aucune barre en stock pour le moment.</p>
            @else
                <table id="stock-table" class="table">
                    <thead>
                        <tr>
                            <th>ORIGINE</th>
                            <th>DATE</th>
                            <th>CLIENT</th>
                            <th>POIDS (g)</th>
                            <th>CARAT</th>
                            <th class="text-end">PRIX UNITAIRE (ACHAT)</th>
                            <th class="text-end">MONTANT (ACHAT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($barres as $barre)
                            <tr>
                                @if ($barre->operation)
                                    <td><a href="{{ route('achats.show', $barre->operation) }}">{{ $barre->operation->numero }}</a> (n°{{ $barre->numero_barre }})</td>
                                    <td>{{ $barre->operation->date_operation->format('d/m/Y') }}</td>
                                    <td>{{ $barre->operation->client->nom_complet }}</td>
                                @else
                                    <td>
                                        <a href="{{ route('credits.show', $barre->remboursementCredit->credit) }}">{{ $barre->remboursementCredit->numero }}</a>
                                        <span class="badge bg-info-subtle text-info-emphasis">Remb. crédit</span>
                                    </td>
                                    <td>{{ $barre->remboursementCredit->date_remboursement->format('d/m/Y') }}</td>
                                    <td>{{ $barre->remboursementCredit->credit->client->nom_complet }}</td>
                                @endif
                                <td>{{ number_format($barre->poids, 3, ',', ' ') }}</td>
                                <td><strong>{{ number_format($barre->carat, 2, ',', ' ') }}</strong></td>
                                <td class="text-end">{{ $fmt($barre->prix_unitaire) }}</td>
                                <td class="text-end">{{ $fmt($barre->montant) }}</td>
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
        if ($('#stock-table').length) {
            $('#stock-table').DataTable({ order: [[1, 'asc']] });
        }
    </script>
@endpush
