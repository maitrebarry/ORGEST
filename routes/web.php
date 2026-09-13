<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BaremeController;
use App\Http\Controllers\BureauController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationAchatController;
use App\Http\Controllers\OperationVenteController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TresorerieController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('home');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::middleware('permission:clients.voir')->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    });

    Route::middleware('permission:clients.creer')->group(function () {
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    });

    Route::middleware('permission:clients.modifier')->group(function () {
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    });

    Route::middleware('permission:clients.supprimer')->group(function () {
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });

    Route::middleware('permission:bareme.voir')->group(function () {
        Route::get('/bareme', [BaremeController::class, 'index'])->name('baremes.index');
        Route::get('/bareme/pdf', [BaremeController::class, 'pdf'])->name('baremes.pdf.active');
        Route::get('/bareme/{bareme}', [BaremeController::class, 'show'])->name('baremes.show');
        Route::get('/bareme/{bareme}/pdf', [BaremeController::class, 'pdf'])->name('baremes.pdf');
    });

    Route::middleware('permission:bareme.gerer')->group(function () {
        Route::get('/bareme-nouvelle-version', [BaremeController::class, 'create'])->name('baremes.create');
        Route::post('/bareme', [BaremeController::class, 'store'])->name('baremes.store');
        Route::get('/bareme/{bareme}/modifier', [BaremeController::class, 'edit'])->name('baremes.edit');
        Route::put('/bareme/{bareme}', [BaremeController::class, 'update'])->name('baremes.update');
        Route::put('/bareme-ligne/{ligne}', [BaremeController::class, 'updateLigne'])->name('baremes.lignes.update');
    });

    Route::middleware('permission:achats.voir')->group(function () {
        Route::get('/achats', [OperationAchatController::class, 'index'])->name('achats.index');
        Route::get('/achats/{achat}', [OperationAchatController::class, 'show'])->name('achats.show');
        Route::get('/achats/{achat}/pdf', [OperationAchatController::class, 'pdf'])->name('achats.pdf');
    });

    Route::middleware('permission:achats.creer')->group(function () {
        Route::get('/achats-nouveau', [OperationAchatController::class, 'create'])->name('achats.create');
        Route::post('/achats', [OperationAchatController::class, 'store'])->name('achats.store');
    });

    Route::middleware('permission:achats.valider')->group(function () {
        Route::patch('/achats/{achat}/paiement', [OperationAchatController::class, 'enregistrerPaiement'])->name('achats.paiement');
        Route::patch('/achats/{achat}/barres/{barre}', [OperationAchatController::class, 'modifierBarre'])->name('achats.barres.update');
    });

    Route::middleware('permission:achats.annuler')->group(function () {
        Route::patch('/achats/{achat}/annuler', [OperationAchatController::class, 'annuler'])->name('achats.annuler');
    });

    Route::middleware('permission:achats.voir')->group(function () {
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    });

    Route::middleware('permission:audit.voir')->group(function () {
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    });

    Route::middleware('permission:ventes.voir')->group(function () {
        Route::get('/ventes', [OperationVenteController::class, 'index'])->name('ventes.index');
        Route::get('/ventes/{vente}', [OperationVenteController::class, 'show'])->name('ventes.show');
        Route::get('/ventes/{vente}/pdf', [OperationVenteController::class, 'pdf'])->name('ventes.pdf');
    });

    Route::middleware('permission:ventes.creer')->group(function () {
        Route::get('/ventes-nouveau', [OperationVenteController::class, 'create'])->name('ventes.create');
        Route::post('/ventes', [OperationVenteController::class, 'store'])->name('ventes.store');
    });

    Route::middleware('permission:ventes.valider')->group(function () {
        Route::patch('/ventes/{vente}/paiement', [OperationVenteController::class, 'enregistrerPaiement'])->name('ventes.paiement');
        Route::patch('/ventes/{vente}/barres/{barre}', [OperationVenteController::class, 'modifierBarre'])->name('ventes.barres.update');
    });

    Route::middleware('permission:ventes.annuler')->group(function () {
        Route::patch('/ventes/{vente}/annuler', [OperationVenteController::class, 'annuler'])->name('ventes.annuler');
    });

    Route::middleware('permission:fonds.voir')->group(function () {
        Route::get('/tresorerie', [TresorerieController::class, 'index'])->name('tresorerie.index');
    });

    Route::middleware('permission:fonds.gerer')->group(function () {
        Route::post('/tresorerie/ouvrir', [TresorerieController::class, 'ouvrir'])->name('tresorerie.ouvrir');
        Route::post('/tresorerie/{journee}/approvisionnement', [TresorerieController::class, 'approvisionner'])->name('tresorerie.approvisionner');
        Route::post('/tresorerie/{journee}/mouvement', [TresorerieController::class, 'mouvement'])->name('tresorerie.mouvement');
        Route::patch('/tresorerie/{journee}/fermer', [TresorerieController::class, 'fermer'])->name('tresorerie.fermer');
        Route::delete('/tresorerie/mouvement/{mouvement}', [TresorerieController::class, 'destroyMouvement'])->name('tresorerie.mouvement.destroy');
    });

    Route::middleware('permission:utilisateurs.voir')->group(function () {
        Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
    });

    Route::middleware('permission:utilisateurs.creer')->group(function () {
        Route::post('/utilisateurs', [UserController::class, 'store'])->name('users.store');
    });

    Route::middleware('permission:utilisateurs.modifier')->group(function () {
        Route::put('/utilisateurs/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('permission:utilisateurs.desactiver')->group(function () {
        Route::patch('/utilisateurs/{user}/desactiver', [UserController::class, 'toggleActif'])->name('users.toggle-actif');
    });

    Route::middleware('permission:utilisateurs.supprimer')->group(function () {
        Route::delete('/utilisateurs/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Le référentiel des permissions (catalogue + création) est réservé au superadmin.
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    });

    Route::middleware('permission:roles.gerer')->group(function () {
        Route::get('/assigner-permissions', [UserPermissionController::class, 'index'])->name('user-permissions.index');
        Route::put('/assigner-permissions/{user}', [UserPermissionController::class, 'update'])->name('user-permissions.update');
    });

    // Gestion des bureaux : réservée au superadmin (création d'un bureau ET
    // de son propriétaire, dans le même geste).
    Route::middleware('permission:bureaux.gerer')->group(function () {
        Route::get('/bureaux', [BureauController::class, 'index'])->name('bureaux.index');
        Route::post('/bureaux', [BureauController::class, 'store'])->name('bureaux.store');
        Route::get('/bureaux/{bureau}/modifier', [BureauController::class, 'edit'])->name('bureaux.edit');
        Route::put('/bureaux/{bureau}', [BureauController::class, 'update'])->name('bureaux.update');
    });

    // Le propriétaire ne peut modifier QUE le logo de son propre bureau,
    // depuis un modal (« Mon bureau » dans Configuration) — pas de page dédiée.
    Route::middleware('permission:bureaux.logo')->group(function () {
        Route::post('/mon-bureau/logo', [BureauController::class, 'updateLogo'])->name('bureaux.logo.update');
    });
});
