document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.needs-validation');

    if (form) {
        const fields = {
            nom: document.getElementById('nom'),
            prenom: document.getElementById('prenom'),
            quartier: document.getElementById('quartier'),
            contact: document.getElementById('contact')
        };

        form.addEventListener('submit', function (event) {
            let hasError = false;

            // Réinitialiser les styles d'erreur et les messages précédents
            Object.keys(fields).forEach(function (key) {
                const field = fields[key];
                if (field) {
                    field.style.borderColor = '';
                    const existingError = document.getElementById(`${key}_error`);
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });

            // Vérifier si les champs sont vides et afficher les erreurs
            Object.keys(fields).forEach(function (key) {
                const field = fields[key];
                if (field) {
                    let isValid = field.value.trim() !== '';

                    if (!isValid) {
                        // Ajouter les styles d'erreur
                        field.style.borderColor = '#dc3545'; // Couleur de bordure pour l'erreur

                        // Créer et afficher un message d'erreur
                        const errorMessage = document.createElement('div');
                        errorMessage.id = `${key}_error`;
                        errorMessage.style.color = '#dc3545'; // Couleur du texte pour l'erreur
                        errorMessage.textContent = `Le champ ${formatLabel(key)} est obligatoire`;
                        field.parentElement.appendChild(errorMessage);

                        // Marquer comme ayant une erreur
                        hasError = true;
                    }
                }
            });

            // Empêcher l'envoi du formulaire si des erreurs sont présentes
            if (hasError) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Ajouter un écouteur d'événements 'input' pour réinitialiser les erreurs
        Object.keys(fields).forEach(function (key) {
            const field = fields[key];
            if (field) {
                field.addEventListener('input', function () {
                    // Réinitialiser les styles d'erreur et les messages précédents
                    field.style.borderColor = '';
                    const existingError = document.getElementById(`${key}_error`);
                    if (existingError) {
                        existingError.remove();
                    }
                });
            }
        });
    }

    // Fonction pour formater les labels des champs
    function formatLabel(key) {
        switch (key) {
            case 'nom':
                return 'Nom';
            case 'prenom':
                return 'Prénom';
            case 'quartier':
                return 'Quartier';
            case 'contact':
                return 'Contact';
            default:
                return key.replace(/_/g, ' ');
        }
    }
});
