@extends('layouts.admin')

@section('title', 'Mon profil')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Profil</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mon profil</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header card-header-brand">
                    <h6 class="text-center text-white mb-0">INFORMATIONS DU PROFIL</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="text-center mb-4">
                            <img id="photo-preview"
                                 src="{{ $user->photo_url }}"
                                 class="rounded-circle border"
                                 style="width: 110px; height: 110px; object-fit: cover; display: {{ $user->photo_url ? 'inline-block' : 'none' }};"
                                 alt="Photo de profil">
                            <div id="photo-fallback"
                                 class="rounded-circle border bg-primary text-white align-items-center justify-content-center mx-auto"
                                 style="width: 110px; height: 110px; font-size: 2.5rem; display: {{ $user->photo_url ? 'none' : 'inline-flex' }};">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="mt-3">
                                <label for="photo" class="btn btn-outline-secondary btn-sm">
                                    <i class='bx bx-camera me-1'></i> Changer la photo
                                </label>
                                <input type="file" id="photo" name="photo" accept="image/*" class="d-none @error('photo') is-invalid @enderror">
                                @error('photo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text">JPG ou PNG, 2 Mo maximum.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Numéro de téléphone <span class="text-danger">*</span></label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header card-header-brand">
                    <h6 class="text-center text-white mb-0">CHANGER LE MOT DE PASSE</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" id="current_password" name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password" tabindex="-1" aria-label="Afficher le mot de passe"><i class='bx bx-show'></i></button>
                            </div>
                            @error('current_password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password" tabindex="-1" aria-label="Afficher le mot de passe"><i class='bx bx-show'></i></button>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_confirmation" tabindex="-1" aria-label="Afficher le mot de passe"><i class='bx bx-show'></i></button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Mettre à jour le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#photo').on('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                $('#photo-fallback').hide();
                $('#photo-preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        });

        // Afficher / masquer les mots de passe (icône œil)
        document.querySelectorAll('.toggle-password').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.target);
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bx-show', 'bx-hide');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bx-hide', 'bx-show');
                }
            });
        });
    </script>
@endpush
