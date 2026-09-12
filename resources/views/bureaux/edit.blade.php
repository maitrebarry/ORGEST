@extends('layouts.admin')

@section('title', 'Modifier le bureau')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('bureaux.index') }}">Bureaux</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier « {{ $bureau->nom }} »</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3">
            @if ($bureau->logo_url)
                <img src="{{ $bureau->logo_url }}" alt="Logo" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
            @endif
            <div>
                <div class="fw-bold">Propriétaire : {{ $bureau->proprietaire?->name ?? '—' }}</div>
                <div class="text-muted small">{{ $bureau->proprietaire?->phone }}</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('bureaux.update', $bureau) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-store me-2'></i>{{ strtoupper($bureau->nom) }}</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom du bureau <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nom') is-invalid @enderror" name="nom" value="{{ old('nom', $bureau->nom) }}">
                        @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Remplacer le logo</label>
                        <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept="image/*">
                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-control" name="adresse" value="{{ old('adresse', $bureau->adresse) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="text" class="form-control" name="telephone" value="{{ old('telephone', $bureau->telephone) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" name="email" value="{{ old('email', $bureau->email) }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pays <span class="text-danger">*</span></label>
                        <select class="single-select form-select" name="pays">
                            @foreach (array_keys(config('pays_devises')) as $pays)
                                <option value="{{ $pays }}" @selected(old('pays', $bureau->pays) === $pays)>{{ $pays }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Détermine automatiquement la devise ({{ $bureau->devise_symbole }}) utilisée sur toutes les factures et écrans de ce bureau.</small>
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="actif" value="1" id="actifSwitch" @checked(old('actif', $bureau->actif))>
                    <label class="form-check-label" for="actifSwitch">Bureau actif</label>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('bureaux.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary px-4"><i class='bx bx-save me-1'></i>Enregistrer</button>
        </div>
    </form>
@endsection
