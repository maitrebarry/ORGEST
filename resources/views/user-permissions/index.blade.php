@extends('layouts.admin')

@section('title', 'Assigner permissions')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Assigner permissions</li>
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
            <div class="card mb-4">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 text-white"><i class='bx bx-user-check me-2'></i>ASSIGNATION DE PERMISSIONS</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('user-permissions.index') }}" id="selectUserForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Utilisateur</label>
                                <input type="text" id="userFilter" class="form-control mb-2" placeholder="Filtrer les utilisateurs...">
                                <select name="user" id="userSelect" class="form-select">
                                    <option value="">Choisir un utilisateur</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected($selectedUser?->id === $user->id)>
                                            {{ $user->name }} — {{ $user->phone }} — {{ ucfirst(str_replace('_', ' ', $user->roles->first()?->name ?? 'aucun rôle')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    @if ($selectedUser)
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4 pt-3 border-top">
                            <div>
                                <h5 class="fw-bold mb-1">{{ $selectedUser->name }}</h5>
                                <div class="text-muted">
                                    {{ $selectedUser->phone }} — {{ ucfirst(str_replace('_', ' ', $selectedUser->roles->first()?->name ?? 'Aucun rôle')) }}
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary fs-6 px-3 py-2">{{ count($userPermissionNames) }} / {{ $totalPermissions }} cochées</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($selectedUser)
                <form method="POST" action="{{ route('user-permissions.update', $selectedUser) }}">
                    @csrf
                    @method('PUT')

                    <div class="card mb-4">
                        <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="selectAllPermissions">
                                <label class="form-check-label fw-bold" for="selectAllPermissions">Tout cocher/décocher</label>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class='bx bx-save me-2'></i>Enregistrer</button>
                        </div>
                    </div>

                    <div class="row g-3">
                        @foreach ($groupedPermissions as $module => $permissions)
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header card-header-brand d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="fw-bold text-white">
                                            <i class='bx bx-folder me-2'></i>{{ ucfirst($module) }}
                                            <span class="badge bg-light text-dark ms-2">{{ count($permissions) }}</span>
                                        </div>
                                        <div class="form-check m-0">
                                            <input class="form-check-input module-checkbox" type="checkbox" data-module="{{ $module }}">
                                            <label class="form-check-label text-white">Tout le module</label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            @foreach ($permissions as $permission)
                                                <div class="col-md-4 col-xl-3">
                                                    <div class="form-check border rounded p-2">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                               name="permissions[]" value="{{ $permission->name }}"
                                                               id="perm_{{ $permission->id }}" data-module="{{ $module }}"
                                                               @checked(in_array($permission->name, $userPermissionNames, true))>
                                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary px-5"><i class='bx bx-save me-2'></i>Enregistrer</button>
                    </div>
                </form>
            @else
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class='bx bx-user-check d-block mb-3' style="font-size: 3rem;"></i>
                        Sélectionnez un utilisateur pour charger ses permissions.
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#userSelect').on('change', function () {
            $('#selectUserForm').submit();
        });

        $('#userFilter').on('input', function () {
            const term = $(this).val().toLowerCase();
            $('#userSelect option').each(function (index) {
                if (index === 0) return;
                $(this).toggle($(this).text().toLowerCase().includes(term));
            });
        });

        function syncModule(module) {
            const items = $('.permission-checkbox[data-module="' + module + '"]');
            const moduleBox = $('.module-checkbox[data-module="' + module + '"]');
            const checkedCount = items.filter(':checked').length;
            moduleBox.prop('checked', checkedCount === items.length && items.length > 0);
        }

        function syncAll() {
            const total = $('.permission-checkbox').length;
            const checked = $('.permission-checkbox:checked').length;
            $('#selectAllPermissions').prop('checked', total > 0 && checked === total);
            $('.module-checkbox').each(function () {
                syncModule($(this).data('module'));
            });
        }

        $('#selectAllPermissions').on('change', function () {
            $('.permission-checkbox').prop('checked', $(this).is(':checked'));
            syncAll();
        });

        $('.module-checkbox').on('change', function () {
            $('.permission-checkbox[data-module="' + $(this).data('module') + '"]').prop('checked', $(this).is(':checked'));
            syncAll();
        });

        $('.permission-checkbox').on('change', syncAll);
        syncAll();
    </script>
@endpush
