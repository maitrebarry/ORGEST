document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".needs-validation");

  if (form) {
    const fields = {
      nom_produit: document.getElementById("nom_produit"),
      unite: document.getElementById("unite"),
      price: document.getElementById("price"),
      prix_gros: document.getElementById("prix_gros"),
      prix_detail: document.getElementById("prix_detail"),
      code: document.getElementById("code"),
      alerte: document.getElementById("alerte"),
    };

    form.addEventListener("submit", function (event) {
      let hasError = false;

      // Réinitialiser les styles d'erreur et les messages précédents
      Object.keys(fields).forEach(function (key) {
        const field = fields[key];
        if (field) {
          field.style.borderColor = "";
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
          let isValid = field.value.trim() !== "";

          if (!isValid) {
            // Ajouter les styles d'erreur
            field.style.borderColor = "#dc3545";

            // Créer et afficher un message d'erreur
            const errorMessage = document.createElement("div");
            errorMessage.id = `${key}_error`;
            errorMessage.style.color = "#dc3545";
            errorMessage.textContent = `Le champ ${formatLabel(
              key
            )} est obligatoire`;
            field.parentElement.appendChild(errorMessage);

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
        field.addEventListener("input", function () {
          field.style.borderColor = "";
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
      case "nom_produit":
        return "Nom produit";
      case "unite":
        return "Unité";
      case "price":
        return "Prix d'achat";
      case "prix_gros":
        return "Prix en gros";
      case "prix_detail":
        return "Prix en détail";
      case "code":
        return "Code barre produit";
      case "alerte":
        return "Alerte stock";
      default:
        return key.replace(/_/g, " ");
    }
  }
});
