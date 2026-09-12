<?php

/*
|--------------------------------------------------------------------------
| Devise par pays
|--------------------------------------------------------------------------
|
| Utilisé pour adapter automatiquement l'affichage monétaire au pays du
| bureau (App\Models\Bureau::pays), sans que l'utilisateur ait à choisir une
| devise séparément. Liste volontairement centrée sur l'Afrique de l'Ouest
| et du Centre (zone d'activité initiale) + quelques pays majeurs, pas
| exhaustive — Mali/XOF/FCFA reste le repli par défaut si un bureau n'a pas
| de pays renseigné ou si son pays n'est pas dans cette liste.
|
*/

return [
    // Zone UEMOA (franc CFA ouest-africain, XOF)
    'Mali' => ['code' => 'XOF', 'symbole' => 'FCFA'],
    'Sénégal' => ['code' => 'XOF', 'symbole' => 'FCFA'],
    'Côte d\'Ivoire' => ['code' => 'XOF', 'symbole' => 'FCFA'],
    'Burkina Faso' => ['code' => 'XOF', 'symbole' => 'FCFA'],
    'Niger' => ['code' => 'XOF', 'symbole' => 'FCFA'],
    'Bénin' => ['code' => 'XOF', 'symbole' => 'FCFA'],
    'Togo' => ['code' => 'XOF', 'symbole' => 'FCFA'],
    'Guinée-Bissau' => ['code' => 'XOF', 'symbole' => 'FCFA'],

    // Zone CEMAC (franc CFA centrafricain, XAF — même symbole, code différent)
    'Cameroun' => ['code' => 'XAF', 'symbole' => 'FCFA'],
    'Gabon' => ['code' => 'XAF', 'symbole' => 'FCFA'],
    'Tchad' => ['code' => 'XAF', 'symbole' => 'FCFA'],
    'Congo' => ['code' => 'XAF', 'symbole' => 'FCFA'],
    'République centrafricaine' => ['code' => 'XAF', 'symbole' => 'FCFA'],
    'Guinée équatoriale' => ['code' => 'XAF', 'symbole' => 'FCFA'],

    // Autres pays d'Afrique fréquemment concernés par le commerce de l'or
    'Guinée' => ['code' => 'GNF', 'symbole' => 'GNF'],
    'Nigeria' => ['code' => 'NGN', 'symbole' => '₦'],
    'Ghana' => ['code' => 'GHS', 'symbole' => 'GH₵'],
    'Mauritanie' => ['code' => 'MRU', 'symbole' => 'MRU'],
    'Maroc' => ['code' => 'MAD', 'symbole' => 'DH'],
    'Algérie' => ['code' => 'DZD', 'symbole' => 'DA'],
    'Tunisie' => ['code' => 'TND', 'symbole' => 'DT'],
    'Égypte' => ['code' => 'EGP', 'symbole' => 'EGP'],
    'Afrique du Sud' => ['code' => 'ZAR', 'symbole' => 'R'],

    // Grandes places internationales de l'or
    'Émirats arabes unis' => ['code' => 'AED', 'symbole' => 'AED'],
    'Arabie saoudite' => ['code' => 'SAR', 'symbole' => 'SAR'],
    'France' => ['code' => 'EUR', 'symbole' => '€'],
    'Belgique' => ['code' => 'EUR', 'symbole' => '€'],
    'Suisse' => ['code' => 'CHF', 'symbole' => 'CHF'],
    'Royaume-Uni' => ['code' => 'GBP', 'symbole' => '£'],
    'États-Unis' => ['code' => 'USD', 'symbole' => '$'],
    'Chine' => ['code' => 'CNY', 'symbole' => '¥'],
    'Inde' => ['code' => 'INR', 'symbole' => '₹'],
];
