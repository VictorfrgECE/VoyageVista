<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/fonctions.php';

// Compter les notifications non lues si l'utilisateur est connecté
$nbNotifs = 0;
if (estConnecte() && isset($pdo)) {
    $nbNotifs = compterNotificationsNonLues($pdo, $_SESSION['utilisateur_id']);
}

// Compter les articles dans le panier
$nbPanier = count(getPanier());

// Détecter la page active pour surligner le lien
$pageActuelle = basename($_SERVER['PHP_SELF']);
$dossierActuel = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titrePage) ? securiser($titrePage) . ' — ' : '' ?><?= NOM_SITE ?></title>
    <meta name="description" content="<?= isset($descriptionPage) ? securiser($descriptionPage) : 'VoyageVista — La plateforme de voyage pensée pour les étudiants en mobilité internationale.' ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Lato:wght@400;700&display=swap" rel="stylesheet">

    <!-- Feuille de style principale -->
    <link rel="stylesheet" href="<?= URL_BASE ?>/css/style.css">
</head>
<body>

<header>
    <nav class="navbar" role="navigation" aria-label="Navigation principale">
        <div class="container navbar-inner">

            <!-- Logo -->
            <a href="<?= URL_BASE ?>/index.php" class="navbar-logo" aria-label="<?= NOM_SITE ?> — Accueil">
                ✈ Voyage<span>Vista</span>
            </a>

            <!-- Liens principaux (desktop) -->
            <ul class="navbar-liens" role="list">
                <li>
                    <a href="<?= URL_BASE ?>/index.php"
                       class="<?= $pageActuelle === 'index.php' ? 'actif' : '' ?>">
                        Accueil
                    </a>
                </li>
                <li>
                    <a href="<?= URL_BASE ?>/destinations/liste.php"
                       class="<?= $dossierActuel === 'destinations' ? 'actif' : '' ?>">
                        Destinations
                    </a>
                </li>
                <li>
                    <a href="<?= URL_BASE ?>/transports/recherche.php"
                       class="<?= $dossierActuel === 'transports' ? 'actif' : '' ?>">
                        Transports
                    </a>
                </li>
                <li>
                    <a href="<?= URL_BASE ?>/hebergements/liste.php"
                       class="<?= $dossierActuel === 'hebergements' ? 'actif' : '' ?>">
                        Hébergements
                    </a>
                </li>
                <li>
                    <a href="<?= URL_BASE ?>/activites/liste.php"
                       class="<?= $dossierActuel === 'activites' ? 'actif' : '' ?>">
                        Activités
                    </a>
                </li>

                <?php if (estConnecte()): ?>
                    <li>
                        <a href="<?= URL_BASE ?>/itineraires/mon_itineraire.php"
                           class="<?= $dossierActuel === 'itineraires' ? 'actif' : '' ?>">
                            Mon Itinéraire
                        </a>
                    </li>

                    <?php if (estEtudiant() || estAdmin()): ?>
                        <li>
                            <a href="<?= URL_BASE ?>/etudiant/universites.php"
                               class="<?= $dossierActuel === 'etudiant' ? 'actif' : '' ?>">
                                Espace Étudiant
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (estPrestataire() || estAdmin()): ?>
                        <li>
                            <a href="<?= URL_BASE ?>/prestataire/index.php"
                               class="<?= $dossierActuel === 'prestataire' ? 'actif' : '' ?>">
                                Espace Prestataire
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (estAdmin()): ?>
                        <li>
                            <a href="<?= URL_BASE ?>/admin/index.php"
                               class="<?= $dossierActuel === 'admin' ? 'actif' : '' ?>">
                                Administration
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- Partie droite : panier + auth -->
            <div class="navbar-auth">
                <?php if (estConnecte()): ?>

                    <!-- Panier -->
                    <a href="<?= URL_BASE ?>/reservations/panier.php"
                       class="btn btn-secondaire btn-petit navbar-panier"
                       aria-label="Panier (<?= $nbPanier ?> article<?= $nbPanier > 1 ? 's' : '' ?>)">
                        🛒
                        <?php if ($nbPanier > 0): ?>
                            <span class="badge-panier"><?= $nbPanier ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Notifications -->
                    <a href="<?= URL_BASE ?>/notifications/mes_notifications.php"
                       class="btn btn-secondaire btn-petit lien-notif"
                       aria-label="Notifications (<?= $nbNotifs ?> non lue<?= $nbNotifs > 1 ? 's' : '' ?>)">
                        🔔
                        <?php if ($nbNotifs > 0): ?>
                            <span class="badge-notif"><?= $nbNotifs ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Nom utilisateur + déconnexion -->
                    <span style="font-size:0.85rem; color:var(--texte-doux); font-weight:600;">
                        👤 <?= securiser($_SESSION['nom'] ?? 'Utilisateur') ?>
                    </span>
                    <a href="<?= URL_BASE ?>/auth/deconnexion.php" class="btn btn-danger btn-petit">
                        Déconnexion
                    </a>

                <?php else: ?>
                    <a href="<?= URL_BASE ?>/auth/connexion.php" class="btn btn-secondaire btn-petit">
                        Connexion
                    </a>
                    <a href="<?= URL_BASE ?>/auth/inscription.php" class="btn btn-primaire btn-petit">
                        S'inscrire
                    </a>
                <?php endif; ?>
            </div>

            <!-- Bouton hamburger (mobile) -->
            <button class="navbar-toggle"
                    id="navbarToggle"
                    aria-label="Ouvrir le menu"
                    aria-expanded="false"
                    aria-controls="navbarMenuMobile">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div><!-- /.navbar-inner -->

        <!-- Menu mobile déroulant -->
        <div class="navbar-menu-mobile" id="navbarMenuMobile" role="menu">
            <a href="<?= URL_BASE ?>/index.php">🏠 Accueil</a>
            <a href="<?= URL_BASE ?>/destinations/liste.php">🌍 Destinations</a>
            <a href="<?= URL_BASE ?>/transports/recherche.php">✈ Transports</a>
            <a href="<?= URL_BASE ?>/hebergements/liste.php">🏨 Hébergements</a>
            <a href="<?= URL_BASE ?>/activites/liste.php">🎭 Activités</a>

            <?php if (estConnecte()): ?>
                <a href="<?= URL_BASE ?>/itineraires/mon_itineraire.php">🗺 Mon Itinéraire</a>
                <a href="<?= URL_BASE ?>/reservations/panier.php">🛒 Panier (<?= $nbPanier ?>)</a>
                <a href="<?= URL_BASE ?>/notifications/mes_notifications.php">🔔 Notifications (<?= $nbNotifs ?>)</a>

                <?php if (estEtudiant() || estAdmin()): ?>
                    <a href="<?= URL_BASE ?>/etudiant/universites.php">🎓 Espace Étudiant</a>
                <?php endif; ?>

                <?php if (estPrestataire() || estAdmin()): ?>
                    <a href="<?= URL_BASE ?>/prestataire/index.php">🏢 Espace Prestataire</a>
                <?php endif; ?>

                <?php if (estAdmin()): ?>
                    <a href="<?= URL_BASE ?>/admin/index.php">⚙ Administration</a>
                <?php endif; ?>

                <a href="<?= URL_BASE ?>/auth/deconnexion.php" class="btn btn-danger">Déconnexion</a>
            <?php else: ?>
                <a href="<?= URL_BASE ?>/auth/connexion.php" class="btn btn-secondaire">Connexion</a>
                <a href="<?= URL_BASE ?>/auth/inscription.php" class="btn btn-primaire">S'inscrire</a>
            <?php endif; ?>
        </div>

    </nav>
</header>

<main>
