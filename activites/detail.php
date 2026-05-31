<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) rediriger('liste.php');

$stmt = $pdo->prepare(
    "SELECT a.*, d.nom AS dest_nom, d.pays AS dest_pays, d.id AS dest_id, d.categorie AS dest_cat
     FROM ACTIVITES a
     JOIN DESTINATIONS d ON d.id = a.destination_id
     WHERE a.id = :id"
);
$stmt->execute([':id' => $id]);
$a = $stmt->fetch();
if (!$a) rediriger('liste.php');

// Ajout au panier
$messageCart = '';
$erreurDate  = '';
if (isset($_POST['ajouter_panier']) && estConnecte()) {
    $dateActivite = isset($_POST['date_activite']) ? trim($_POST['date_activite']) : '';
    $nbPersonnes  = (isset($_POST['nb_personnes']) && ctype_digit($_POST['nb_personnes']))
                    ? max(1, min(20, (int)$_POST['nb_personnes'])) : 1;

    if (empty($dateActivite)) {
        $erreurDate = "Veuillez sélectionner une date.";
    } elseif (strtotime($dateActivite) < strtotime(date('Y-m-d'))) {
        $erreurDate = "La date ne peut pas être dans le passé.";
    } else {
        $prixTotal = $a['prix'] > 0 ? round((float)$a['prix'] * $nbPersonnes, 2) : 0.0;
        ajouterAuPanier('activite', $id, $prixTotal, $a['nom'], [
            'date_activite' => $dateActivite,
            'nb_personnes'  => $nbPersonnes,
            'prix_unitaire' => (float)$a['prix'],
        ]);
        $messageCart = securiser($a['nom']) . " ajouté au panier ! ({$nbPersonnes} personne" . ($nbPersonnes > 1 ? 's' : '') . ")";
    }
}

// Activités similaires (même destination)
$stmtSim = $pdo->prepare(
    "SELECT a2.*, d.nom AS dest_nom FROM ACTIVITES a2
     JOIN DESTINATIONS d ON d.id = a2.destination_id
     WHERE a2.destination_id = :dest AND a2.id != :id
     ORDER BY a2.prix ASC LIMIT 3"
);
$stmtSim->execute([':dest' => $a['destination_id'], ':id' => $id]);
$similaires = $stmtSim->fetchAll();

$emojisCategorie = [
    'culture'      => '🏛', 'sport'      => '🏄', 'gastronomie' => '🍽',
    'tourisme'     => '📸', 'bien-être'  => '🧘', 'aventure'    => '🧗',
    'nature'       => '🌿', 'nightlife'  => '🎵',
];

$titrePage = $a['nom'];
require_once '../includes/header.php';
?>

<div style="background:white; border-bottom:1px solid var(--bordure);">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= URL_BASE ?>/index.php">Accueil</a>
            <span class="breadcrumb-sep">›</span>
            <a href="liste.php">Activités</a>
            <span class="breadcrumb-sep">›</span>
            <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $a['dest_id'] ?>"><?= securiser($a['dest_nom']) ?></a>
            <span class="breadcrumb-sep">›</span>
            <span><?= securiser($a['nom']) ?></span>
        </nav>
    </div>
</div>

<?php if ($messageCart): ?>
    <div class="container" style="padding-top:1rem;">
        <div class="alerte alerte-succes">
            ✅ <?= $messageCart ?>
            — <a href="<?= URL_BASE ?>/reservations/panier.php">Voir le panier</a>
        </div>
    </div>
<?php endif; ?>

<div class="container section">
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:2rem; align-items:start;">

        <!-- Contenu principal -->
        <div>
            <div class="boite mb-3">

                <!-- Image principale -->
                <?php if (imageExiste($a['image_url'] ?? '')): ?>
                    <img
                        src="<?= srcImage($a['image_url']) ?>"
                        alt="<?= securiser($a['nom']) ?>"
                        style="width:100%; height:280px; object-fit:cover; border-radius:8px; margin-bottom:1.5rem;"
                        loading="lazy"
                    >
                <?php endif; ?>

                <!-- En-tête -->
                <div style="display:flex; align-items:flex-start; gap:1rem; margin-bottom:1.5rem;">
                    <div style="font-size:3.5rem; flex-shrink:0;">
                        <?= $emojisCategorie[$a['categorie']] ?? '🎭' ?>
                    </div>
                    <div>
                        <div style="font-size:0.82rem; color:var(--bleu); font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.3rem;">
                            <?= securiser($a['dest_nom']) ?>, <?= securiser($a['dest_pays']) ?>
                        </div>
                        <h1 style="font-size:1.6rem; margin-bottom:0.4rem;"><?= securiser($a['nom']) ?></h1>
                        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                            <span style="background:#FDF4FF; color:#9333EA; padding:0.2rem 0.65rem; border-radius:4px; font-size:0.82rem; font-weight:700;">
                                <?= $emojisCategorie[$a['categorie']] ?? '🎭' ?> <?= ucfirst(securiser($a['categorie'])) ?>
                            </span>
                            <span style="font-size:0.85rem; color:var(--texte-doux);">
                                ⏱ <?= securiser((string)$a['duree_heures']) ?>h
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <?php if ($a['description']): ?>
                    <p style="color:var(--texte-doux); line-height:1.8; font-size:0.95rem;">
                        <?= securiser($a['description']) ?>
                    </p>
                <?php endif; ?>

                <!-- Caractéristiques -->
                <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--bordure);">
                    <h3 style="font-size:1rem; margin-bottom:1rem;">Informations pratiques</h3>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; font-size:0.9rem;">
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span>🏷</span>
                            <span><strong>Catégorie :</strong> <?= ucfirst(securiser($a['categorie'])) ?></span>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span>⏱</span>
                            <span><strong>Durée :</strong> <?= securiser((string)$a['duree_heures']) ?> heure<?= $a['duree_heures'] > 1 ? 's' : '' ?></span>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span>📍</span>
                            <span><strong>Destination :</strong>
                                <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $a['dest_id'] ?>">
                                    <?= securiser($a['dest_nom']) ?>
                                </a>
                            </span>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span>💶</span>
                            <span><strong>Prix :</strong>
                                <?php if ($a['prix'] > 0): ?>
                                    <?= formatPrix((float)$a['prix']) ?> / personne
                                <?php else: ?>
                                    <span style="color:var(--succes); font-weight:700;">Gratuit</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activités similaires -->
            <?php if (!empty($similaires)): ?>
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Autres activités à <?= securiser($a['dest_nom']) ?></h2>
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">
                    <?php foreach ($similaires as $s): ?>
                        <a href="detail.php?id=<?= $s['id'] ?>" style="text-decoration:none; color:inherit;">
                            <div class="carte">
                                <div class="carte-image-placeholder" style="height:90px; font-size:1.8rem;">
                                    <?= $emojisCategorie[$s['categorie']] ?? '🎭' ?>
                                </div>
                                <div style="padding:0.75rem;">
                                    <div style="font-weight:700; font-size:0.85rem; margin-bottom:0.25rem;"><?= securiser($s['nom']) ?></div>
                                    <div style="font-size:0.8rem; color:var(--texte-doux); margin-bottom:0.25rem;">⏱ <?= securiser((string)$s['duree_heures']) ?>h</div>
                                    <div style="color:var(--bleu); font-weight:700; font-size:0.92rem;">
                                        <?= $s['prix'] > 0 ? formatPrix((float)$s['prix']) : '<span style="color:var(--succes)">Gratuit</span>' ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Colonne latérale -->
        <div style="position:sticky; top:80px;">
            <div class="boite" style="border:2px solid var(--bleu);">
                <div style="text-align:center; margin-bottom:1.25rem;">
                    <?php if ($a['prix'] > 0): ?>
                        <div style="font-size:0.82rem; color:var(--texte-doux); margin-bottom:0.25rem;">Prix par personne</div>
                        <div style="font-family:'Poppins',sans-serif; font-size:2.2rem; font-weight:800; color:var(--bleu);">
                            <?= formatPrix((float)$a['prix']) ?>
                        </div>
                    <?php else: ?>
                        <div style="font-family:'Poppins',sans-serif; font-size:1.8rem; font-weight:800; color:var(--succes);">
                            Gratuit 🎉
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (estConnecte()): ?>
                    <?php if ($erreurDate): ?>
                        <div class="alerte alerte-erreur" style="margin-bottom:0.75rem; font-size:0.85rem;"><?= securiser($erreurDate) ?></div>
                    <?php endif; ?>
                    <form action="detail.php?id=<?= $id ?>" method="POST">
                        <div class="champ-groupe" style="margin-bottom:0.6rem;">
                            <label for="date_activite" style="font-size:0.82rem; font-weight:600;">Date de l'activité</label>
                            <input type="date" id="date_activite" name="date_activite" class="champ"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <?php if ($a['prix'] > 0): ?>
                        <div class="champ-groupe" style="margin-bottom:0.6rem;">
                            <label for="nb_personnes" style="font-size:0.82rem; font-weight:600;">Nombre de personnes</label>
                            <input type="number" id="nb_personnes" name="nb_personnes" class="champ"
                                   min="1" max="20" value="1" required>
                        </div>
                        <div id="recap-total" style="font-size:0.82rem; color:var(--bleu); font-weight:700; margin-bottom:0.6rem;"></div>
                        <script>
                        (function () {
                            var nb = document.getElementById('nb_personnes');
                            var recap = document.getElementById('recap-total');
                            var prixUnit = <?= (float)$a['prix'] ?>;
                            function update() {
                                var n = parseInt(nb.value) || 1;
                                recap.textContent = n + ' × ' + prixUnit.toFixed(2).replace('.', ',') + ' € = ' + (n * prixUnit).toFixed(2).replace('.', ',') + ' €';
                            }
                            nb.addEventListener('input', update);
                            update();
                        }());
                        </script>
                        <?php else: ?>
                        <input type="hidden" name="nb_personnes" value="1">
                        <?php endif; ?>
                        <button type="submit" name="ajouter_panier" class="btn btn-orange btn-bloc btn-grand">
                            🛒 Ajouter au panier
                        </button>
                    </form>
                    <a href="<?= URL_BASE ?>/reservations/panier.php"
                       class="btn btn-secondaire btn-bloc mt-2">
                        Voir le panier
                    </a>
                <?php else: ?>
                    <a href="<?= URL_BASE ?>/auth/connexion.php" class="btn btn-primaire btn-bloc btn-grand">
                        Se connecter pour réserver
                    </a>
                <?php endif; ?>

                <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid var(--bordure); font-size:0.8rem; color:var(--texte-doux); text-align:center;">
                    🔒 Réservation sécurisée · Annulation possible
                </div>
            </div>

            <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $a['dest_id'] ?>"
               class="btn btn-secondaire btn-bloc mt-2">
                🌍 Voir la destination
            </a>
            <a href="liste.php" class="btn btn-secondaire btn-bloc mt-1">
                ← Toutes les activités
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
