# ORGEST

Application de gestion d'achat, de stock et de vente d'or, développée pour **Computer Service Barry (CSB)**.

Basée sur le même template, la même architecture et les mêmes conventions que le projet Wari-nioumas :
Laravel 13, Blade, Bootstrap 5, DataTables, SweetAlert2, spatie/laravel-permission.

## Développement en cours

Le développement se fait progressivement, phase par phase, en s'appuyant sur le cahier des charges CSB
(achat/vente d'or, barème densité → carat, stock, trésorerie, audit journalier).

**Phase 1 — Base technique** (en place) : authentification, utilisateurs, rôles et permissions.

Les phases suivantes (clients, barème, achats, stock, ventes, trésorerie, audit, facturation) seront
ajoutées au fur et à mesure, une fois chaque règle métier confirmée.

## Installation locale

```bash
composer install
npm install
cp .env.example .env   # puis ajuster DB_* si besoin
php artisan key:generate
php artisan migrate --seed
npm run build
```

Compte superadmin créé par défaut (à changer en production) : téléphone `74745669`, mot de passe `superadmin74`.
