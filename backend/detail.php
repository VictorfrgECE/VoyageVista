<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) rediriger('liste.php');

// Récupérer l'hébergement avec sa destination
$stmt = $pdo->prepare(
    "SELECT h.*, d.nom AS dest_nom, d.pays AS dest_pays, d.id AS dest_id, d.categorie AS dest_cat
     FROM HEBERGEMENTS h
     JOIN DESTINATIONS d ON d.id = h.destination_id
     WHERE h.id = :id"
);
$stmt->execute([':id' => $id]);
$h = $stmt->fetch();
if (!$h) rediriger('liste.php');

// Ajout au panier
$messageCart = '';
$erreurDate  = '';
if (isset($_POST['ajouter_panier']) && estConnecte()) {
    $dateArrivee = isset($_POST['date_arrivee']) ? trim($_POST['date_arrivee']) : '';
    $dateDepart  = isset($_POST['date_depart'])  ? trim($_POST['date_depart'])  : '';

    if (empty($dateArrivee) || empty($dateDepart)) {
        $erreurDate = "Veuillez sélectionner les dates d'arrivée et de départ.";
    } elseif (strtotime($dateArrivee) < strtotime(date('Y-m-d'))) {
        $erreurDate = "La date d'arrivée ne peut pas être dans le passé.";
    } elseif (strtotime($dateDepart) <= strtotime($dateArrivee)) {
        $erreurDate = "La date de départ doit être après la date d'arrivée.";
    } else {
        $nbNuits   = (int) ((strtotime($dateDepart) - strtotime($dateArrivee)) / 86400);
        $prixTotal = round((float)$h['prix_par_nuit'] * $nbNuits, 2);
        ajouterAuPanier('hebergement', $id, $prixTotal, $h['nom'], [
            'date_arrivee'  => $dateArrivee,
            'date_depart'   => $dateDepart,
            'nb_nuits'      => $nbNuits,
            'prix_unitaire' => (float)$h['prix_par_nuit'],
        ]);
        $messageCart = securiser($h['nom']) . " ajouté au panier ! ({$nbNuits} nuit" . ($nbNuits > 1 ? 's' : '') . ")";
    }
}

// Hébergements similaires (même destination, même type)
$stmtSim = $pdo->prepare(
    "SELECT h2.*, d.nom AS dest_nom FROM HEBERGEMENTS h2
     JOIN DESTINATIONS d ON d.id = h2.destination_id
     WHERE h2.destination_id = :dest AND h2.id != :id
     ORDER BY h2.prix_par_nuit ASC LIMIT 3"
);
$stmtSim->execute([':dest' => $h['destination_id'], ':id' => $id]);
$similaires = $stmtSim->fetchAll();

$icones = ['hotel' => '🏨', 'auberge' => '🏠', 'appartement' => '🏢', 'villa' => '🏡', 'camping' => '⛺', 'residence' => '🏫'];
$labels = ['hotel' => 'Hôtel', 'auberge' => 'Auberge de jeunesse', 'appartement' => 'Appartement', 'villa' => 'Villa', 'camping' => 'Camping', 'residence' => 'Résidence'];

$titrePage = $h['nom'];
require_once '../includes/header.php';
?>

<div style="background:white; border-bottom:1px solid var(--bordure);">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= URL_BASE ?>/index.php">Accueil</a>
            <span class="breadcrumb-sep">›</span>
            <a href="liste.php">Hébergements</a>
            <span class="breadcrumb-sep">›</span>
            <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $h['dest_id'] ?>"><?= securiser($h['dest_nom']) ?></a>
            <span class="breadcrumb-sep">›</span>
            <span><?= securiser($h['nom']) ?></span>
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
                <?php if (imageExiste($h['image_url'] ?? '')): ?>
                    <img
                        src="<?= srcImage($h['image_url']) ?>"
                        alt="<?= securiser($h['nom']) ?>"
                        style="width:100%; height:280px; object-fit:cover; border-radius:8px; margin-bottom:1.5rem;"
                        loading="lazy"
                    >
                <?php endif; ?>

                <!-- En-tête -->
                <div style="display:flex; align-items:flex-start; gap:1rem; margin-bottom:1.5rem;">
                    <div style="font-size:3.5rem; flex-shrink:0;"><?= $icones[$h['type']] ?? '🏠' ?></div>
                    <div>
                        <div style="font-size:0.82rem; color:var(--bleu); font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.3rem;">
                            <?= securiser($h['dest_nom']) ?>, <?= securiser($h['dest_pays']) ?>
                        </div>
                        <h1 style="font-size:1.6rem; margin-bottom:0.4rem;"><?= securiser($h['nom']) ?></h1>
                        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                            <span style="background:#EEF2FF; color:#4338CA; padding:0.2rem 0.65rem; border-radius:4px; font-size:0.82rem; font-weight:700;">
                                <?= $labels[$h['type']] ?? $h['type'] ?>
                            </span>
                            <?php if ($h['etoiles']): ?>
                                <?= afficherEtoiles((int)$h['etoiles']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <?php if ($h['description']): ?>
                    <p style="color:var(--texte-doux); line-height:1.8; font-size:0.95rem;">
                        <?= securiser($h['description']) ?>
                    </p>
                <?php endif; ?>

                <!-- Caractéristiques -->
                <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--bordure);">
                    <h3 style="font-size:1rem; margin-bottom:1rem;">Caractéristiques</h3>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; font-size:0.9rem;">
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span>🏷</span>
                            <span><strong>Type :</strong> <?= $labels[$h['type']] ?? $h['type'] ?></span>
                        </div>
                        <?php if ($h['etoiles']): ?>
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <span>⭐</span>
                                <span><strong>Classement :</strong> <?= $h['etoiles'] ?> étoile<?= $h['etoiles'] > 1 ? 's' : '' ?></span>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span>📍</span>
                            <span><strong>Destination :</strong>
                                <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $h['dest_id'] ?>">
                                    <?= securiser($h['dest_nom']) ?>
                                </a>
                            </span>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span>💶</span>
                            <span><strong>Prix :</strong> <?= formatPrix((float)$h['prix_par_nuit']) ?>/nuit</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hébergements similaires -->
            <?php if (!empty($similaires)): ?>
                <h2 style="font-size:1.1rem; margin-bottom:1rem;">Autres hébergements à <?= securiser($h['dest_nom']) ?></h2>
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">
                    <?php foreach ($similaires as $s): ?>
                        <a href="detail.php?id=<?= $s['id'] ?>" style="text-decoration:none; color:inherit;">
                            <div class="carte">
                                <div class="carte-image-placeholder" style="height:90px; font-size:1.8rem;">
                                    <?= $icones[$s['type']] ?? '🏠' ?>
                                </div>
                                <div style="padding:0.75rem;">
                                    <div style="font-weight:700; font-size:0.85rem; margin-bottom:0.25rem;"><?= securiser($s['nom']) ?></div>
                                    <div style="color:var(--bleu); font-weight:700; font-size:0.92rem;"><?= formatPrix((float)$s['prix_par_nuit']) ?>/nuit</div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Colonne latérale : réservation -->
        <div style="position:sticky; top:80px;">
            <div class="boite" style="border:2px solid var(--bleu);">
                <div style="text-align:center; margin-bottom:1.25rem;">
                    <div style="font-size:0.82rem; color:var(--texte-doux); margin-bottom:0.25rem;">Prix par nuit</div>
                    <div style="font-family:'Poppins',sans-serif; font-size:2.2rem; font-weight:800; color:var(--bleu);">
                        <?= formatPrix((float)$h['prix_par_nuit']) ?>
                    </div>
                </div>

                <?php if (estConnecte()): ?>
                    <?php if ($erreurDate): ?>
                        <div class="alerte alerte-erreur" style="margin-bottom:0.75rem; font-size:0.85rem;"><?= securiser($erreurDate) ?></div>
                    <?php endif; ?>
                    <form action="detail.php?id=<?= $id ?>" method="POST">
                        <div class="champ-groupe" style="margin-bottom:0.6rem;">
                            <label for="date_arrivee" style="font-size:0.82rem; font-weight:600;">Arrivée</label>
                            <input type="date" id="date_arrivee" name="date_arrivee" class="champ"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="champ-groupe" style="margin-bottom:0.6rem;">
                            <label for="date_depart" style="font-size:0.82rem; font-weight:600;">Départ</label>
                            <input type="date" id="date_depart" name="date_depart" class="champ"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>
                        <div id="recap-nuits" style="font-size:0.82rem; color:var(--bleu); font-weight:700; margin-bottom:0.6rem; display:none;"></div>
                        <button type="submit" name="ajouter_panier" class="btn btn-orange btn-bloc btn-grand">
                            🛒 Ajouter au panier
                        </button>
                    </form>
                    <a href="<?= URL_BASE ?>/reservations/panier.php"
                       class="btn btn-secondaire btn-bloc mt-2">
                        Voir le panier
                    </a>
                    <script>
                    (function () {
                        var arr = document.getElementById('date_arrivee');
                        var dep = document.getElementById('date_depart');
                        var recap = document.getElementById('recap-nuits');
                        var prixNuit = <?= (float)$h['prix_par_nuit'] ?>;
                        function update() {
                            var a = new Date(arr.value), d = new Date(dep.value);
                            if (!isNaN(a) && !isNaN(d) && d > a) {
                                var nuits = Math.round((d - a) / 86400000);
                                recap.textContent = nuits + ' nuit' + (nuits > 1 ? 's' : '') + ' — ' + (nuits * prixNuit).toFixed(2).replace('.', ',') + ' €';
                                recap.style.display = 'block';
                                if (arr.value) dep.min = arr.value.replace(/(\d{4})-(\d{2})-(\d{2})/, function(m,y,mo,d2){ return y+'-'+mo+'-'+(parseInt(d2)+1).toString().padStart(2,'0'); });
                            } else {
                                recap.style.display = 'none';
                            }
                        }
                        arr.addEventListener('change', update);
                        dep.addEventListener('change', update);
                    }());
                    </script>
                <?php else: ?>
                    <a href="<?= URL_BASE ?>/auth/connexion.php" class="btn btn-primaire btn-bloc btn-grand">
                        Se connecter pour réserver
                    </a>
                <?php endif; ?>

                <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid var(--bordure); font-size:0.8rem; color:var(--texte-doux); text-align:center;">
                    🔒 Paiement sécurisé · Annulation possible
                </div>
            </div>

            <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $h['dest_id'] ?>"
               class="btn btn-secondaire btn-bloc mt-2">
                🌍 Voir la destination
            </a>
            <a href="liste.php" class="btn btn-secondaire btn-bloc mt-1">
                ← Tous les hébergements
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
