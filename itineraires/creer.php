<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// Page réservée aux utilisateurs connectés
if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

$erreur  = '';
$champs  = ['titre' => '', 'date_debut' => '', 'date_fin' => ''];

// ----------------------------------------------------------------
//  Traitement du formulaire
// ----------------------------------------------------------------
if (isset($_POST['creer_itineraire'])) {
    $titre     = isset($_POST['titre'])      ? trim($_POST['titre'])      : '';
    $dateDebut = isset($_POST['date_debut']) ? trim($_POST['date_debut']) : '';
    $dateFin   = isset($_POST['date_fin'])   ? trim($_POST['date_fin'])   : '';

    $champs = ['titre' => $titre, 'date_debut' => $dateDebut, 'date_fin' => $dateFin];

    // Validations
    if (empty($titre)) {
        $erreur = "Le titre de l'itinéraire est obligatoire.";

    } elseif (strlen($titre) < 3) {
        $erreur = "Le titre doit contenir au moins 3 caractères.";

    } elseif (empty($dateDebut) || empty($dateFin)) {
        $erreur = "Les dates de début et de fin sont obligatoires.";

    } elseif ($dateFin < $dateDebut) {
        $erreur = "La date de fin doit être égale ou postérieure à la date de début.";

    } elseif ($dateDebut < date('Y-m-d')) {
        $erreur = "La date de début ne peut pas être dans le passé.";

    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO ITINERAIRES (titre, date_debut, date_fin, statut, budget_total, utilisateur_id)
             VALUES (:titre, :debut, :fin, 'brouillon', 0.00, :uid)"
        );
        $stmt->execute([
            ':titre' => $titre,
            ':debut' => $dateDebut,
            ':fin'   => $dateFin,
            ':uid'   => $_SESSION['utilisateur_id'],
        ]);

        $newId = (int) $pdo->lastInsertId();

        // Notification de création
        creerNotification(
            $pdo,
            $_SESSION['utilisateur_id'],
            "Itinéraire créé : " . $titre,
            "Votre itinéraire \"$titre\" a été créé. Ajoutez des transports, hébergements et activités pour le compléter.",
            'info'
        );

        rediriger('detail.php?id=' . $newId);
    }
}

$titrePage = 'Créer un itinéraire';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🗺 Créer un itinéraire</h1>
        <p>Planifiez votre séjour étape par étape</p>
    </div>
</div>

<div class="container section">
    <div style="max-width:600px; margin:0 auto;">

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
        <?php endif; ?>

        <div class="boite">
            <form action="creer.php" method="POST" novalidate>

                <div class="champ-groupe">
                    <label for="titre">Nom de l'itinéraire *</label>
                    <input
                        type="text"
                        id="titre"
                        name="titre"
                        class="champ"
                        value="<?= securiser($champs['titre']) ?>"
                        placeholder="Ex : Erasmus à Barcelone — Été 2026"
                        required
                        maxlength="200"
                    >
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="champ-groupe">
                        <label for="date_debut">Date de départ *</label>
                        <input
                            type="date"
                            id="date_debut"
                            name="date_debut"
                            class="champ"
                            value="<?= securiser($champs['date_debut']) ?>"
                            min="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>
                    <div class="champ-groupe">
                        <label for="date_fin">Date de retour *</label>
                        <input
                            type="date"
                            id="date_fin"
                            name="date_fin"
                            class="champ"
                            value="<?= securiser($champs['date_fin']) ?>"
                            min="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>
                </div>

                <p style="font-size:0.83rem; color:var(--texte-doux); margin-bottom:1.5rem;">
                    * Champs obligatoires. Vous pourrez ajouter transports, hébergements et activités dans l'étape suivante.
                </p>

                <div style="display:flex; gap:0.75rem;">
                    <button type="submit" name="creer_itineraire" class="btn btn-primaire btn-grand">
                        Créer l'itinéraire →
                    </button>
                    <a href="mon_itineraire.php" class="btn btn-secondaire btn-grand">Annuler</a>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
// Synchroniser la date min de fin avec la date de début choisie
document.getElementById('date_debut').addEventListener('change', function () {
    const fin = document.getElementById('date_fin');
    fin.min = this.value;
    if (fin.value && fin.value < this.value) {
        fin.value = this.value;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
