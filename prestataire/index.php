<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estConnecte() || (!estPrestataire() && !estAdmin())) {
    rediriger('../index.php');
}

$titrePage = 'Espace Prestataire';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🏢 Espace Prestataire</h1>
        <p>Ajoutez et gérez vos offres d'hébergements et de transports</p>
    </div>
</div>

<div class="container section">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; max-width:700px; margin:0 auto;">

        <a href="ajouter_hebergement.php" style="text-decoration:none;">
            <div class="boite" style="text-align:center; padding:2.5rem; cursor:pointer; transition:box-shadow 0.2s;"
                 onmouseover="this.style.boxShadow='var(--ombre-forte)'"
                 onmouseout="this.style.boxShadow='var(--ombre)'">
                <div style="font-size:3rem; margin-bottom:0.75rem;">🏨</div>
                <h3 style="margin-bottom:0.5rem;">Ajouter un hébergement</h3>
                <p style="color:var(--texte-doux); font-size:0.88rem; margin-bottom:1.25rem;">
                    Hôtel, auberge, appartement, villa, camping…
                </p>
                <span class="btn btn-primaire">+ Ajouter</span>
            </div>
        </a>

        <a href="ajouter_transport.php" style="text-decoration:none;">
            <div class="boite" style="text-align:center; padding:2.5rem; cursor:pointer; transition:box-shadow 0.2s;"
                 onmouseover="this.style.boxShadow='var(--ombre-forte)'"
                 onmouseout="this.style.boxShadow='var(--ombre)'">
                <div style="font-size:3rem; margin-bottom:0.75rem;">✈</div>
                <h3 style="margin-bottom:0.5rem;">Ajouter un transport</h3>
                <p style="color:var(--texte-doux); font-size:0.88rem; margin-bottom:1.25rem;">
                    Avion, train, bus, ferry, voiture…
                </p>
                <span class="btn btn-primaire">+ Ajouter</span>
            </div>
        </a>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
