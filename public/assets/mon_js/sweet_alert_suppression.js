document.addEventListener('DOMContentLoaded', function() {
    // Sélectionne tous les boutons de suppression
    const deleteButtons = document.querySelectorAll('.delete-button');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            // Empêche la redirection immédiate
            event.preventDefault(); 
            const deleteUrl = this.getAttribute('href');
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Une fois supprimé, vous ne pourrez pas récupérer ces données !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer!',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });
    });
});