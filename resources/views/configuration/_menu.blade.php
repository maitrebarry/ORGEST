<div class="card">
    <div class="card-header card-header-brand text-white d-flex align-items-center gap-2">
        <i class='bx bx-cog'></i>
        <span class="fw-semibold">Configuration</span>
    </div>
    <div class="card-body">
        <div class="list-group list-group-flush">
            @can('utilisateurs.voir')
                <a href="{{ route('users.index') }}" class="list-group-item py-2 {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class='bx bx-user me-2'></i><span>Liste Utilisateur</span>
                </a>
            @endcan
            @role('superadmin')
                <a href="{{ route('permissions.index') }}" class="list-group-item py-2 {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                    <i class='bx bx-shield-alt-2 me-2'></i><span>Permissions</span>
                </a>
            @endrole
            @can('roles.gerer')
                <a href="{{ route('user-permissions.index') }}" class="list-group-item py-2 {{ request()->routeIs('user-permissions.*') ? 'active' : '' }}">
                    <i class='bx bx-user-check me-2'></i><span>Assigner permissions</span>
                </a>
            @endcan
            @can('bureaux.gerer')
                <a href="{{ route('bureaux.index') }}" class="list-group-item py-2 {{ request()->routeIs('bureaux.index', 'bureaux.edit') ? 'active' : '' }}">
                    <i class='bx bx-store me-2'></i><span>Bureaux</span>
                </a>
            @endcan
            @can('bureaux.logo')
                <button type="button" class="list-group-item py-2 text-start" data-bs-toggle="modal" data-bs-target="#monBureauModal">
                    <i class='bx bx-store me-2'></i><span>Mon bureau</span>
                </button>
            @endcan
        </div>
    </div>
</div>

@can('bureaux.logo')
    @php $monBureau = auth()->user()->bureau; @endphp
    <div class="modal fade" id="monBureauModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mon bureau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if (! $monBureau)
                        <div class="alert alert-warning mb-0">Votre compte n'est rattaché à aucun bureau.</div>
                    @else
                        <div class="alert alert-info py-2">
                            <i class='bx bx-info-circle me-1'></i>
                            Vous ne pouvez modifier que le <strong>logo</strong> de votre bureau. Les autres
                            informations sont gérées par l'administrateur de la plateforme.
                        </div>
                        <div class="row mb-3">
                            <div class="col-4"><small class="text-muted">Adresse</small><div>{{ $monBureau->adresse ?? '—' }}</div></div>
                            <div class="col-4"><small class="text-muted">Téléphone</small><div>{{ $monBureau->telephone ?? '—' }}</div></div>
                            <div class="col-4"><small class="text-muted">Email</small><div>{{ $monBureau->email ?? '—' }}</div></div>
                        </div>
                        <form method="POST" action="{{ route('bureaux.logo.update') }}" enctype="multipart/form-data" id="monBureauLogoForm" class="d-flex align-items-center gap-3">
                            @csrf
                            @if ($monBureau->logo_url)
                                <img id="logo-preview" src="{{ $monBureau->logo_url }}" alt="Logo" style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px;">
                            @else
                                <img id="logo-preview" src="" alt="Logo" style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px; display: none;">
                                <div id="logo-fallback" class="d-flex align-items-center justify-content-center bg-light" style="width: 90px; height: 90px; border-radius: 8px;">
                                    <i class='bx bx-image text-muted' style="font-size: 2rem;"></i>
                                </div>
                            @endif
                            <div>
                                <label for="monBureauLogoInput" class="btn btn-outline-secondary btn-sm">
                                    <i class='bx bx-upload me-1'></i> Choisir un nouveau logo
                                </label>
                                <input type="file" id="monBureauLogoInput" name="logo" accept="image/*" class="d-none">
                                @error('logo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </form>
                    @endif
                </div>
                @if ($monBureau)
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" form="monBureauLogoForm" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer le logo</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $('#monBureauLogoInput').on('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#logo-fallback').hide();
                    $('#logo-preview').attr('src', e.target.result).show();
                };
                reader.readAsDataURL(file);
            });

            @if ($errors->has('logo'))
                var monBureauModal = new bootstrap.Modal(document.getElementById('monBureauModal'));
                monBureauModal.show();
            @endif
        </script>
    @endpush
@endcan
