document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.needs-validation');

    if (form) {
        form.addEventListener('submit', function (event) {
            const date = document.getElementById('date');
            const nom = document.getElementById('nom');
            const quartier = document.getElementById('quartier');

            // Reset error styles and messages for date
            date.style.borderColor = '';
            let existingErrordate = document.getElementById('date_error');
            if (existingErrordate) {
                existingErrordate.remove();
            }

            // Reset error styles and messages for nom
            nom.style.borderColor = '';
            let existingErrornom = document.getElementById('nom_error');
            if (existingErrornom) {
                existingErrornom.remove();
            }

            // Reset error styles and messages for quartier
            quartier.style.borderColor = '';
            let existingErrorquartier = document.getElementById('quartier_error');
            if (existingErrorquartier) {
                existingErrorquartier.remove();
            }

            // Check if the date field is empty
            if (date.value.trim() === '') {
                date.style.borderColor = '#dc3545';

                const errorMessagedate = document.createElement('div');
                errorMessagedate.id = 'date_error';
                errorMessagedate.style.color = '#dc3545';
                errorMessagedate.textContent = 'Le champ date est obligatoire';
                date.parentElement.appendChild(errorMessagedate);

                event.preventDefault();
                event.stopPropagation();
            }

            // Check if the nom field is empty
            if (nom.value.trim() === '') {
                nom.style.borderColor = '#dc3545';

                const errorMessagenom = document.createElement('div');
                errorMessagenom.id = 'nom_error';
                errorMessagenom.style.color = '#dc3545';
                errorMessagenom.textContent = 'Le champ nom est obligatoire';
                nom.parentElement.appendChild(errorMessagenom);

                event.preventDefault();
                event.stopPropagation();
            }

            // Check if the quartier field is empty
            if (quartier.value.trim() === '') {
                quartier.style.borderColor = '#dc3545';

                const errorMessagequartier = document.createElement('div');
                errorMessagequartier.id = 'quartier_error';
                errorMessagequartier.style.color = '#dc3545';
                errorMessagequartier.textContent = 'Le champ quartier est obligatoire';
                quartier.parentElement.appendChild(errorMessagequartier);

                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Input event listeners for date to reset error messages
        const date = document.getElementById('date');
        if (date) {
            date.addEventListener('input', function () {
                date.style.borderColor = '';
                const existingErrordate = document.getElementById('date_error');
                if (existingErrordate) {
                    existingErrordate.remove();
                }
            });
        }

        // Input event listeners for nom to reset error messages
        const nom = document.getElementById('nom');
        if (nom) {
            nom.addEventListener('input', function () {
                nom.style.borderColor = '';
                const existingErrornom = document.getElementById('nom_error');
                if (existingErrornom) {
                    existingErrornom.remove();
                }
            });
        }

        // Input event listeners for quartier to reset error messages
        const quartier = document.getElementById('quartier');
        if (quartier) {
            quartier.addEventListener('input', function () {
                quartier.style.borderColor = '';
                const existingErrorquartier = document.getElementById('quartier_error');
                if (existingErrorquartier) {
                    existingErrorquartier.remove();
                }
            });
        }
    }
});