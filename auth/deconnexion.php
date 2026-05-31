<?php
// ================================================================
//  VoyageVista — Déconnexion
//  Détruit la session et redirige vers la page d'accueil
// ================================================================
session_start();

// Vider toutes les variables de session
session_unset();

// Supprimer le cookie de session côté navigateur
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Détruire la session côté serveur
session_destroy();

// Démarrer une nouvelle session vide (pour les messages flash, etc.)
session_start();
$_SESSION['message_deconnexion'] = 'Vous avez été déconnecté avec succès.';

// Redirection vers l'accueil
header('Location: ../index.php');
exit;
