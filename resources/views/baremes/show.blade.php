@extends('layouts.admin')

@section('title', 'Barème — '.$version->libelle)

@php
    $fmt2 = fn ($v) => number_format((float) $v, 2, ',', ' ');
@endphp

@php
    $estAMoi = $version->bureau_id === auth()->user()->bureau_id;
    // "Modifier" reste possible même sur le socle commun / une autre version :
    // ça créera alors une copie propre à mon bureau plutôt que de l'éditer en
    // place, donc estUtilisee() ne bloque que si c'est déjà MA version.
    $modifiable = auth()->user()->can('bareme.gerer') && ($estAMoi ? ! $version->estUtilisee() : true);
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Barème</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('baremes.index') }}">Densité → Carat</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $version->libelle }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if (! $estAMoi)
        <div class="alert alert-info py-2">
            <i class='bx bx-info-circle me-1'></i>
            @if (is_null($version->bureau_id))
                Barème commun, partagé par tous les bureaux tant qu'ils n'ont pas créé leur propre version.
            @endif
            Toute modification créera automatiquement votre propre version — le barème d'origine ne sera jamais
            touché.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="text-white mb-0"><i class='bx bx-grid-alt me-2'></i>{{ strtoupper($version->libelle) }}</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $version->actif ? 'bg-success' : 'bg-secondary' }}">
                    {{ $version->actif ? 'Version active' : 'Version archivée' }}
                </span>
                <a href="{{ route('baremes.pdf', $version) }}" target="_blank" class="btn btn-light btn-sm">
                    <i class='bx bxs-file-pdf'></i> PDF
                </a>
                @if ($modifiable)
                    <a href="{{ route('baremes.edit', $version) }}" class="btn btn-light btn-sm">
                        <i class='bx bx-edit-alt'></i> Modifier
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4"><small class="text-muted">Créée le</small><div>{{ $version->created_at->format('d/m/Y à H:i') }}</div></div>
                <div class="col-md-4"><small class="text-muted">Créée par</small><div>{{ $version->user?->name ?? '—' }}</div></div>
                <div class="col-md-4"><small class="text-muted">Nombre de lignes</small><div>{{ $version->lignes->count() }}</div></div>
            </div>
            @if ($version->observations)
                <p><strong>Observations :</strong> {{ $version->observations }}</p>
            @endif

            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>DENSITÉ MIN</th>
                        <th>DENSITÉ MAX</th>
                        <th>CARAT</th>
                        @if ($modifiable) <th width="8%"></th> @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($version->lignes as $ligne)
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
                                    <input type="number" step="0.01" min="0" class="form-control" name="densite_min" id="edit_ligne_densite_min">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Densité max <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="densite_max" id="edit_ligne_densite_max">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Carat <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="carat" id="edit_ligne_carat">
                                </div>
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
    @endif
@endsection

@push('scripts')
    <script>
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
