@extends('layouts.admin')

@section('title', 'Crédit '.$credit->numero)

@php
    $devise = auth()->user()->devise_symbole;
    $fmt = fn ($m) => number_format((float) $m, 2, ',', ' ').' '.$devise;
    $peutRembourser = auth()->user()->can('credits.gerer') && ! $credit->estAnnule() && $credit->solde() > 0;
    $peutAnnuler = auth()->user()->can('credits.annuler') && ! $credit->estAnnule() && (float) $credit->montant_rembourse <= 0;
@endphp

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Cahier de crédit</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('credits.index') }}">Cahier de crédit</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $credit->numero }}</li>
                </ol>
            </nav>
        </div>
        @if ($peutAnnuler)
            <div class="ms-auto">
                <form method="POST" action="{{ route('credits.annuler', $credit) }}" class="d-inline confirm-form" data-title="Annuler ce crédit ?">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger"><i class='bx bx-block'></i> Annuler le crédit</button>
                </form>
            </div>
        @endif
    </div>
    <hr />

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between">
            <h6 class="text-white mb-0"><i class='bx bx-wallet me-2'></i>CRÉDIT {{ $credit->numero }}</h6>
            <span class="badge {{ $credit->statutBadge() }}">{{ $credit->statutLibelle() }}</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><small class="text-muted d-block">Client</small>{{ $credit->client->nom_complet }}</div>
                <div class="col-md-3"><small class="text-muted d-block">Date</small>{{ $credit->date_credit->format('d/m/Y à H:i') }}</div>
                <div class="col-md-3"><small class="text-muted d-block">Montant accordé</small>{{ $fmt($credit->montant_accorde) }}</div>
                <div class="col-md-3"><small class="text-muted d-block">Opérateur</small>{{ $credit->user?->name ?? '—' }}</div>
            </div>
            @if ($credit->observations)
                <div class="mt-2"><small class="text-muted d-block">Observations</small>{{ $credit->observations }}</div>
            @endif
        </div>
    </div>

    <div class="row mb-3 align-items-stretch">
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Montant remis</small>
                    <div class="fw-bold">{{ $fmt($credit->montant_remis) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Montant remboursé</small>
                    <div class="fw-bold">{{ $fmt($credit->montant_rembourse) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card {{ $credit->solde() > 0 ? 'bg-warning-subtle' : 'bg-light' }} h-100">
                <div class="card-body py-2">
                    <small class="text-muted">Solde restant</small>
                    <div class="fw-bold">{{ $fmt($credit->solde()) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-history me-2'></i>HISTORIQUE DES REMBOURSEMENTS</h6>
        </div>
        <div class="card-body">
            @if ($credit->remboursements->isEmpty())
                <p class="text-muted text-center py-3 mb-0">Aucun remboursement enregistré pour le moment.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>DATE</th>
                                <th>RÉFÉRENCE</th>
                                <th>MODE</th>
                                <th>OR (poids)</th>
                                <th class="text-end">ESPÈCES</th>
                                <th class="text-end">VALEUR OR</th>
                                <th class="text-end">REMBOURSÉ</th>
                                <th class="text-end">RELIQUAT RENDU</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($credit->remboursements as $r)
                                <tr>
                                    <td>{{ $r->date_remboursement->format('d/m/Y') }}</td>
                                    <td>{{ $r->numero }}</td>
                                    <td>{{ $r->mode_libelle }}</td>
                                    <td>{{ $r->lignesOr->isNotEmpty() ? number_format($r->lignesOr->sum('poids'), 3, ',', ' ').' g' : '—' }}</td>
                                    <td class="text-end">{{ (float) $r->montant_especes > 0 ? $fmt($r->montant_especes) : '—' }}</td>
                                    <td class="text-end">{{ (float) $r->montant_or > 0 ? $fmt($r->montant_or) : '—' }}</td>
                                    <td class="text-end">{{ $fmt($r->montant_total) }}</td>
                                    <td class="text-end">{{ (float) $r->reliquat > 0 ? $fmt($r->reliquat) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($peutRembourser)
        <div class="card mb-4">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-cash me-2'></i>ENREGISTRER UN REMBOURSEMENT</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('credits.rembourser', $credit) }}" id="remboursementForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label d-block">Mode de remboursement <span class="text-danger">*</span></label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="mode" id="modeEspeces" value="especes" checked>
                            <label class="btn btn-outline-primary" for="modeEspeces">Espèces</label>

                            <input type="radio" class="btn-check" name="mode" id="modeOr" value="or">
                            <label class="btn btn-outline-primary" for="modeOr">Or</label>

                            <input type="radio" class="btn-check" name="mode" id="modeOrEspeces" value="or_especes">
                            <label class="btn btn-outline-primary" for="modeOrEspeces">Or + espèces</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date du remboursement <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="date_remboursement" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-md-4 mb-3 champ-especes">
                            <label class="form-label" id="labelMontantEspeces">Montant remis en espèces <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" inputmode="numeric" data-montant class="form-control" name="montant_especes" id="montantEspeces">
                                <span class="input-group-text">{{ $devise }}</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 champ-especes">
                            <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                            <select name="mode_paiement" class="form-select">
                                <option value="">Sélectionner…</option>
                                @foreach (\App\Models\MouvementFinancier::MODES_PAIEMENT as $valeur => $libelle)
                                    <option value="{{ $valeur }}">{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 champ-or d-none">
                            <label class="form-label">Prix de base du jour <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" class="form-control" name="prix_base" id="prixBaseRemb" data-montant>
                                <span class="input-group-text">{{ $devise }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="champ-or d-none">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0">Or apporté</label>
                            <button type="button" class="btn btn-light btn-sm" id="addLigneOrBtn"><i class='bx bx-plus'></i> Ajouter une barre</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="lignesOrTable">
                                <thead>
                                    <tr>
                                        <th width="6%">#</th>
                                        <th>Poids (g)</th>
                                        <th>Eau</th>
                                        <th width="10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="lignesOrBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observations</label>
                        <textarea class="form-control" name="observations" rows="2"></textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4"><i class='bx bx-save me-1'></i>Enregistrer le remboursement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(document).on('submit', '.confirm-form', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: $(this).data('title') || 'Confirmer ?',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                confirmButtonText: 'Oui', cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });

        @if ($peutRembourser)
            function appliquerMode() {
                const mode = document.querySelector('input[name="mode"]:checked').value;
                const especes = mode === 'especes' || mode === 'or_especes';
                const or = mode === 'or' || mode === 'or_especes';

                document.querySelectorAll('.champ-especes').forEach(el => el.classList.toggle('d-none', !especes));
                document.querySelectorAll('.champ-or').forEach(el => el.classList.toggle('d-none', !or));
                document.getElementById('labelMontantEspeces').textContent = mode === 'or_especes'
                    ? 'Complément en espèces'
                    : 'Montant remis en espèces';

                document.getElementById('montantEspeces').required = especes;
                document.querySelector('select[name="mode_paiement"]').required = especes;
                document.getElementById('prixBaseRemb').required = or;

                if (or && document.querySelectorAll('#lignesOrBody tr').length === 0) {
                    ajouterLigneOr();
                }
            }

            let indexLigneOr = 0;
            function ajouterLigneOr() {
                const idx = indexLigneOr++;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="num-ligne">${document.querySelectorAll('#lignesOrBody tr').length + 1}</td>
                    <td><input type="text" inputmode="decimal" class="form-control" name="barres[${idx}][poids]" placeholder="Ex : 27,89"></td>
                    <td><input type="text" inputmode="decimal" class="form-control" name="barres[${idx}][eau]" placeholder="Ex : 1,49"></td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-ligne-or" title="Supprimer"><i class='bx bx-trash'></i></button></td>
                `;
                document.getElementById('lignesOrBody').appendChild(tr);
            }

            function renumeroterLignesOr() {
                document.querySelectorAll('#lignesOrBody tr').forEach((tr, i) => {
                    tr.querySelector('.num-ligne').textContent = i + 1;
                });
            }

            document.querySelectorAll('input[name="mode"]').forEach(r => r.addEventListener('change', appliquerMode));
            document.getElementById('addLigneOrBtn').addEventListener('click', ajouterLigneOr);
            document.getElementById('lignesOrBody').addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-ligne-or');
                if (!btn) return;
                if (document.querySelectorAll('#lignesOrBody tr').length <= 1) {
                    Swal.fire({ icon: 'warning', text: 'Il faut conserver au moins une barre.' });
                    return;
                }
                btn.closest('tr').remove();
                renumeroterLignesOr();
            });

            appliquerMode();
        @endif
    </script>
@endpush
