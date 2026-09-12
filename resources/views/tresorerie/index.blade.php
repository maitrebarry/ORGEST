@extends('layouts.admin')

@section('title', 'Trésorerie')

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 0, ',', ' ').' '.$devise;
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Trésorerie</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Fonds d'achat</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    @if ($journeeOuverte)
        @if ($journeeOuverte->estEnRupture())
            <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class='bx bx-error-circle fs-4'></i>
                <div>
                    <strong>Fonds épuisé.</strong> Le solde disponible est à {{ $fmt($journeeOuverte->soldeDisponible()) }} :
                    aucun paiement d'achat ne pourra être honoré tant qu'un approvisionnement n'aura pas été enregistré.
                </div>
            </div>
        @elseif ($journeeOuverte->soldeFaible())
            <div class="alert alert-warning d-flex align-items-center gap-2">
                <i class='bx bx-error fs-4'></i>
                <div>
                    <strong>Solde faible.</strong> Il ne reste que {{ $fmt($journeeOuverte->soldeDisponible()) }} disponible
                    (moins de 10% des entrées du jour) — pensez à demander un approvisionnement pour éviter une rupture.
                </div>
            </div>
        @endif

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 mb-2">
            <div class="col">
                <div class="card radius-10 bg-secondary bg-gradient">
                    <div class="card-body">
                        <p class="mb-0 text-white">Fonds initial</p>
                        <h5 class="my-1 text-white">{{ $fmt($journeeOuverte->fondsInitial()) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 bg-success bg-gradient">
                    <div class="card-body">
                        <p class="mb-0 text-white">Total entrées</p>
                        <h5 class="my-1 text-white">{{ $fmt($journeeOuverte->totalEntrees()) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 bg-danger bg-gradient">
                    <div class="card-body">
                        <p class="mb-0 text-white">Total sorties</p>
                        <h5 class="my-1 text-white">{{ $fmt($journeeOuverte->totalSorties()) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 bg-primary bg-gradient">
                    <div class="card-body">
                        <p class="mb-0 text-white">Solde disponible</p>
                        <h5 class="my-1 text-white">{{ $fmt($journeeOuverte->soldeDisponible()) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header card-header-brand d-flex align-items-center flex-wrap gap-2">
                <h6 class="text-white mb-0">
                    <i class='bx bx-money-withdraw me-2'></i>JOURNÉE OUVERTE — {{ $journeeOuverte->date_ouverture->format('d/m/Y à H:i') }}
                </h6>
                <div class="ms-auto d-flex gap-2">
                    @can('fonds.gerer')
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addApproModal">
                            <i class='bx bxs-plus-square'></i> Approvisionnement
                        </button>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addMouvementModal">
                            <i class='bx bx-transfer'></i> Autre mouvement
                        </button>
                        <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#fermerModal">
                            <i class='bx bx-lock-alt'></i> Clôturer la journée
                        </button>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    <i class='bx bx-info-circle'></i> Les paiements d'achats et encaissements de ventes alimentent
                    automatiquement cette journée — ils portent le libellé <span class="badge bg-light text-dark border">auto</span>.
                </p>
                <table id="mouvements-table" class="table">
                    <thead>
                        <tr>
                            <th>HEURE</th>
                            <th>NATURE</th>
                            <th>LIBELLÉ</th>
                            <th>MODE</th>
                            <th>ENTRÉE</th>
                            <th>SORTIE</th>
                            <th>PAR</th>
                            <th width="8%">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journeeOuverte->mouvements as $mouvement)
                            <tr>
                                <td>{{ $mouvement->date_mouvement->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge {{ $mouvement->sens === 'entree' ? 'bg-success' : 'bg-danger' }}">
                                        {{ $mouvement->nature_libelle }}
                                    </span>
                                </td>
                                <td>
                                    {{ $mouvement->libelle }}
                                    @if ($mouvement->estAutomatique())
                                        <span class="badge bg-light text-dark border" title="Généré automatiquement">auto</span>
                                    @endif
                                </td>
                                <td>{{ $mouvement->mode_paiement_libelle ?? '—' }}</td>
                                <td>{{ $mouvement->sens === 'entree' ? $fmt($mouvement->montant) : '—' }}</td>
                                <td>{{ $mouvement->sens === 'sortie' ? $fmt($mouvement->montant) : '—' }}</td>
                                <td>{{ $mouvement->user?->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    @can('fonds.gerer')
                                        @unless ($mouvement->estAutomatique())
                                            <form method="POST" action="{{ route('tresorerie.mouvement.destroy', $mouvement) }}" class="d-inline confirm-form" data-title="Supprimer ce mouvement ?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Supprimer"><i class='bx bx-trash'></i></button>
                                            </form>
                                        @endunless
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-lock-open-alt me-2'></i>AUCUNE JOURNÉE FINANCIÈRE OUVERTE</h6>
            </div>
            <div class="card-body">
                @can('fonds.gerer')
                    <p class="text-muted">Ouvrez une nouvelle journée pour commencer à enregistrer les fonds d'achat.</p>
                    <form method="POST" action="{{ route('tresorerie.ouvrir') }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Fonds initial ({{ $devise }}) <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric" data-montant class="form-control" name="montant_initial" value="{{ old('montant_initial', 0) }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Observations</label>
                            <input type="text" class="form-control" name="observations" value="{{ old('observations') }}" placeholder="Facultatif">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><i class='bx bx-lock-open-alt'></i> Ouvrir la journée</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted mb-0">Aucune journée financière n'est actuellement ouverte.</p>
                @endcan
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-history me-2'></i>HISTORIQUE DES JOURNÉES CLÔTURÉES</h6>
        </div>
        <div class="card-body">
            <table id="historique-table" class="table">
                <thead>
                    <tr>
                        <th>OUVERTURE</th>
                        <th>FERMETURE</th>
                        <th>SOLDE THÉORIQUE</th>
                        <th>SOLDE PHYSIQUE</th>
                        <th>ÉCART</th>
                        <th>OUVERTE PAR</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historique as $journee)
                        <tr>
                            <td>{{ $journee->date_ouverture->format('d/m/Y H:i') }}</td>
                            <td>{{ $journee->date_fermeture?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $fmt($journee->solde_theorique_fermeture) }}</td>
                            <td>{{ $fmt($journee->solde_physique_fermeture) }}</td>
                            <td class="{{ (float) $journee->ecart_fermeture != 0 ? 'text-danger fw-bold' : '' }}">{{ $fmt($journee->ecart_fermeture) }}</td>
                            <td>{{ $journee->ouvrePar?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($journeeOuverte)
        @can('fonds.gerer')
            <div class="modal fade" id="addApproModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('tresorerie.approvisionner', $journeeOuverte) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Nouvel approvisionnement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Montant ({{ $devise }}) <span class="text-danger">*</span></label>
                                    <input type="text" inputmode="numeric" data-montant class="form-control" name="montant">
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Libellé</label>
                                    <input type="text" class="form-control" name="libelle" placeholder="Ex : Renforcement">
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

            <div class="modal fade" id="addMouvementModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('tresorerie.mouvement', $journeeOuverte) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Nouveau mouvement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sens <span class="text-danger">*</span></label>
                                        <select class="form-select" name="sens">
                                            <option value="entree">Entrée</option>
                                            <option value="sortie">Sortie</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Montant ({{ $devise }}) <span class="text-danger">*</span></label>
                                        <input type="text" inputmode="numeric" data-montant class="form-control" name="montant">
                                    </div>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="libelle" placeholder="Motif du mouvement">
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

            <div class="modal fade" id="fermerModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('tresorerie.fermer', $journeeOuverte) }}">
                            @csrf @method('PATCH')
                            <div class="modal-header">
                                <h5 class="modal-title">Clôturer la journée</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Solde théorique actuel : <strong>{{ $fmt($journeeOuverte->soldeDisponible()) }}</strong></p>
                                <div class="mb-1">
                                    <label class="form-label">Solde physique compté ({{ $devise }}) <span class="text-danger">*</span></label>
                                    <input type="text" inputmode="numeric" data-montant class="form-control" name="solde_physique" value="{{ old('solde_physique') }}">
                                    <small class="text-muted">L'écart avec le solde théorique sera calculé et enregistré automatiquement.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-dark">Clôturer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif
@endsection

@push('scripts')
    <script>
        $('#mouvements-table').DataTable({ order: [[0, 'asc']] });
        $('#historique-table').DataTable();

        document.querySelectorAll('#mouvements-table [title]').forEach(el => new bootstrap.Tooltip(el));

        $(document).on('submit', '.confirm-form', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: $(this).data('title') || 'Confirmer ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });
    </script>
@endpush
