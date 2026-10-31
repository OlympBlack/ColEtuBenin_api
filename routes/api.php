<?php

use App\Http\Controllers\Api\AnnonceController;
use App\Http\Controllers\Api\AvisController;
use App\Http\Controllers\Api\AdminKycController;
use App\Http\Controllers\Api\AnnonceMessageController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\ManagedAnnonceController;
use App\Http\Controllers\Api\ManagedPubliciteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\PubliciteBoutiqueController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ControleurForfait;
use App\Http\Controllers\Api\ControleurPortefeuille;
use App\Http\Controllers\Api\ControleurAnnonce;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/annonces', [AnnonceController::class, 'index']);
Route::get('/annonces/{annonce}', [AnnonceController::class, 'show']);
Route::get('/annonces/{annonce}/similaires', [AnnonceController::class, 'similar']);
// Avis etudiants "je cherche une chambre"
Route::get('/avis', [AvisController::class, 'index']);
Route::get('/avis/{avis}', [AvisController::class, 'show']);

Route::get('/push/public-key', [PushSubscriptionController::class, 'publicKey']);
Route::get('/universites', [\App\Http\Controllers\Api\UniversiteController::class, 'index']);
Route::get('/quartiers', [\App\Http\Controllers\Api\QuartierController::class, 'index']);
Route::get('/publicites-boutiques', [PubliciteBoutiqueController::class, 'index']);
Route::post('/publicites-boutiques/{publicite}/click', [PubliciteBoutiqueController::class, 'click']);

// Collections publiques
Route::get('/collections', [\App\Http\Controllers\Api\CollectionImmobiliereController::class, 'index']);
Route::get('/collections/{slug}', [\App\Http\Controllers\Api\CollectionImmobiliereController::class, 'show']);

// Auth Public
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1')->name('api.register');
Route::post('/verify-otp', [OtpController::class, 'verify'])->middleware('throttle:8,1')->name('api.verify-otp');
Route::post('/resend-otp', [OtpController::class, 'resend'])->middleware('throttle:3,1')->name('api.resend-otp');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:8,1')->name('api.login');
Route::get('/auth/validation/{id}/{hash}', [RegisterController::class, 'validateAccountFromEmail'])->name('api.auth.validation');
Route::get('/auth/validate-account/{id}/{hash}', [RegisterController::class, 'validateAccountFromEmail'])->name('api.auth.validate-account');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [ProfileController::class, 'show']);
    Route::patch('/me', [ProfileController::class, 'update']);
    Route::patch('/me/password', [ProfileController::class, 'updatePassword']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/kyc/documents', [KycController::class, 'store']);
    Route::post('/annonces/{annonce}/favori', [FavoriteController::class, 'store']);
    Route::delete('/annonces/{annonce}/favori', [FavoriteController::class, 'destroy']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('/push/subscriptions', [PushSubscriptionController::class, 'destroy']);
    Route::post('/profile/complete', [ProfileController::class, 'completeProfile']);

    // Avis etudiants : publication et discussion avec les demarcheurs
    Route::get('/mes-avis', [AvisController::class, 'mine']);
    Route::post('/avis', [AvisController::class, 'store']);
    Route::match(['put', 'patch'], '/avis/{avis}', [AvisController::class, 'update']);
    Route::delete('/avis/{avis}', [AvisController::class, 'destroy']);
    Route::post('/avis/{avis}/contact', [AvisController::class, 'contact']);

    // Messagerie & discussions (accessible aux participants de la conversation)
    Route::get('/conversations/{conversation}', [AnnonceMessageController::class, 'showConversation']);
    Route::post('/conversations/{conversation}/messages', [AnnonceMessageController::class, 'storeConversation']);
    Route::patch('/conversations/{conversation}/read', [AnnonceMessageController::class, 'markConversationAsRead']);

    Route::middleware('verified.user')->group(function () {
        Route::post('/annonces', [ControleurAnnonce::class, 'store']);
        Route::get('/forfaits-publications', [ControleurForfait::class, 'index']);
        Route::post('/achats-forfaits', [ControleurForfait::class, 'store']);
        Route::get('/portefeuille-publications', [ControleurPortefeuille::class, 'show']);

        Route::get('/annonces/{annonce}/messages', [AnnonceMessageController::class, 'index']);
        Route::post('/annonces/{annonce}/messages', [AnnonceMessageController::class, 'store']);
        Route::patch('/annonces/{annonce}', [ManagedAnnonceController::class, 'update']);
        Route::delete('/annonces/{annonce}', [ManagedAnnonceController::class, 'destroy']);
        Route::patch('/annonces/{annonce}/status', [ManagedAnnonceController::class, 'updateStatus']);
        Route::post('/publicites-boutiques', [ManagedPubliciteController::class, 'store']);
    });

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::patch('/users/{user}/toggle-validation', [\App\Http\Controllers\Api\AdminController::class, 'toggleUserValidation']);
        Route::delete('/users/{user}', [\App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
        Route::get('/annonces', [\App\Http\Controllers\Api\AdminController::class, 'annonces']);
        Route::delete('/annonces/{annonce}', [\App\Http\Controllers\Api\AdminController::class, 'deleteAnnonce']);
        Route::patch('/annonces/{annonce}/status', [\App\Http\Controllers\Api\AdminController::class, 'toggleAnnonceStatus']);
        Route::patch('/annonces/{annonce}/publication-status', [\App\Http\Controllers\Api\AdminController::class, 'updatePublicationStatus']);
        Route::get('/finances', [\App\Http\Controllers\Api\AdminController::class, 'finances']);
        Route::get('/kyc/submissions', [AdminKycController::class, 'index']);
        Route::patch('/kyc/{user}', [AdminKycController::class, 'review']);

        // Collections CRUD for Admin
        Route::get('/collections', [\App\Http\Controllers\Api\AdminCollectionController::class, 'index']);
        Route::post('/collections', [\App\Http\Controllers\Api\AdminCollectionController::class, 'store']);
        Route::get('/collections/{id}', [\App\Http\Controllers\Api\AdminCollectionController::class, 'show']);
        Route::match(['put', 'patch', 'post'], '/collections/{id}', [\App\Http\Controllers\Api\AdminCollectionController::class, 'update']);
        Route::delete('/collections/{id}', [\App\Http\Controllers\Api\AdminCollectionController::class, 'destroy']);
        Route::patch('/collections/{id}/toggle-status', [\App\Http\Controllers\Api\AdminCollectionController::class, 'toggleStatus']);

        // Modération des Signalements
        Route::get('/signalements', [\App\Http\Controllers\Api\SignalementController::class, 'index']);
        Route::patch('/signalements/{id}', [\App\Http\Controllers\Api\SignalementController::class, 'update']);
        Route::delete('/signalements/{id}', [\App\Http\Controllers\Api\SignalementController::class, 'destroy']);

        // Gestion des Messages de Contact / Support
        Route::get('/contacts', [\App\Http\Controllers\Api\ContactController::class, 'index']);
        Route::patch('/contacts/{id}', [\App\Http\Controllers\Api\ContactController::class, 'update']);
        Route::delete('/contacts/{id}', [\App\Http\Controllers\Api\ContactController::class, 'destroy']);

        // Paramétrage dynamique de la plateforme
        Route::get('/settings', [\App\Http\Controllers\Api\ParametrePlateformeController::class, 'show']);
        Route::match(['post', 'put'], '/settings', [\App\Http\Controllers\Api\ParametrePlateformeController::class, 'update']);
        Route::post('/settings/reset', [\App\Http\Controllers\Api\ParametrePlateformeController::class, 'reset']);
    });

    Broadcast::routes();
});

// Public: Paramètres généraux de la plateforme
Route::get('/settings', [\App\Http\Controllers\Api\ParametrePlateformeController::class, 'show']);

// Public & Hybrid: Signaler une annonce & Contacter le support
Route::post('/annonces/{annonce}/signaler', [\App\Http\Controllers\Api\SignalementController::class, 'store']);
Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'store']);
