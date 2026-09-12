$(document).ready(function () {
  // Gestion de la sélection d'une commande
  $("#commande_select").on("change", function () {
    const id_commande = $(this).val();

    if (!id_commande) {
      alert("Veuillez sélectionner une commande.");
      return;
    }

    // Appel AJAX pour récupérer les détails de la commande
    $.ajax({
      type: "POST",
      url: `${ROOT}/Commandes/reception`, 
      dataType: "json",
      data: { id_commande: id_commande },
      beforeSend: function () {
        $("#loading").show();
      },
      success: function (response) {
        $("#loading").hide();

        if (response.success) {
          const {
            reference,
            date_de_commande,
            nom_fournisseur,
            prenom_fournisseur,
            ligneCommande,
          } = response.data;
          // Mise à jour des champs
          $("#ref_commande").val(reference || "");
          $("#date_commande").val(date_de_commande || "");
          $("#id_fournisseur").val(
            `${nom_fournisseur} ${prenom_fournisseur}` || ""
          );
          const $tbody = $("#articles_table tbody");
          $tbody.empty();

          if (ligneCommande.length > 0) {
            ligneCommande.forEach((article) => {
              const maxReception = article.quantite - article.qte_recu;
              $tbody.append(`
                                <tr>
                                    <td>
                                        <span class="badge bg-primary text-wight">${
                                          article.nom
                                        }</span>
                                        <input type="hidden" name="id_magasin[${
                                          article.id_stock
                                        }]" value="${article.id_magasin}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" value="${
                                          article.nom_produit
                                        }" readonly>
                                        <input type="hidden" name="id_stock[${
                                          article.id_stock
                                        }]" value="${article.id_stock}">
                                        <input type="hidden" name="prix_achat[${
                                          article.id_stock
                                        }]" value="${article.prix_achat}">
                                    </td>                                           
                                    <td>
                                        <input type="number" class="form-control" value="${
                                          article.quantite_diponible
                                        }" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" value="${
                                          article.quantite
                                        }" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" value="${
                                          article.qte_recu
                                        }" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control qte-reception-actuelle" name="recep_act[${
                                          article.id_stock
                                        }]" 
                                            min="0" max="${maxReception}" value="${maxReception}" 
                                            ${
                                              maxReception <= 0
                                                ? "disabled"
                                                : ""
                                            }>
                                    </td>
                                </tr>
                            `);
            });
          } else {
            $tbody.append(
              '<tr><td colspan="6">Aucun article trouvé.</td></tr>'
            );
          }

          $("#valider-btn").hide();
        } else {
          alert("Erreur : " + response.message);
        }
      },
      error: function (xhr, status, error) {
        $("#loading").hide();
        alert("Une erreur est survenue.");
        console.error(error);
      },
    });
  });

  $(document).on("input", ".qte-reception-actuelle", function () {
    const $this = $(this);
    let currentValue = parseInt($this.val());

    // Si la valeur est négative, la convertir en positive
    if (currentValue < 0) {
      currentValue = Math.abs(currentValue);
      $this.val(currentValue);
    }

    const maxReception = parseInt($this.attr("max"));

    // Si la valeur dépasse le maximum, la ramener au maximum
    if (currentValue > maxReception) {
      $this.val(maxReception);
    }

    // Vérifier s'il y a au moins une entrée valide
    const atLeastOneValid = $(".qte-reception-actuelle")
      .toArray()
      .some((input) => {
        const val = parseInt($(input).val());
        return val > 0 && val <= parseInt($(input).attr("max"));
      });

    // Basculer l'affichage du bouton "valider"
    $("#valider-btn").toggle(atLeastOneValid);
  });
});
