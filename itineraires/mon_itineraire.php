<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

$uid = $_SESSION['utilisateur_id'];

// ----------------------------------------------------------------
//  Suppression d'un itinéraire (POST)
// ----------------------------------------------------------------
if (isset($_POST['supprimer']) && isset($_POST['itin_id'])) {
    $itinId = (int) $_POST['itin_id'];

    // Vérifier que l'itinéraire appartient bien à cet utilisateur
    $stmt = $pdo->prepare("SELECT id FROM ITINERAIRES WHERE id = :id AND utilisateur_id = :uid");
    $stmt->execute([':id' => $itinId, ':uid' => $uid]);

    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM ITINERAIRES WHERE id = :id")->execute([':id' => $itinId]);
    }

    rediriger('mon_itineraire.php');
}

// ----------------------------------------------------------------
//  Récupération des itinéraires de l'utilisateur
// ----------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT i.*,
            COUNT(ei.id) AS nb_elements
     FROM ITINERAIRES i
     LEFT JOIN ELEMENTS_ITINERAIRE ei ON ei.itineraire_id = i.id
     WHERE i.utilisateur_id = :uid
     GROUP BY i.id
     ORDER BY i.date_debut DESC"
);
$stmt->execute([':uid' => $uid]);
$itineraires = $stmt->fetchAll();

// Labels et couleurs des statuts
$statuts = [
    'brouillon' => ['label' => 'Brouillon',  'classe' => 'statut-brouillon'],
    'confirme'  => ['label' => 'Confirmé',   'classe' => 'statut-confirme'],
    'termine'   => ['label' => 'Terminé',    'classe' => 'statut-termine'],
    'annule'    => ['label' => 'Annulé',     'classe' => 'statut-annule'],
];

$titrePage = 'Mes itinéraires';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container flex-entre">
        <div>
            <h1>🗺 Mes itinéraires</h1>
            <p>Bonjour <?= securiser($_SESSION['nom']) ?> — gérez vos séjours planifiés</p>
        </div>
        <a href="creer.php" class="btn btn-orange btn-grand">+ Nouvel itinéraire</a>
    </div>
</div>

<div class="container section">

    <?php if (empty($itineraires)): ?>

        <!-- Aucun itinéraire -->
        <div class="boite centrer" style="padding:4rem 2rem;">
            <div style="font-size:4rem; margin-bottom:1rem;">🧳</div>
            <h2 style="margin-bottom:0.5rem;">Aucun itinéraire pour l'instant</h2>
            <p style="color:var(--texte-doux); margin-bottom:2rem;">
                Créez votre premier itinéraire et commencez à planifier votre aventure.
            </p>
            <a href="creer.php" class="btn btn-primaire btn-grand">Créer mon premier itinéraire</a>
        </div>

    <?php else: ?>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem;">
            <?php foreach ($itineraires as $itin):
                $info    = $statuts[$itin['statut']] ?? $statuts['brouillon'];
                $nbJours = (int) ceil((strtotime($itin['date_fin']) - strtotime($itin['date_debut'])) / 86400) + 1;
            ?>
                <div class="carte" style="border-top:4px solid var(--bleu);">
                    <div class="carte-corps">

                        <!-- En-tête carte -->
                        <div class="flex-entre" style="margin-bottom:0.75rem;">
                            <span class="statut <?= $info['classe'] ?>"><?= $info['label'] ?></span>
                            <span style="font-size:0.8rem; color:var(--texte-doux);">
                                <?= $nbJours ?> jour<?= $nbJours > 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <h3 style="font-size:1.1rem; margin-bottom:0.5rem;">
                            <?= securiser($itin['titre']) ?>
                        </h3>

                        <!-- Dates -->
                        <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:var(--texte-doux); margin-bottom:0.6rem;">
                            <span>📅</span>
                            <span>
                                <?= formatDate($itin['date_debut']) ?>
                                →
                                <?= formatDate($itin['date_fin']) ?>
                            </span>
                        </div>

                        <!-- Éléments + budget -->
                        <div style="display:flex; gap:1.25rem; font-size:0.85rem; margin-bottom:1.25rem;">
                            <span style="color:var(--texte-doux);">
                                📌 <?= (int)$itin['nb_elements'] ?> élément<?= $itin['nb_elements'] > 1 ? 's' : '' ?>
                            </span>
                            <?php if ($itin['budget_total'] > 0): ?>
                                <span style="font-weight:700; color:var(--bleu);">
                                    💶 <?= formatPrix((float)$itin['budget_total']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <a href="detail.php?id=<?= $itin['id'] ?>" class="btn btn-primaire btn-petit">
                                Voir le détail
                            </a>
                            <form action="mon_itineraire.php" method="POST"
                                  onsubmit="return confirm('Supprimer l\'itinéraire « <?= securiser($itin['titre']) ?> » ?')">
                                <input type="hidden" name="itin_id" value="<?= $itin['id'] ?>">
                                <button type="submit" name="supprimer" class="btn btn-danger btn-petit">
                                    Supprimer
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
