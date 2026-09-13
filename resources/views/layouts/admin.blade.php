<!doctype html>
<html lang="fr" class="semi-bleu">
<head>
    @include('partials.head')
    @stack('styles')
</head>
<body>
    <div class="wrapper">
        @include('partials.sidebar')
        @include('partials.navbar')

        <div class="page-wrapper">
            <div class="page-content">
                @yield('content')
            </div>

            <div class="overlay toggle-icon"></div>
            <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>

            @include('partials.footer')
            @include('partials.theme-customizer')
        </div>
    </div>

    @include('partials.foot')
    <script>
        // Formatage en direct des montants (espace tous les 3 chiffres, ex.
        // "5000000" -> "5 000 000") : appliqué à TOUT champ portant
        // data-montant, où que ce soit dans l'application. La virgule
        // décimale (clavier français) reste possible et n'est jamais
        // touchée par le regroupement. Les espaces sont retirés côté
        // serveur avant validation (cf. FormRequest::prepareForValidation()).
        function formaterMontantEnDirect(input) {
            const depuisLaFin = input.value.length - input.selectionStart;
            const brut = input.value.replace(/\s/g, '');

            const correspondance = brut.match(/^(-?)(\d*)([.,]\d*)?$/);
            if (!correspondance) return; // caractère invalide en cours de frappe : on ne touche à rien

            const [, signe, entier, decimal] = correspondance;
            const entierSansZeros = entier.replace(/^0+(?=\d)/, '');
            const avecEspaces = entierSansZeros.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

            input.value = signe + avecEspaces + (decimal || '');

            const nouvellePosition = Math.max(0, input.value.length - depuisLaFin);
            input.setSelectionRange(nouvellePosition, nouvellePosition);
        }

        document.addEventListener('input', function (e) {
            if (e.target.matches('[data-montant]')) formaterMontantEnDirect(e.target);
        });

        // Déclenche directement l'impression de la facture PDF, sans ouvrir
        // de nouvel onglet ni exiger de chercher le bouton "imprimer" à
        // l'intérieur d'une visionneuse PDF (utilisateurs non-informaticiens) :
        // une iframe cachée charge le PDF puis lance window.print() dessus dès
        // qu'il est prêt. Plus fiable qu'un window.open() + print(), qui peut
        // être bloqué par le navigateur ou ne jamais se déclencher selon le
        // moment où la visionneuse PDF interne finit de charger.
        function imprimerFacture(url) {
            let iframe = document.getElementById('iframeImpressionFacture');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'iframeImpressionFacture';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);
            }
            iframe.onload = function () {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            };
            iframe.src = url;
        }
    </script>
    @stack('scripts')
    @include('partials.pwa')

    <script>
        @if (session('status'))
            window.addEventListener('DOMContentLoaded', () => Swal.fire({
                icon: 'success',
                text: @json(session('status')),
                @if (session('facture_url'))
                    confirmButtonText: 'Imprimer la facture',
                    showCancelButton: true,
                    cancelButtonText: 'Fermer',
                @else
                    timer: 2500,
                    showConfirmButton: false,
                @endif
            })@if (session('facture_url')).then((resultat) => {
                if (resultat.isConfirmed) imprimerFacture(@json(session('facture_url')));
            })@endif);
        @endif
        @if (session('error'))
            window.addEventListener('DOMContentLoaded', () => Swal.fire({
                icon: 'error', text: @json(session('error')),
            }));
        @endif
    </script>
</body>
</html>
