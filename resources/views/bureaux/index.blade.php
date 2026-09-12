@extends('layouts.admin')

@section('title', 'Bureaux')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bureaux</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBureauModal">
                <i class='bx bxs-plus-square'></i> Nouveau bureau
            </button>
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-lg-4">
            @include('configuration._menu')
        </div>

        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header card-header-brand">
                    <h6 class="text-center text-white mb-0">LISTE DES BUREAUX</h6>
                </div>
                <div class="card-body">
                    <table id="bureaux-table" class="table">
                        <thead>
                            <tr>
                                <th>LOGO</th>
                                <th>NOM</th>
                                <th>PROPRIÉTAIRE</th>
                                <th>TÉLÉPHONE</th>
                                <th>STATUT</th>
                                <th width="10%">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bureaux as $bureau)
                                <tr>
                                    <td>
                                        @if ($bureau->logo_url)
                                            <img src="{{ $bureau->logo_url }}" alt="Logo" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $bureau->nom }}</td>
                                    <td>{{ $bureau->proprietaire?->name ?? '—' }} @if($bureau->proprietaire)<br><small class="text-muted">{{ $bureau->proprietaire->phone }}</small>@endif</td>
                                    <td>{{ $bureau->telephone ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $bureau->actif ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $bureau->actif ? 'Actif' : 'Désactivé' }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('bureaux.edit', $bureau) }}" class="btn btn-success btn-sm" title="Modifier">
                                            <i class='bx bx-edit-alt'></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addBureauModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('bureaux.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau bureau</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            <i class='bx bx-info-circle me-1'></i>
                            Créer un bureau crée aussi, dans le même geste, le compte de son <strong>propriétaire</strong>.
                        </div>

                        <h6 class="fw-bold text-muted text-uppercase small mb-2">Le bureau</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom du bureau <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nom" value="{{ old('nom') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo</label>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Adresse</label>
                                <input type="text" class="form-control" name="adresse" value="{{ old('adresse') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="telephone" value="{{ old('telephone') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" name="email" value="{{ old('email') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pays <span class="text-danger">*</span></label>
                                <select class="single-select form-select" name="pays" id="paysBureau">
                                    @foreach (array_keys(config('pays_devises')) as $pays)
                                        <option value="{{ $pays }}" @selected(old('pays', 'Mali') === $pays)>{{ $pays }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Devise appliquée</label>
                                <input type="text" class="form-control" id="deviseApercu" disabled>
                                <small class="text-muted">Déduite automatiquement du pays — utilisée sur toutes les factures et écrans de ce bureau.</small>
                            </div>
                        </div>

                        <h6 class="fw-bold text-muted text-uppercase small mb-2 mt-3">Son propriétaire</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="proprietaire_nom" value="{{ old('proprietaire_nom') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="proprietaire_telephone" value="{{ old('proprietaire_telephone') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="proprietaire_password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="proprietaire_password_confirmation">
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
                        <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Créer le bureau</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#bureaux-table').DataTable({ order: [[1, 'asc']] });
        document.querySelectorAll('#bureaux-table [title]').forEach(el => new bootstrap.Tooltip(el));

        const paysDevises = @json(config('pays_devises'));

        function actualiserApercuDevise() {
            const pays = document.getElementById('paysBureau').value;
            const devise = paysDevises[pays];
            document.getElementById('deviseApercu').value = devise ? devise.symbole + ' (' + devise.code + ')' : '—';
        }

        document.getElementById('paysBureau').addEventListener('change', actualiserApercuDevise);
        actualiserApercuDevise();

        @if ($errors->any())
            var addBureauModal = new bootstrap.Modal(document.getElementById('addBureauModal'));
            addBureauModal.show();
        @endif
    </script>
@endpush
