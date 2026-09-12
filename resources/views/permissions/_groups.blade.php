@forelse ($grouped as $module => $permissions)
    <div class="mb-4">
        <h6 class="text-uppercase text-muted border-bottom pb-2 mb-2">
            <i class='bx bx-folder me-1'></i>{{ $module }}
            <span class="badge bg-light text-dark">{{ $permissions->count() }}</span>
        </h6>
        <table class="table table-sm mb-0">
            <tbody>
                @foreach ($permissions as $permission)
                    <tr>
                        <td width="8%" class="text-muted">{{ $permission->id }}</td>
                        <td class="fw-bold">{{ $permission->name }}</td>
                        <td class="text-end">
                            <span class="badge bg-light text-dark border">{{ $permission->users_count }} utilisateur(s)</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p class="text-muted text-center py-4 mb-0">Aucune permission trouvée.</p>
@endforelse
