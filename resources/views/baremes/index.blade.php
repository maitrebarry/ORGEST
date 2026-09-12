@extends('layouts.admin')

@section('title', 'Barème')

@php
    $fmt2 = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $estAMoi = $versionActive && $versionActive->bureau_id === auth()->user()->bureau_id;
    // "Modifier" reste possible même sur le socle commun : ça créera une
    // copie propre à mon bureau plutôt que de l'éditer en place (cf.
    // BaremeController::update()), donc estUtilisee() ne bloque que si
    // c'est déjà MA version.
    $modifiable = $versionActive && auth()->user()->can('bareme.gerer') && ($estAMoi ? ! $versionActive->estUtilisee() : true);
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Barème</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Densité → Carat</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex gap-2">
            @if ($versionActive)
                <a href="{{ route('baremes.pdf.active') }}" target="_blank" class="btn btn-secondary">
                    <i class='bx bxs-file-pdf'></i> Exporter en PDF
                </a>
            @endif
            @can('bareme.gerer')
                <a href="{{ route('baremes.create') }}" class="btn btn-primary">
                    <i class='bx bxs-plus-square'></i> Nouvelle version
                </a>
            @endcan
        </div>
    </div>
    <hr />

    @if (! $versionActive)
        <div class="alert alert-warning">
            <i class='bx bx-error-circle me-1'></i>
            Aucune version du barème n'est active pour le moment. Les opérations d'achat/vente ne pourront pas
            déterminer de carat tant qu'une version n'aura pas été créée.
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="text-white mb-0"><i class='bx bx-grid-alt me-2'></i>VERSION ACTIVE
                        @if ($versionActive)
                            — {{ $versionActive->libelle }}
                        @endif
                    </h6>
                    @if ($versionActive)
                        <span class="badge bg-light text-dark">{{ $versionActive->lignes->count() }} ligne(s)</span>
                        @if ($modifiable)
                            <a href="{{ route('baremes.edit', $versionActive) }}" class="btn btn-light btn-sm">
                                <i class='bx bx-edit-alt'></i> Modifier
                            </a>
                        @endif
                    @endif
                </div>
                <div class="card-body">
                    @if ($versionActive)
                        <table id="bareme-table" class="table table-sm">
                            <thead>
                                <tr>
                                    <th>DENSITÉ MIN</th>
                                    <th>DENSITÉ MAX</th>
                                    <th>CARAT</th>
                                    @if ($modifiable)
                                        <th width="8%"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($versionActive->lignes as $ligne)
                                    <tr>
                                        <td>{{ $fmt2($ligne->densite_min) }}</td>
                                        <td>{{ $fmt2($ligne->densite_max) }}</td>
                                        <td><strong>{{ $fmt2($ligne->carat) }}</strong></td>
                                        @if ($modifiable)
                                            <td class="text-center">
                                                <a href="javascript:;" class="btn btn-success btn-sm edit-ligne-button"
                                                   data-bs-toggle="modal" data-bs-target="#editLigneModal"
                                                   data-url="{{ route('baremes.lignes.update', $ligne) }}"
                                                   data-densite_min="{{ $ligne->densite_min }}"
                                                   data-densite_max="{{ $ligne->densite_max }}"
                                                   data-carat="{{ $ligne->carat }}"
                                                   title="Modifier cette ligne">
                                                    <i class='bx bx-edit-alt'></i>
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted text-center py-4 mb-0">Aucune donnée.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header card-header-brand">
                    <h6 class="text-white mb-0"><i class='bx bx-history me-2'></i>HISTORIQUE DES VERSIONS</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse ($versions as $version)
                            <a href="{{ route('baremes.show', $version) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $version->libelle }}</div>
                                    <small class="text-muted">
                                        {{ $version->created_at->format('d/m/Y à H:i') }}
                                        @if ($version->user) · {{ $version->user->name }} @endif
                                    </small>
                                </div>
                                <span class="badge {{ $version->actif ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $version->actif ? 'Active' : $version->lignes_count.' lignes' }}
                                </span>
                            </a>
                        @empty
                            <p class="text-muted text-center py-4 mb-0">Aucune version créée.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($modifiable)
        <div class="modal fade" id="editLigneModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="editLigneForm" action="">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier la ligne du barème</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Densité min <span class="text-danger">*</span></label>
                                    <input type="text" inputmode="decimal" class="form-control" name="densite_min" id="edit_ligne_densite_min">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Densité max <span class="text-danger">*</span></label>
                                    <input type="text" inputmode="decimal" class="form-control" name="densite_max" id="edit_ligne_densite_max">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Carat <span class="text-danger">*</span></label>
                                    <input type="text" inputmode="decimal" class="form-control" name="carat" id="edit_ligne_carat">
                                </div>
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger py-2 mb-0">
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
    @endif
@endsection

@push('scripts')
    <script>
        if ($('#bareme-table').length) {
            $('#bareme-table').DataTable({ pageLength: 25, order: [[0, 'asc']] });
        }

        $(document).on('click', '.edit-ligne-button', function () {
            const data = $(this).data();
            $('#editLigneForm').attr('action', data.url);
            $('#edit_ligne_densite_min').val(data.densite_min);
            $('#edit_ligne_densite_max').val(data.densite_max);
            $('#edit_ligne_carat').val(data.carat);
        });

        @if ($errors->any())
            window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editLigneModal')).show());
        @endif
    </script>
@endpush
