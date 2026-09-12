@extends('layouts.admin')

@section('title', 'Nouvelle vente')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Ventes d'or</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ventes.index') }}">Ventes d'or</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nouvelle vente</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if ($barresDisponibles->isEmpty())
        <div class="alert alert-danger">
            <i class='bx bx-error-circle me-1'></i>
            Aucune barre disponible en stock. Enregistrez d'abord un
            <a href="{{ route('achats.create') }}">achat d'or</a>.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('ventes.store') }}" id="venteForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-user me-2'></i>CLIENT ET OPÉRATION</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Client (acheteur) <span class="text-danger">*</span></label>
                        <select class="single-select form-select" name="client_id">
                            <option value="">-- Choisir --</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                    {{ $client->nom_complet }} @if ($client->telephone) ({{ $client->telephone }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Client introuvable ? <a href="{{ route('clients.index') }}" target="_blank">Créez-le d'abord ici</a>, puis rechargez cette page.</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Date de l'opération <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="date_operation" value="{{ old('date_operation', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prix de base (vente) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" class="form-control" name="prix_base" id="prixBase" data-montant value="{{ old('prix_base') }}" placeholder="Ex : 85 000">
                            <span class="input-group-text">{{ auth()->user()->devise_symbole }}</span>
                        </div>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Observations</label>
                    <textarea class="form-control" name="observations" rows="2">{{ old('observations') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="text-white mb-0"><i class='bx bx-list-ul me-2'></i>BARRES DISPONIBLES EN STOCK (<span id="nombreSelection">0</span> sélectionnée(s))</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle" id="barresTable">
                        <thead>
                            <tr>
                                <th width="5%"><input type="checkbox" class="form-check-input" id="cocherToutesLesBarres" title="Tout cocher / décocher"></th>
                                <th>ACHAT D'ORIGINE</th>
                                <th>VENDEUR INITIAL</th>
                                <th>POIDS (g)</th>
                                <th>CARAT</th>
                                <th>PRIX UNITAIRE</th>
                                <th>MONTANT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($barresDisponibles as $barre)
                                <tr class="barre-row" data-poids="{{ $barre->poids }}" data-carat="{{ $barre->carat }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input barre-checkbox" name="barres_ids[]" value="{{ $barre->id }}" @checked(is_array(old('barres_ids')) && in_array($barre->id, old('barres_ids')))>
                                    </td>
                                    <td>{{ $barre->operation->numero }} (barre n°{{ $barre->numero_barre }})</td>
                                    <td>{{ $barre->operation->client->nom_complet }}</td>
                                    <td>{{ number_format($barre->poids, 3, ',', ' ') }}</td>
                                    <td><strong>{{ number_format($barre->carat, 2, ',', ' ') }}</strong></td>
                                    <td class="barre-prix-unitaire text-muted">—</td>
                                    <td class="barre-montant text-muted">—</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="3" id="poidsTotal">Poids sélectionné : 0,000 g</td>
                                <td colspan="2" class="text-end">TOTAL</td>
                                <td colspan="2" id="montantTotal">0 {{ auth()->user()->devise_symbole }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('ventes.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary px-4" @disabled($barresDisponibles->isEmpty())>
                <i class='bx bx-save me-1'></i>Enregistrer la vente
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const devise = @json(auth()->user()->devise_symbole);

        function toNombre(valeur) {
            if (valeur === null || valeur === undefined) return NaN;
            return parseFloat(String(valeur).trim().replace(/\s/g, '').replace(',', '.'));
        }

        function normaliserChamp(input) {
            const n = toNombre(input.value);
            if (!isNaN(n) && input.value.trim() !== '') {
                input.value = String(input.value).trim().replace(',', '.');
            }
        }

        function fmtFcfa(n) {
            return Math.round(n).toLocaleString('fr-FR').replace(/ | /g, ' ') + ' ' + devise;
        }

        function recalculerTout() {
            const prixBase = toNombre(document.getElementById('prixBase').value);
            let poidsSelectionne = 0, montantTotal = 0, nombreSelection = 0;

            document.querySelectorAll('.barre-row').forEach(function (row) {
                const checkbox = row.querySelector('.barre-checkbox');
                const puEl = row.querySelector('.barre-prix-unitaire');
                const montantEl = row.querySelector('.barre-montant');
                const poids = parseFloat(row.dataset.poids);
                const carat = parseFloat(row.dataset.carat);

                if (!checkbox.checked || !prixBase) {
                    puEl.textContent = '—'; puEl.classList.add('text-muted');
                    montantEl.textContent = '—'; montantEl.classList.add('text-muted');
                    return;
                }

                nombreSelection++;
                poidsSelectionne += poids;

                const prixUnitaire = (prixBase / 24) * carat;
                const montant = poids * prixUnitaire;
                montantTotal += montant;

                puEl.textContent = fmtFcfa(prixUnitaire);
                puEl.classList.remove('text-muted');
                montantEl.textContent = fmtFcfa(montant);
                montantEl.classList.remove('text-muted');
            });

            document.getElementById('nombreSelection').textContent = nombreSelection;
            document.getElementById('poidsTotal').textContent = 'Poids sélectionné : ' + poidsSelectionne.toFixed(3).replace('.', ',') + ' g';
            document.getElementById('montantTotal').textContent = fmtFcfa(montantTotal);
        }

        function synchroniserCaseTout() {
            const cases = document.querySelectorAll('.barre-checkbox');
            const cochees = document.querySelectorAll('.barre-checkbox:checked').length;
            const caseTout = document.getElementById('cocherToutesLesBarres');
            caseTout.checked = cases.length > 0 && cochees === cases.length;
            caseTout.indeterminate = cochees > 0 && cochees < cases.length;
        }

        document.querySelectorAll('.barre-checkbox').forEach(cb => cb.addEventListener('change', function () {
            synchroniserCaseTout();
            recalculerTout();
        }));
        document.getElementById('cocherToutesLesBarres').addEventListener('change', function () {
            document.querySelectorAll('.barre-checkbox').forEach(cb => cb.checked = this.checked);
            synchroniserCaseTout();
            recalculerTout();
        });
        document.getElementById('prixBase').addEventListener('input', recalculerTout);
        document.getElementById('prixBase').addEventListener('blur', function () { normaliserChamp(this); recalculerTout(); });

        document.getElementById('venteForm').addEventListener('submit', function (e) {
            const auMoinsUne = document.querySelectorAll('.barre-checkbox:checked').length > 0;
            if (!auMoinsUne) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', text: 'Sélectionnez au moins une barre à vendre.' });
            }
        });

        synchroniserCaseTout();
        recalculerTout();
    </script>
@endpush
