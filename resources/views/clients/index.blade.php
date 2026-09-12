@extends('layouts.admin')

@section('title', 'Clients')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Clients</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des clients</li>
                </ol>
            </nav>
        </div>
        @can('clients.creer')
            <div class="ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class='bx bxs-plus-square'></i> Client
                </button>
            </div>
        @endcan
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-group me-2'></i>LISTE DES CLIENTS</h6>
        </div>
        <div class="card-body">
            <table id="clients-table" class="table">
                <thead>
                    <tr>
                        <th>PRENOM ET NOM</th>
                        <th>TELEPHONE</th>
                        <th>ADRESSE</th>
                        <th>STATUT</th>
                        <th width="15%">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                        <tr>
                            <td>{{ $client->nom_complet }}</td>
                            <td>{{ $client->telephone ?? '—' }}</td>
                            <td>{{ $client->adresse ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $client->actif ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $client->actif ? 'Actif' : 'Désactivé' }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                @can('clients.modifier')
                                    <a href="javascript:;" class="btn btn-success btn-sm edit-client-button"
                                       data-bs-toggle="modal" data-bs-target="#editClientModal"
                                       data-url="{{ route('clients.update', $client) }}"
                                       data-nom="{{ $client->nom }}"
                                       data-prenom="{{ $client->prenom }}"
                                       data-telephone="{{ $client->telephone }}"
                                       data-adresse="{{ $client->adresse }}"
                                       data-actif="{{ $client->actif ? 1 : 0 }}"
                                       title="Modifier">
                                        <i class='bx bx-edit-alt'></i>
                                    </a>
                                @endcan
                                @can('clients.supprimer')
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline delete-client-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Supprimer">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @can('clients.creer')
        <div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('clients.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Nouveau client</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" class="form-control" name="prenom" value="{{ old('prenom') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" value="{{ old('nom') }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input type="text" class="form-control" name="telephone" value="{{ old('telephone') }}" placeholder="70000000">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" class="form-control" name="adresse" value="{{ old('adresse') }}">
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
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('clients.modifier')
        <div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="editClientForm" action="">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier le client</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" class="form-control" name="prenom" id="edit_prenom">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom" id="edit_nom">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input type="text" class="form-control" name="telephone" id="edit_telephone">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" class="form-control" name="adresse" id="edit_adresse">
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="actif" value="1" id="edit_actif">
                                <label class="form-check-label" for="edit_actif">Client actif</label>
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

    @if ($errors->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addClientModal')).show());
        </script>
    @endif
@endsection

@push('scripts')
    <script>
        $('#clients-table').DataTable();

        document.querySelectorAll('#clients-table [title]').forEach(el => new bootstrap.Tooltip(el));

        $(document).on('click', '.edit-client-button', function () {
            const data = $(this).data();
            $('#editClientForm').attr('action', data.url);
            $('#edit_nom').val(data.nom);
            $('#edit_prenom').val(data.prenom);
            $('#edit_telephone').val(data.telephone);
            $('#edit_adresse').val(data.adresse);
            $('#edit_actif').prop('checked', data.actif == 1);
        });

        $(document).on('submit', '.delete-client-form', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: 'Ce client sera supprimé.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    </script>
@endpush
