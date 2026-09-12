@extends('layouts.admin')

@section('title', 'Permissions')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Permissions</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-lg-4">
            @include('configuration._menu')
        </div>

        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 text-white"><i class='bx bx-shield-alt-2 me-2'></i>RÉFÉRENTIEL DES PERMISSIONS</h6>
                    <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
                        <i class='bx bx-plus'></i> Ajouter
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="position-relative">
                            <input type="text" id="permission-search" class="form-control ps-5"
                                   placeholder="Rechercher une permission...">
                            <i class='bx bx-search position-absolute top-50 translate-middle-y' style="left: .75rem; color: #94a3b8;"></i>
                        </div>
                    </div>
                    <div id="permission-groups">
                        @include('permissions._groups')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="card-header card-header-brand text-white">
                    <h5 class="modal-title mb-0"><i class='bx bx-shield-alt-2 me-2'></i>Nouvelle permission</h5>
                </div>
                <form method="POST" action="{{ route('permissions.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nom de la permission</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" placeholder="Ex: chauffeurs.voir">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Recommandé : minuscules, format module.action (ex: vehicules.creer).</small>
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

    @if ($errors->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addPermissionModal')).show());
        </script>
    @endif
@endsection

@push('scripts')
    <script>
        let searchTimer = null;

        $('#permission-search').on('input', function () {
            const term = $(this).val();

            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                $.ajax({
                    url: '{{ route('permissions.index') }}',
                    method: 'GET',
                    data: { search: term },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (html) {
                        $('#permission-groups').html(html);
                    },
                });
            }, 300);
        });
    </script>
@endpush
