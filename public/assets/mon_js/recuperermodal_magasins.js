document.addEventListener('DOMContentLoaded', function() {
    // Récupérer la valeur de ROOT
    var baseURL = document.getElementById('baseURL').value;  
    var modal = document.getElementById('modifierModal');
    
    if (modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            // Récupérer le bouton qui a déclenché l'événement modal
            var button = event.relatedTarget;
            var magasineId = button.getAttribute('data-id');
            var date = button.getAttribute('data-date');
            var nom = button.getAttribute('data-nom');
            var quartier = button.getAttribute('data-quartier');
            // Remplir les champs de la modal avec les valeurs récupérées  
            modal.querySelector('input[name="date"]').value = date;
            modal.querySelector('input[name="nom"]').value = nom;
            modal.querySelector('input[name="quartier"]').value = quartier;
            // Mettre à jour l'action du formulaire avec la bonne URL
            var form = modal.querySelector('form');
            form.action = baseURL + '/Configurations/update_magasins/' + magasineId;
        });
    }
});
