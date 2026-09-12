document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.needs-validatio');
    
    if (form) {
        form.addEventListener('submit', function (event) {
            const libelle = document.getElementById('libelle');
            const symbole = document.getElementById('symbole');

            // Réinitialiser les styles d'erreur et les messages précédents pour libelle
            libelle.style.borderColor = '';
            let existingErrorLibelle = document.getElementById('libelle_error');
            if (existingErrorLibelle) {
                existingErrorLibelle.remove();
            }

            // Réinitialiser les styles d'erreur et les messages précédents pour symbole
            symbole.style.borderColor = '';
            let existingErrorSymbole = document.getElementById('symbole_error');
            if (existingErrorSymbole) {
                existingErrorSymbole.remove();
            }

            // Vérifier si le champ libelle est vide
            if (libelle.value.trim() === '') {
                // Ajouter les styles d'erreur pour libelle
                libelle.style.borderColor = '#dc3545';

                // Créer et afficher un message d'erreur pour libelle
                const errorMessageLibelle = document.createElement('div');
                errorMessageLibelle.id = 'libelle_error';
                errorMessageLibelle.style.color = '#dc3545';
                errorMessageLibelle.textContent = 'Le champ libellé est obligatoire';
                libelle.parentElement.appendChild(errorMessageLibelle);

                // Empêcher l'envoi du formulaire
                event.preventDefault();
                event.stopPropagation();
            }

            // Vérifier si le champ symbole est vide
            if (symbole.value.trim() === '') {
                // Ajouter les styles d'erreur pour symbole
                symbole.style.borderColor = '#dc3545';

                // Créer et afficher un message d'erreur pour symbole
                const errorMessageSymbole = document.createElement('div');
                errorMessageSymbole.id = 'symbole_error';
                errorMessageSymbole.style.color = '#dc3545';
                errorMessageSymbole.textContent = 'Le champ symbole est obligatoire';
                symbole.parentElement.appendChild(errorMessageSymbole);

                // Empêcher l'envoi du formulaire
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Ajouter un écouteur d'événements 'input' pour réinitialiser les erreurs du champ libelle
        const libelle = document.getElementById('libelle');
        if (libelle) {
            libelle.addEventListener('input', function () {
                libelle.style.borderColor = '';
                const existingErrorLibelle = document.getElementById('libelle_error');
                if (existingErrorLibelle) {
                    existingErrorLibelle.remove();
                }
            });
        }

        // Ajouter un écouteur d'événements 'input' pour réinitialiser les erreurs du champ symbole
        const symbole = document.getElementById('symbole');
        if (symbole) {
            symbole.addEventListener('input', function () {
                symbole.style.borderColor = '';
                const existingErrorSymbole = document.getElementById('symbole_error');
                if (existingErrorSymbole) {
                    existingErrorSymbole.remove();
                }
            });
        }
    }
});
