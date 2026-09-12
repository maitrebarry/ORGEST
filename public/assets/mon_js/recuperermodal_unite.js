document.addEventListener('DOMContentLoaded', function() {
    // Récupérer la valeur de ROOT
    var baseURL = document.getElementById('baseURL').value;  
    var modal = document.getElementById('modifierModal');
    
    if (modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            // Récupérer le bouton qui a déclenché l'événement modal
            var button = event.relatedTarget;
            var uniteId = button.getAttribute('data-id');
            var libelle = button.getAttribute('data-libelle');
            var symbole = button.getAttribute('data-symbole');
            
            // Remplir les champs de la modal avec les valeurs récupérées  
            modal.querySelector('input[name="libelle"]').value = libelle;
            modal.querySelector('input[name="symbole"]').value = symbole;
            
            // Mettre à jour l'action du formulaire avec la bonne URL
            var form = modal.querySelector('form');
            form.action = baseURL + '/Configurations/update_unite/' + uniteId;
        });
    }
});