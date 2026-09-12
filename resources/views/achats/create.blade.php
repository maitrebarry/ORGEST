@extends('layouts.admin')

@section('title', "Nouvel achat d'or")

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Achats d'or</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('achats.index') }}">Achats d'or</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nouvel achat</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if (! $baremeActif)
        <div class="alert alert-danger">
            <i class='bx bx-error-circle me-1'></i>
            Aucun barème actif : impossible de déterminer un carat. Créez d'abord une
            <a href="{{ route('baremes.create') }}">version du barème</a>.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('achats.store') }}" id="achatForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-user me-2'></i>CLIENT ET OPÉRATION</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Client <span class="text-danger">*</span></label>
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
                        <label class="form-label">Prix de base <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" class="form-control" name="prix_base" id="prixBase" data-montant value="{{ old('prix_base') }}" placeholder="Ex : 80 000">
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
                <h6 class="text-white mb-0"><i class='bx bx-list-ul me-2'></i>BARRES (<span id="nombreBarres">1</span>)</h6>
                <button type="button" class="btn btn-light btn-sm" id="addBarreBtn">
                    <i class='bx bx-plus'></i> Ajouter une barre
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle" id="barresTable">
                        <thead>
                            <tr>
                                <th width="6%">#</th>
                                <th width="14%">Poids (g) <span class="text-danger">*</span></th>
                                <th width="14%">Eau <span class="text-danger">*</span></th>
                                <th width="13%">Densité</th>
                                <th width="11%">Carat</th>
                                <th width="16%">Prix unitaire</th>
                                <th width="18%">Montant</th>
                                <th width="8%"></th>
                            </tr>
                        </thead>
                        <tbody id="barresBody"></tbody>
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="2" id="poidsTotal">Poids total : 0,000 g</td>
                                <td colspan="1" id="eauTotal">Eau : 0,0000</td>
                                <td colspan="2"></td>
                                <td colspan="1" class="text-end">TOTAL</td>
                                <td colspan="2" id="montantTotal">0 {{ auth()->user()->devise_symbole }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="text-muted small mb-0">
                    <i class='bx bx-info-circle'></i>
                    La densité (Poids ÷ Eau) est tronquée à 2 décimales sans arrondi pour déterminer le carat via le
                    barème actif. Ce calcul est indicatif ; le serveur recalcule et vérifie tout à l'enregistrement.
                </p>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('achats.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary px-4" @disabled(! $baremeActif)>
                <i class='bx bx-save me-1'></i>Enregistrer l'achat
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const bareme = @json($baremeActif ? $baremeActif->lignes->map(fn ($l) => ['min' => (float) $l->densite_min, 'max' => (float) $l->densite_max, 'carat' => (float) $l->carat]) : []);
        const devise = @json(auth()->user()->devise_symbole);

        function toNombre(valeur) {
            // Accepte aussi bien "27.89" que "27,89" (clavier français) :
            // un <input type="number"> rejette silencieusement la virgule et
            // vide le champ, d'où l'usage d'un simple champ texte ici.
            if (valeur === null || valeur === undefined) return NaN;
            return parseFloat(String(valeur).trim().replace(/\s/g, '').replace(',', '.'));
        }

        function normaliserChamp(input) {
            const n = toNombre(input.value);
            if (!isNaN(n) && input.value.trim() !== '') {
                input.value = String(input.value).trim().replace(',', '.');
            }
        }

        function tronquer2(valeur) {
            // Troncature (jamais arrondi) à 2 décimales, cohérent avec le calcul serveur.
            return Math.trunc(valeur * 100) / 100;
        }

        function trouverCarat(densiteTronquee) {
            const ligne = bareme.find(l => densiteTronquee >= l.min && densiteTronquee <= l.max);
            return ligne ? ligne.carat : null;
        }

        function fmtFcfa(n) {
            return Math.round(n).toLocaleString('fr-FR').replace(/ | /g, ' ') + ' ' + devise;
        }

        let compteur = 0;
        let indexChamp = 0;

        function rowTemplate() {
            compteur++;
            // Index unique et STABLE par ligne, utilisé pour les DEUX champs
            // (poids et eau) : "barres[][poids]" et "barres[][eau]" sont deux
            // groupes à crochets vides distincts pour PHP, qui incrémente
            // chacun séparément — sans index explicite partagé, poids et eau
            // d'une même ligne finissent associés à des lignes différentes.
            const idx = indexChamp++;
            const tr = document.createElement('tr');
            tr.className = 'barre-row';
            tr.innerHTML = `
                <td class="num-barre">${compteur}</td>
                <td><input type="text" inputmode="decimal" class="form-control barre-poids" name="barres[${idx}][poids]" placeholder="Ex : 27,89"></td>
                <td><input type="text" inputmode="decimal" class="form-control barre-eau" name="barres[${idx}][eau]" placeholder="Ex : 1,49"></td>
                <td><span class="barre-densite text-muted">—</span></td>
                <td><span class="barre-carat fw-bold text-muted">—</span></td>
                <td><span class="barre-prix-unitaire text-muted">—</span></td>
                <td><span class="barre-montant fw-bold text-muted">—</span></td>
                <td class="text-center text-nowrap">
                    <button type="button" class="btn btn-success btn-sm add-barre-apres-btn" title="Ajouter une barre après"><i class='bx bx-plus'></i></button>
                    <button type="button" class="btn btn-danger btn-sm remove-barre-btn" title="Supprimer cette barre"><i class='bx bx-trash'></i></button>
                </td>
            `;
            return tr;
        }

        function renumeroter() {
            document.querySelectorAll('#barresBody .barre-row').forEach((row, i) => {
                row.querySelector('.num-barre').textContent = i + 1;
            });
            document.getElementById('nombreBarres').textContent = document.querySelectorAll('#barresBody .barre-row').length;
        }

        function recalculerLigne(row) {
            const prixBase = toNombre(document.getElementById('prixBase').value);
            const poids = toNombre(row.querySelector('.barre-poids').value);
            const eau = toNombre(row.querySelector('.barre-eau').value);

            const densiteEl = row.querySelector('.barre-densite');
            const caratEl = row.querySelector('.barre-carat');
            const puEl = row.querySelector('.barre-prix-unitaire');
            const montantEl = row.querySelector('.barre-montant');

            if (!poids || !eau || eau <= 0) {
                densiteEl.textContent = '—'; caratEl.textContent = '—'; puEl.textContent = '—'; montantEl.textContent = '—';
                caratEl.classList.add('text-muted'); caratEl.classList.remove('text-danger');
                return { poids: 0, eau: 0, montant: 0 };
            }

            const densiteBrute = poids / eau;
            const densiteTronquee = tronquer2(densiteBrute);
            densiteEl.textContent = densiteTronquee.toFixed(2).replace('.', ',');
            densiteEl.classList.remove('text-muted');

            const carat = trouverCarat(densiteTronquee);
            if (carat === null) {
                caratEl.textContent = 'Hors barème';
                caratEl.classList.add('text-danger'); caratEl.classList.remove('text-muted', 'fw-bold');
                puEl.textContent = '—'; montantEl.textContent = '—';
                return { poids, eau, montant: 0 };
            }
            caratEl.classList.remove('text-danger', 'text-muted');
            caratEl.classList.add('fw-bold');
            caratEl.textContent = carat.toFixed(2).replace('.', ',');

            let montant = 0;
            if (prixBase) {
                const prixUnitaire = (prixBase / 24) * carat;
                montant = poids * prixUnitaire;
                puEl.textContent = fmtFcfa(prixUnitaire);
                puEl.classList.remove('text-muted');
                montantEl.textContent = fmtFcfa(montant);
                montantEl.classList.remove('text-muted');
            } else {
                puEl.textContent = '—'; montantEl.textContent = '—';
            }

            return { poids, eau, montant };
        }

        function recalculerTout() {
            let poidsTotal = 0, eauTotal = 0, montantTotal = 0;
            document.querySelectorAll('#barresBody .barre-row').forEach(row => {
                const r = recalculerLigne(row);
                poidsTotal += r.poids; eauTotal += r.eau; montantTotal += r.montant;
            });
            document.getElementById('poidsTotal').textContent = 'Poids total : ' + poidsTotal.toFixed(3).replace('.', ',') + ' g';
            document.getElementById('eauTotal').textContent = 'Eau : ' + eauTotal.toFixed(4).replace('.', ',');
            document.getElementById('montantTotal').textContent = fmtFcfa(montantTotal);
        }

        document.getElementById('addBarreBtn').addEventListener('click', function () {
            document.getElementById('barresBody').appendChild(rowTemplate());
            renumeroter();
        });

        document.getElementById('barresBody').addEventListener('click', function (e) {
            const btn = e.target.closest('.add-barre-apres-btn');
            if (!btn) return;
            btn.closest('tr').after(rowTemplate());
            renumeroter();
        });

        document.getElementById('barresBody').addEventListener('input', function (e) {
            if (e.target.matches('.barre-poids, .barre-eau')) recalculerTout();
        });

        // À la sortie du champ, on réécrit "27,89" en "27.89" : le champ
        // envoyé au serveur est toujours au format à point décimal.
        document.getElementById('barresBody').addEventListener('blur', function (e) {
            if (e.target.matches('.barre-poids, .barre-eau')) normaliserChamp(e.target);
        }, true);

        document.getElementById('prixBase').addEventListener('input', recalculerTout);
        document.getElementById('prixBase').addEventListener('blur', function () { normaliserChamp(this); });

        document.getElementById('barresBody').addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-barre-btn');
            if (!btn) return;
            const rows = document.querySelectorAll('#barresBody .barre-row');
            if (rows.length <= 1) {
                Swal.fire({ icon: 'warning', text: 'Il faut conserver au moins une barre.' });
                return;
            }

            const row = btn.closest('tr');
            const numero = row.querySelector('.num-barre').textContent;
            const poids = row.querySelector('.barre-poids').value;

            Swal.fire({
                title: 'Supprimer cette barre ?',
                text: 'Barre n°' + numero + (poids ? ' (' + poids + ' g)' : ''),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => {
                if (!result.isConfirmed) return;
                row.remove();
                renumeroter();
                recalculerTout();
            });
        });

        // Première barre par défaut
        document.getElementById('barresBody').appendChild(rowTemplate());
        renumeroter();
    </script>
@endpush
