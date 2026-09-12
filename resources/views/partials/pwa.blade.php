{{-- Modal d'installation de l'application (PWA) --}}
<div class="modal fade" id="pwaInstallModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <img src="{{ asset('assets/images/icon-192.png') }}" alt="ORGEST" style="width:88px;height:88px;border-radius:20px;box-shadow:0 8px 22px rgba(10,18,35,.2);">
                <h5 class="mt-3 mb-1">Installer ORGEST</h5>
                <p class="text-muted mb-4">Ajoutez l'application à votre appareil pour l'ouvrir comme une vraie application mobile, en plein écran et hors-ligne.</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="pwaInstallBtn"><i class='bx bx-download'></i> Installer l'application</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="pwaLaterBtn">Plus tard</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        // 1) Enregistrement du service worker (rend l'app installable + hors-ligne)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }

        // 2) Capture de l'invite d'installation, puis affichage du modal une seule fois
        let deferredPrompt = null;
        const dejaTraite = () => localStorage.getItem('orgest-pwa-installed') === '1';

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (dejaTraite() || !window.bootstrap) return;
            const modal = new bootstrap.Modal(document.getElementById('pwaInstallModal'));
            modal.show();

            document.getElementById('pwaInstallBtn').addEventListener('click', async () => {
                modal.hide();
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                localStorage.setItem('orgest-pwa-installed', '1');
            }, { once: true });

            document.getElementById('pwaLaterBtn').addEventListener('click', () => {
                localStorage.setItem('orgest-pwa-installed', '1');
            }, { once: true });
        });

        window.addEventListener('appinstalled', () => localStorage.setItem('orgest-pwa-installed', '1'));
    })();
</script>
