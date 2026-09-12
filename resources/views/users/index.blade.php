@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste Utilisateur</li>
                </ol>
            </nav>
        </div>
        @can('utilisateurs.creer')
            <div class="ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class='bx bxs-plus-square'></i> Utilisateur
                </button>
            </div>
        @endcan
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-lg-4">
            @include('configuration._menu')
        </div>

        <div class="col-12 col-lg-8">
        <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-center text-white mb-0">LISTE DES UTILISATEURS</h6>
        </div>
        <div class="card-body">
            <table id="users-table" class="table">
                <thead>
                    <tr>
                        <th>NOM ET PRENOM</th>
                        <th>TELEPHONE</th>
                        <th>ROLE</th>
                        <th>STATUT</th>
                        <th width="15%">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $user->roles->pluck('name')->join(', '))) }}</td>
                            <td>
                                <span class="badge {{ $user->actif ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $user->actif ? 'Actif' : 'Désactivé' }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                @can('utilisateurs.modifier')
                                    @if (! $user->hasRole('superadmin') || auth()->user()->hasRole('superadmin'))
                                        <a href="javascript:;" class="btn btn-success btn-sm edit-user-button"
                                           data-bs-toggle="modal" data-bs-target="#editUserModal"
                                           data-url="{{ route('users.update', $user) }}"
                                           data-name="{{ $user->name }}"
                                           data-phone="{{ $user->phone }}"
                                           data-role="{{ $user->roles->first()?->name }}"
                                           title="Modifier">
                                            <i class='bx bx-edit-alt'></i>
                                        </a>
                                    @endif
                                @endcan
                                @can('utilisateurs.desactiver')
                                    @if ((! $user->hasRole('superadmin') || auth()->user()->hasRole('superadmin')) && $user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.toggle-actif', $user) }}" class="d-inline toggle-actif-form">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-{{ $user->actif ? 'danger' : 'primary' }} btn-sm"
                                                    title="{{ $user->actif ? 'Désactiver' : 'Activer' }}">
                                                <i class="bx {{ $user->actif ? 'bx-block' : 'bx-check-circle' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                @can('utilisateurs.supprimer')
                                    @if (! $user->hasRole('superadmin') && $user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline delete-user-form" data-name="{{ $user->name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-dark btn-sm" title="Supprimer définitivement">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
        </div>
    </div>

    @can('utilisateurs.creer')
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title text-center">Nouveau Utilisateur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="{{ old('name') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmer mot de passe <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password_confirmation">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Rôle <span class="text-danger">*</span></label>
                                    <select class="single-select form-select" name="role">
                                        <option value="">-- Choisir --</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger mt-3 py-2 mb-0">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('utilisateurs.modifier')
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="editUserForm" action="">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title text-center">Modifier l'utilisateur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" id="edit_name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone" id="edit_phone">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" name="password">
                                    <small class="text-muted">Laisser vide pour ne pas changer</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmer mot de passe</label>
                                    <input type="password" class="form-control" name="password_confirmation">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Rôle <span class="text-danger">*</span></label>
                                    <select class="single-select form-select" name="role" id="edit_role">
                                        @can('utilisateurs.modifier')
                                            @if (auth()->user()->hasRole('superadmin'))
                                                <option value="superadmin">Superadmin</option>
                                            @endif
                                        @endcan
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                                        @endforeach
                                    </select>
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
    @endcan
@endsection

@push('scripts')
    <script>
        $('#users-table').DataTable({ order: [[0, 'asc']] });

        document.querySelectorAll('#users-table [title]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });

        $(document).on('click', '.edit-user-button', function () {
            $('#editUserForm').attr('action', $(this).data('url'));
            $('#edit_name').val($(this).data('name'));
            $('#edit_phone').val($(this).data('phone'));
            $('#edit_role').val($(this).data('role'));
        });

        $(document).on('submit', '.toggle-actif-form', function (e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Êtes-vous sûr ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui',
                cancelButtonText: 'Annuler',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Suppression définitive : il faut saisir le nom exact de l'utilisateur
        $(document).on('submit', '.delete-user-form', function (e) {
            e.preventDefault();
            const form = this;
            const name = $(this).data('name');

            Swal.fire({
                title: 'Supprimer définitivement ?',
                html: 'Cette action supprimera définitivement le compte de <b>' + $('<div>').text(name).html() + '</b>. '
                    + 'C\'est irréversible.<br><br>'
                    + 'Pour confirmer, saisissez le nom exact :',
                input: 'text',
                inputPlaceholder: name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Supprimer',
                cancelButtonText: 'Annuler',
                preConfirm: (value) => {
                    if ((value || '').trim() !== name) {
                        Swal.showValidationMessage('Le nom saisi ne correspond pas.');
                        return false;
                    }
                    return true;
                },
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });

        @if ($errors->any())
            var addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
            addUserModal.show();
        @endif
    </script>
@endpush
