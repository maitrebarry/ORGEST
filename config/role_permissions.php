<?php

/*
|--------------------------------------------------------------------------
| Permissions par défaut par rôle
|--------------------------------------------------------------------------
|
| Les rôles ne donnent PAS automatiquement de permissions (voir RoleSeeder :
| aucune permission n'est attachée aux rôles). Chaque utilisateur reçoit ses
| permissions individuellement. Ce tableau sert uniquement de MODÈLE : à la
| création d'un utilisateur, on lui attribue directement les permissions
| listées ici pour son rôle. Le propriétaire/superadmin peut ensuite les
| ajuster une par une via « Assigner permissions ». Les autorisations réelles
| de l'application (sidebar, boutons, routes) ne dépendent QUE des
| permissions directes de l'utilisateur.
|
| Le rôle « superadmin » n'apparaît pas ici : il contourne toutes les
| vérifications via Gate::before (voir AppServiceProvider). Il gère tout
| (bureaux, propriétaires, rôles/permissions globaux).
|
| Hiérarchie (application multi-bureaux) : le superadmin crée un bureau et
| son propriétaire ; le propriétaire crée et gère ses propres gérants
| (subalternes) pour son bureau. Seuls 3 rôles existent : superadmin,
| proprietaire, gerant.
|
*/

return [
    // Propriétaire d'un bureau (créé uniquement par le superadmin) : gère ses
    // propres gérants et l'activité de SON bureau. Ne peut ni créer d'autres
    // bureaux/propriétaires (permission « bureaux.gerer », réservée au
    // superadmin), ni gérer les rôles/permissions globaux.
    'proprietaire' => [
        'utilisateurs.voir',
        'utilisateurs.creer',
        'utilisateurs.modifier',
        'utilisateurs.desactiver',
        'roles.gerer',
        'clients.voir',
        'clients.creer',
        'clients.modifier',
        'clients.supprimer',
        'bareme.voir',
        'bareme.gerer',
        'achats.voir',
        'achats.creer',
        'achats.valider',
        'achats.annuler',
        'ventes.voir',
        'ventes.creer',
        'ventes.valider',
        'ventes.annuler',
        'fonds.voir',
        'fonds.gerer',
        'bureaux.logo',
        'audit.voir',
        'credits.voir',
        'credits.creer',
        'credits.gerer',
        'credits.annuler',
    ],

    // Gérant : subalterne créé par le propriétaire, gère l'activité
    // quotidienne du bureau (clients, achats, encaissements). Les actions
    // sensibles (supprimer un client, modifier le barème, annuler une
    // opération validée, gérer les utilisateurs/le bureau) restent réservées
    // au propriétaire et au superadmin.
    'gerant' => [
        'clients.voir',
        'clients.creer',
        'clients.modifier',
        'bareme.voir',
        'achats.voir',
        'achats.creer',
        'achats.valider',
        'ventes.voir',
        'ventes.creer',
        'ventes.valider',
        'fonds.voir',
        'fonds.gerer',
        'credits.voir',
        'credits.creer',
        'credits.gerer',
    ],
];
