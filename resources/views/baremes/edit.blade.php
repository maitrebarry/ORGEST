@extends('layouts.admin')

@section('title', 'Modifier le barème')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Barème</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('baremes.index') }}">Densité → Carat</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier « {{ $version->libelle }} »</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @php $estAMoi = $version->bureau_id === auth()->user()->bureau_id; @endphp

    @if ($estAMoi)
        <div class="alert alert-warning">
            <i class='bx bx-edit-alt me-1'></i>
            Tu modifies directement cette version (pas de nouvelle version créée). Cela n'est possible que tant
            qu'aucune opération d'achat/vente ne s'appuie encore dessus.
        </div>
    @endif

    <form method="POST" action="{{ route('baremes.update', $version) }}" id="baremeForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-grid-alt me-2'></i>{{ strtoupper($version->libelle) }}</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Libellé de la version <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="libelle" value="{{ old('libelle', $version->libelle) }}">
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Observations</label>
                    <textarea class="form-control" name="observations" rows="2">{{ old('observations', $version->observations) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header card-header-brand">
                <h6 class="text-white mb-0"><i class='bx bx-paste me-2'></i>COLLER UNE LISTE (facultatif)</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    Une ligne par plage : <code>densité min, densité max, carat</code> (séparateur virgule, point-virgule
                    ou tabulation — pratique pour coller depuis un tableur ou le fichier CSV fourni).
                    <strong>Remplace toutes les lignes ci-dessous.</strong>
                </p>
                <textarea class="form-control mb-2" id="collePaste" rows="4" placeholder="18.65,18.71,22.90&#10;19.10,19.16,23.60"></textarea>
                <button type="button" class="btn btn-secondary btn-sm" id="chargerColleBtn">
                    <i class='bx bx-import'></i> Charger la liste (remplace les lignes)
                </button>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header card-header-brand d-flex align-items-center justify-content-between">
                <h6 class="text-white mb-0"><i class='bx bx-list-ul me-2'></i>PLAGES DENSITÉ → CARAT</h6>
                <button type="button" class="btn btn-light btn-sm" id="addLigneBtn">
                    <i class='bx bx-plus'></i> Ajouter une ligne
                </button>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <table class="table" id="lignesTable">
                    <thead>
                        <tr>
                            <th width="30%">Densité min <span class="text-danger">*</span></th>
                            <th width="30%">Densité max <span class="text-danger">*</span></th>
                            <th width="25%">Carat <span class="text-danger">*</span></th>
                            <th width="15%"></th>
                        </tr>
                    </thead>
                    <tbody id="lignesBody">
                        @php $anciennes = old('lignes', $version->lignes->map(fn ($l) => ['densite_min' => $l->densite_min, 'densite_max' => $l->densite_max, 'carat' => $l->carat])->all()); @endphp
                        @foreach ($anciennes as $i => $ligne)
                            <tr class="ligne-row">
                                <td><input type="text" inputmode="decimal" class="form-control" name="lignes[{{ $i }}][densite_min]" value="{{ $ligne['densite_min'] ?? '' }}"></td>
                                <td><input type="text" inputmode="decimal" class="form-control" name="lignes[{{ $i }}][densite_max]" value="{{ $ligne['densite_max'] ?? '' }}"></td>
                                <td><input type="text" inputmode="decimal" class="form-control" name="lignes[{{ $i }}][carat]" value="{{ $ligne['carat'] ?? '' }}"></td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-success btn-sm add-ligne-apres-btn" title="Ajouter une ligne après"><i class='bx bx-plus'></i></button>
                                    <button type="button" class="btn btn-danger btn-sm remove-ligne-btn" title="Supprimer cette ligne"><i class='bx bx-trash'></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ route('baremes.show', $version) }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary px-4"><i class='bx bx-save me-1'></i>Enregistrer les modifications</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Cf. commentaire équivalent dans baremes/create.blade.php : index
        // explicite obligatoire pour regrouper les 3 champs d'une même ligne.
        let indexLigne = {{ count($anciennes) }};

        const rowTemplate = () => {
            const idx = indexLigne++;
            const tr = document.createElement('tr');
            tr.className = 'ligne-row';
            tr.innerHTML = `
                <td><input type="text" inputmode="decimal" class="form-control" name="lignes[${idx}][densite_min]"></td>
                <td><input type="text" inputmode="decimal" class="form-control" name="lignes[${idx}][densite_max]"></td>
                <td><input type="text" inputmode="decimal" class="form-control" name="lignes[${idx}][carat]"></td>
                <td class="text-center text-nowrap">
                    <button type="button" class="btn btn-success btn-sm add-ligne-apres-btn" title="Ajouter une ligne après"><i class='bx bx-plus'></i></button>
                    <button type="button" class="btn btn-danger btn-sm remove-ligne-btn" title="Supprimer cette ligne"><i class='bx bx-trash'></i></button>
                </td>
            `;
            return tr;
        };

        document.getElementById('addLigneBtn').addEventListener('click', function () {
            document.getElementById('lignesBody').appendChild(rowTemplate());
        });

        document.getElementById('lignesBody').addEventListener('click', function (e) {
            const btn = e.target.closest('.add-ligne-apres-btn');
            if (!btn) return;
            btn.closest('tr').after(rowTemplate());
        });

        function parserLigneCollee(ligne) {
            let morceaux = ligne.split(/[;\t]+/).map(s => s.trim()).filter(s => s !== '');
            if (morceaux.length !== 3) {
                morceaux = ligne.split(',').map(s => s.trim()).filter(s => s !== '');
            }
            if (morceaux.length !== 3) return null;

            const nombres = morceaux.map(m => parseFloat(m.replace(',', '.')));
            if (nombres.some(n => isNaN(n))) return null;

            return { densite_min: nombres[0], densite_max: nombres[1], carat: nombres[2] };
        }

        document.getElementById('chargerColleBtn').addEventListener('click', function () {
            const texte = document.getElementById('collePaste').value;
            const lignesTexte = texte.split('\n').map(l => l.trim()).filter(l => l !== '');

            if (lignesTexte.length === 0) {
                Swal.fire({ icon: 'warning', text: 'Colle d\'abord une liste de lignes.' });
                return;
            }

            const parsees = [];
            const ignorees = [];
            lignesTexte.forEach(function (ligne, i) {
                const r = parserLigneCollee(ligne);
                if (r) parsees.push(r); else ignorees.push(i + 1);
            });

            if (parsees.length === 0) {
                Swal.fire({ icon: 'error', text: 'Aucune ligne exploitable trouvée. Format attendu : densite_min,densite_max,carat' });
                return;
            }

            const suite = function () {
                const body = document.getElementById('lignesBody');
                body.innerHTML = '';
                parsees.forEach(function (l) {
                    const tr = rowTemplate();
                    const inputs = tr.querySelectorAll('input');
                    inputs[0].value = l.densite_min;
                    inputs[1].value = l.densite_max;
                    inputs[2].value = l.carat;
                    body.appendChild(tr);
                });
                Swal.fire({ icon: 'success', text: parsees.length + ' ligne(s) chargée(s).' + (ignorees.length ? ' (' + ignorees.length + ' ligne(s) ignorée(s) : format non reconnu)' : ''), timer: 3000, showConfirmButton: false });
            };

            Swal.fire({
                title: 'Remplacer les lignes actuelles ?',
                text: parsees.length + ' ligne(s) vont remplacer le tableau ci-dessous.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, remplacer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) suite(); });
        });

        document.getElementById('lignesBody').addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-ligne-btn');
            if (!btn) return;

            const rows = document.querySelectorAll('#lignesBody .ligne-row');
            if (rows.length <= 1) {
                Swal.fire({ icon: 'warning', text: 'Il faut conserver au moins une ligne.' });
                return;
            }

            const ligne = btn.closest('tr');
            const inputs = ligne.querySelectorAll('input');
            const resume = [inputs[0].value, inputs[1].value, inputs[2].value].filter(v => v !== '').join(' / ');

            Swal.fire({
                title: 'Supprimer cette ligne ?',
                text: resume ? 'Plage ' + resume : 'Cette ligne vide sera retirée.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) ligne.remove(); });
        });

        document.getElementById('baremeForm').addEventListener('submit', function (e) {
            const rows = document.querySelectorAll('#lignesBody .ligne-row');
            let valide = true;

            rows.forEach(function (row) {
                row.querySelectorAll('input').forEach(function (input) {
                    if (input.value.trim() === '') valide = false;
                });
            });

            if (!valide) {
                e.preventDefault();
                Swal.fire({ icon: 'error', text: 'Chaque ligne doit avoir sa densité min, sa densité max et son carat renseignés.' });
            }
        });
    </script>
@endpush
