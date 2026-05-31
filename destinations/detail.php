<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// ----------------------------------------------------------------
//  Récupération de l'id et protection contre les entrées invalides
// ----------------------------------------------------------------
$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    rediriger('liste.php');
}

// ----------------------------------------------------------------
//  Destination principale
// ----------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM DESTINATIONS WHERE id = :id");
$stmt->execute([':id' => $id]);
$dest = $stmt->fetch();

if (!$dest) {
    rediriger('liste.php');
}

// ----------------------------------------------------------------
//  Ajout au panier (POST)
// ----------------------------------------------------------------
$messageCartSucces = '';
if (isset($_POST['ajouter_panier']) && estConnecte()) {
    $type        = $_POST['type']         ?? '';
    $refId       = isset($_POST['ref_id']) && ctype_digit($_POST['ref_id']) ? (int)$_POST['ref_id'] : 0;
    $prixItem    = isset($_POST['prix'])   && is_numeric($_POST['prix'])    ? (float)$_POST['prix']  : 0;
    $nomItem     = $_POST['nom_item']     ?? '';

    $typesValides = ['transport', 'hebergement', 'activite'];

    if (in_array($type, $typesValides) && $refId > 0 && $prixItem > 0) {
        ajouterAuPanier($type, $refId, $prixItem, $nomItem);
        $messageCartSucces = securiser($nomItem) . " ajouté au panier !";
    }
}

// ----------------------------------------------------------------
//  Données liées à cette destination (jointures)
// ----------------------------------------------------------------

// Transports disponibles
$stmtTransports = $pdo->prepare(
    "SELECT * FROM TRANSPORTS WHERE destination_id = :id ORDER BY prix ASC"
);
$stmtTransports->execute([':id' => $id]);
$transports = $stmtTransports->fetchAll();

// Hébergements
$stmtHebergements = $pdo->prepare(
    "SELECT * FROM HEBERGEMENTS WHERE destination_id = :id ORDER BY prix_par_nuit ASC"
);
$stmtHebergements->execute([':id' => $id]);
$hebergements = $stmtHebergements->fetchAll();

// Activités
$stmtActivites = $pdo->prepare(
    "SELECT * FROM ACTIVITES WHERE destination_id = :id ORDER BY prix ASC"
);
$stmtActivites->execute([':id' => $id]);
$activites = $stmtActivites->fetchAll();

// Universités Erasmus
$stmtUnivs = $pdo->prepare(
    "SELECT * FROM UNIVERSITES WHERE destination_id = :id ORDER BY nom"
);
$stmtUnivs->execute([':id' => $id]);
$universites = $stmtUnivs->fetchAll();

// Infos visa pour les ressortissants UE
$stmtVisa = $pdo->prepare(
    "SELECT * FROM INFOS_VISA WHERE destination_id = :id AND zone_nationalite = 'UE' LIMIT 1"
);
$stmtVisa->execute([':id' => $id]);
$visa = $stmtVisa->fetch();

// Emoji par catégorie
$emojisCategorie = [
    'plage' => '🏖', 'ville' => '🏙', 'culture' => '🏛',
    'nature' => '🌿', 'montagne' => '⛰', 'aventure' => '🧗',
];

$titrePage = $dest['nom'] . ' — ' . $dest['pays'];
require_once '../includes/header.php';
?>

<!-- Breadcrumb -->
<div style="background:white; border-bottom:1px solid var(--bordure);">
    <div class="container">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="<?= URL_BASE ?>/index.php">Accueil</a>
            <span class="breadcrumb-sep">›</span>
            <a href="liste.php">Destinations</a>
            <span class="breadcrumb-sep">›</span>
            <span><?= securiser($dest['nom']) ?></span>
        </nav>
    </div>
</div>

<!-- ================================================================
     EN-TÊTE DESTINATION
================================================================ -->
<div style="background:linear-gradient(135deg, var(--bleu) 0%, #023E8A 100%); color:white; padding:3rem 0 2.5rem;">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr auto; gap:2rem; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                    <span class="badge-categorie badge-<?= securiser($dest['categorie']) ?>">
                        <?= $emojisCategorie[$dest['categorie']] ?? '🌍' ?> <?= securiser($dest['categorie']) ?>
                    </span>
                    <?php if (!empty($universites)): ?>
                        <span style="background:rgba(255,255,255,0.15); color:white; padding:0.2rem 0.65rem; border-radius:50px; font-size:0.75rem; font-weight:700;">
                            🎓 Erasmus disponible
                        </span>
                    <?php endif; ?>
                </div>
                <h1 style="color:white; font-size:clamp(1.8rem,4vw,2.8rem); margin-bottom:0.3rem;">
                    <?= securiser($dest['nom']) ?>
                </h1>
                <p style="color:rgba(255,255,255,0.8); font-size:1.05rem; margin:0;">
                    📍 <?= securiser($dest['pays']) ?>
                </p>
            </div>

            <!-- Prix depuis -->
            <?php if (!empty($transports)): ?>
                <div style="background:rgba(255,255,255,0.1); border-radius:var(--rayon); padding:1.25rem 1.75rem; text-align:center; backdrop-filter:blur(4px);">
                    <div style="font-size:0.78rem; color:rgba(255,255,255,0.7); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.25rem;">
                        Transport depuis
                    </div>
                    <div style="font-family:'Poppins',sans-serif; font-size:1.8rem; font-weight:800; color:white;">
                        <?= formatPrix((float)$transports[0]['prix']) ?>
                    </div>
                    <div style="font-size:0.8rem; color:rgba(255,255,255,0.7);">
                        Paris — <?= securiser($transports[0]['compagnie']) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ================================================================
     MESSAGE PANIER
================================================================ -->
<?php if ($messageCartSucces): ?>
    <div style="background:#E6F7EC; color:#2D9E4E; padding:0.85rem 1.25rem; border-bottom:1px solid #b7e4c7; text-align:center; font-weight:700;">
        ✅ <?= $messageCartSucces ?>
        — <a href="<?= URL_BASE ?>/reservations/panier.php" style="color:#2D9E4E; text-decoration:underline;">Voir le panier</a>
    </div>
<?php endif; ?>

<div class="container section">
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:2.5rem; align-items:start;">

        <!-- ============================================================
             COLONNE PRINCIPALE
        ============================================================ -->
        <div>

            <!-- Description -->
            <div class="boite mb-3">
                <h2 style="margin-bottom:1rem; font-size:1.25rem;">À propos de <?= securiser($dest['nom']) ?></h2>
                <p style="color:var(--texte-doux); line-height:1.8; margin:0;">
                    <?= securiser($dest['description']) ?>
                </p>
            </div>

            <!-- --------------------------------------------------------
                 TRANSPORTS
            -------------------------------------------------------- -->
            <div class="boite mb-3">
                <h2 style="margin-bottom:1.25rem; font-size:1.25rem;">✈ Transports disponibles</h2>

                <?php if (empty($transports)): ?>
                    <p style="color:var(--texte-doux);">Aucun transport disponible pour cette destination.</p>
                <?php else: ?>
                    <?php foreach ($transports as $t):
                        $icones = ['avion' => '✈', 'train' => '🚂', 'bus' => '🚌', 'ferry' => '⛴', 'voiture' => '🚗'];
                        $icone  = $icones[$t['type']] ?? '🚀';
                    ?>
                        <div style="display:flex; align-items:center; gap:1rem; padding:1rem; border:1px solid var(--bordure); border-radius:var(--rayon-petit); margin-bottom:0.75rem;">
                            <div style="font-size:1.6rem; flex-shrink:0;"><?= $icone ?></div>
                            <div style="flex:1;">
                                <div style="font-weight:700; font-size:0.95rem;"><?= securiser($t['compagnie']) ?></div>
                                <div style="font-size:0.83rem; color:var(--texte-doux);">
                                    <?= securiser($t['lieu_depart']) ?> → <?= securiser($t['lieu_arrivee']) ?>
                                    · <?= ucfirst(securiser($t['type'])) ?>
                                </div>
                            </div>
                            <div style="font-family:'Poppins',sans-serif; font-weight:800; color:var(--bleu); font-size:1.1rem; white-space:nowrap;">
                                <?= formatPrix((float)$t['prix']) ?>
                            </div>
                            <?php if (estConnecte()): ?>
                                <form action="detail.php?id=<?= $id ?>" method="POST" style="flex-shrink:0;">
                                    <input type="hidden" name="type"     value="transport">
                                    <input type="hidden" name="ref_id"   value="<?= $t['id'] ?>">
                                    <input type="hidden" name="prix"     value="<?= $t['prix'] ?>">
                                    <input type="hidden" name="nom_item" value="<?= htmlspecialchars($t['compagnie'] . ' — ' . $t['lieu_depart'] . ' → ' . $t['lieu_arrivee'], ENT_QUOTES) ?>">
                                    <button type="submit" name="ajouter_panier" class="btn btn-primaire btn-petit">
                                        🛒 Ajouter
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- --------------------------------------------------------
                 HÉBERGEMENTS
            -------------------------------------------------------- -->
            <div class="boite mb-3">
                <h2 style="margin-bottom:1.25rem; font-size:1.25rem;">🏨 Hébergements</h2>

                <?php if (empty($hebergements)): ?>
                    <p style="color:var(--texte-doux);">Aucun hébergement disponible.</p>
                <?php else: ?>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem;">
                        <?php foreach ($hebergements as $h):
                            $icones = ['hotel' => '🏨', 'auberge' => '🏠', 'appartement' => '🏢', 'villa' => '🏡', 'camping' => '⛺', 'residence' => '🏫'];
                            $icone  = $icones[$h['type']] ?? '🏠';
                        ?>
                            <div style="border:1px solid var(--bordure); border-radius:var(--rayon-petit); padding:1rem; display:flex; flex-direction:column; gap:0.6rem;">
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <span style="font-size:1.3rem;"><?= $icone ?></span>
                                    <div>
                                        <div style="font-weight:700; font-size:0.92rem;"><?= securiser($h['nom']) ?></div>
                                        <div style="font-size:0.78rem; color:var(--texte-doux); text-transform:capitalize;"><?= securiser($h['type']) ?></div>
                                    </div>
                                </div>

                                <?php if ($h['etoiles']): ?>
                                    <div><?= afficherEtoiles((int)$h['etoiles']) ?></div>
                                <?php endif; ?>

                                <?php if ($h['description']): ?>
                                    <p style="font-size:0.82rem; color:var(--texte-doux); margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                        <?= securiser($h['description']) ?>
                                    </p>
                                <?php endif; ?>

                                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:auto;">
                                    <div>
                                        <span style="font-family:'Poppins',sans-serif; font-weight:800; color:var(--bleu);">
                                            <?= formatPrix((float)$h['prix_par_nuit']) ?>
                                        </span>
                                        <span style="font-size:0.78rem; color:var(--texte-doux);">/nuit</span>
                                    </div>
                                    <?php if (estConnecte()): ?>
                                        <form action="detail.php?id=<?= $id ?>" method="POST">
                                            <input type="hidden" name="type"     value="hebergement">
                                            <input type="hidden" name="ref_id"   value="<?= $h['id'] ?>">
                                            <input type="hidden" name="prix"     value="<?= $h['prix_par_nuit'] ?>">
                                            <input type="hidden" name="nom_item" value="<?= htmlspecialchars($h['nom'], ENT_QUOTES) ?>">
                                            <button type="submit" name="ajouter_panier" class="btn btn-primaire btn-petit">
                                                🛒
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- --------------------------------------------------------
                 ACTIVITÉS
            -------------------------------------------------------- -->
            <div class="boite">
                <h2 style="margin-bottom:1.25rem; font-size:1.25rem;">🎭 Activités à faire</h2>

                <?php if (empty($activites)): ?>
                    <p style="color:var(--texte-doux);">Aucune activité disponible.</p>
                <?php else: ?>
                    <?php foreach ($activites as $a): ?>
                        <div style="display:flex; align-items:center; gap:1rem; padding:0.85rem 0; border-bottom:1px solid var(--bordure);">
                            <div style="flex:1;">
                                <div style="font-weight:700; font-size:0.92rem;"><?= securiser($a['nom']) ?></div>
                                <div style="font-size:0.8rem; color:var(--texte-doux);">
                                    <?= securiser($a['categorie']) ?> · ⏱ <?= securiser((string)$a['duree_heures']) ?>h
                                </div>
                            </div>
                            <div style="font-family:'Poppins',sans-serif; font-weight:800; color:var(--bleu); white-space:nowrap;">
                                <?= $a['prix'] > 0 ? formatPrix((float)$a['prix']) : 'Gratuit' ?>
                            </div>
                            <?php if (estConnecte()): ?>
                                <form action="detail.php?id=<?= $id ?>" method="POST">
                                    <input type="hidden" name="type"     value="activite">
                                    <input type="hidden" name="ref_id"   value="<?= $a['id'] ?>">
                                    <input type="hidden" name="prix"     value="<?= $a['prix'] > 0 ? $a['prix'] : 1 ?>">
                                    <input type="hidden" name="nom_item" value="<?= htmlspecialchars($a['nom'], ENT_QUOTES) ?>">
                                    <button type="submit" name="ajouter_panier" class="btn btn-secondaire btn-petit">
                                        🛒
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- ============================================================
             COLONNE LATÉRALE
        ============================================================ -->
        <div style="position:sticky; top:80px; display:flex; flex-direction:column; gap:1.25rem;">

            <!-- Panier rapide -->
            <?php if (!estConnecte()): ?>
                <div class="boite" style="text-align:center; border:2px dashed var(--bleu);">
                    <div style="font-size:2rem; margin-bottom:0.5rem;">✈</div>
                    <h3 style="font-size:1rem; margin-bottom:0.5rem;">Envie de partir ?</h3>
                    <p style="font-size:0.85rem; color:var(--texte-doux); margin-bottom:1rem;">
                        Connectez-vous pour ajouter des éléments à votre panier.
                    </p>
                    <a href="<?= URL_BASE ?>/auth/connexion.php" class="btn btn-primaire btn-bloc">
                        Se connecter
                    </a>
                    <a href="<?= URL_BASE ?>/auth/inscription.php" class="btn btn-secondaire btn-bloc mt-1">
                        Créer un compte
                    </a>
                </div>
            <?php else: ?>
                <div class="boite" style="background:var(--bleu); color:white;">
                    <h3 style="color:white; font-size:1rem; margin-bottom:0.75rem;">🛒 Votre panier</h3>
                    <?php $panier = getPanier(); ?>
                    <?php if (empty($panier)): ?>
                        <p style="font-size:0.85rem; opacity:0.8; margin-bottom:1rem;">Aucun élément pour l'instant.</p>
                    <?php else: ?>
                        <p style="font-size:0.85rem; opacity:0.9; margin-bottom:0.5rem;">
                            <?= count($panier) ?> élément<?= count($panier) > 1 ? 's' : '' ?>
                            — <strong><?= formatPrix(totalPanier()) ?></strong>
                        </p>
                    <?php endif; ?>
                    <a href="<?= URL_BASE ?>/reservations/panier.php"
                       class="btn btn-grand"
                       style="background:white; color:var(--bleu); width:100%; justify-content:center; margin-top:0.25rem;">
                        Voir le panier →
                    </a>
                </div>
            <?php endif; ?>

            <!-- Infos visa -->
            <?php if ($visa): ?>
                <div class="boite">
                    <h3 style="font-size:1rem; margin-bottom:0.85rem;">🛂 Infos visa (UE)</h3>
                    <div style="display:flex; flex-direction:column; gap:0.5rem; font-size:0.88rem;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--texte-doux);">Visa requis</span>
                            <strong style="color:<?= $visa['visa_requis'] ? 'var(--erreur)' : 'var(--succes)' ?>">
                                <?= $visa['visa_requis'] ? '✕ Oui' : '✓ Non' ?>
                            </strong>
                        </div>
                        <?php if ($visa['type_visa']): ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--texte-doux);">Type</span>
                                <strong><?= securiser($visa['type_visa']) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ($visa['duree_max_jours']): ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--texte-doux);">Durée max</span>
                                <strong><?= (int)$visa['duree_max_jours'] ?> jours</strong>
                            </div>
                        <?php endif; ?>
                        <?php if ($visa['cout_eur']): ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--texte-doux);">Coût</span>
                                <strong><?= formatPrix((float)$visa['cout_eur']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Universités Erasmus -->
            <?php if (!empty($universites)): ?>
                <div class="boite">
                    <h3 style="font-size:1rem; margin-bottom:0.85rem;">🎓 Universités Erasmus</h3>
                    <?php foreach ($universites as $u): ?>
                        <div style="padding:0.6rem 0; border-bottom:1px solid var(--bordure); font-size:0.85rem;">
                            <div style="font-weight:700;"><?= securiser($u['nom']) ?></div>
                            <div style="color:var(--texte-doux);">
                                <?= securiser($u['langue']) ?>
                                <?= $u['code_erasmus'] ? ' · <code style="font-size:0.75rem;">' . securiser($u['code_erasmus']) . '</code>' : '' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="<?= URL_BASE ?>/etudiant/universites.php?destination_id=<?= $id ?>"
                       class="btn btn-secondaire btn-bloc mt-2" style="font-size:0.82rem;">
                        Voir les logements étudiants
                    </a>
                </div>
            <?php endif; ?>

            <!-- Localisation -->
            <?php if ($dest['latitude'] && $dest['longitude']): ?>
                <div class="boite" style="padding:0; overflow:hidden; border-radius:var(--rayon);">
                    <div style="background:var(--fond); padding:0.75rem 1rem; font-size:0.85rem; font-weight:700; color:var(--texte-doux);">
                        📍 Localisation
                    </div>
                    <div style="background:#e8f4fd; padding:1.5rem; text-align:center; font-size:0.83rem; color:var(--texte-doux);">
                        Lat <?= number_format((float)$dest['latitude'], 4) ?>
                        / Lon <?= number_format((float)$dest['longitude'], 4) ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Navigation entre destinations -->
<div style="background:var(--fond); border-top:1px solid var(--bordure); padding:1.5rem 0;">
    <div class="container flex-entre">
        <a href="liste.php" class="btn btn-secondaire">← Toutes les destinations</a>
        <?php if (estConnecte()): ?>
            <a href="<?= URL_BASE ?>/itineraires/creer.php" class="btn btn-primaire">
                Créer un itinéraire avec <?= securiser($dest['nom']) ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
