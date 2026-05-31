<?php
// ================================================================
//  VoyageVista — Constantes globales
// ================================================================

// Informations du site
define('NOM_SITE',      'VoyageVista');
define('VERSION',       '1.0.0');
define('EMAIL_CONTACT', 'voyage.vista@gmail.com');
define('TEL_CONTACT',   '06 29 61 89 65');

// URL de base — détectée automatiquement pour fonctionner en local ET en production
// Retourne "http://localhost/voyagevista" en développement WAMP
define('URL_BASE', rtrim(
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . '/voyagevista',
    '/'
));

// Rôles utilisateurs
define('ROLE_ADMIN',    'admin');
define('ROLE_USER',     'user');
define('ROLE_ETUDIANT', 'etudiant');

// Nombre d'éléments par page (pagination)
define('ITEMS_PAR_PAGE', 12);

// Durée de session (en secondes) — 2 heures
define('SESSION_DUREE', 7200);
