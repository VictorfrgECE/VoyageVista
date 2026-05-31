<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// ----------------------------------------------------------------
//  Récupération et validation des paramètres GET
// ----------------------------------------------------------------
$depart      = isset($_GET['depart'])      ? trim($_GET['depart'])      : '';
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$dateDepart  = isset($_GET['date_depart']) ? trim($_GET['date_depart']) : '';
$type        = isset($_GET['type'])        ? trim($_GET['type'])        : '';
$budgetMax   = isset($_GET['budget_max'])  && is_numeric($_GET['budget_max']) ? (float)$_GET['budget_max'] : null;
$tri         = isset($_GET['tri'])         ? trim($_GET['tri'])         : 'prix_asc';

$typesValides = ['avion', 'train', 'bus', 'ferry', 'voiture'];
if ($type && !in_array($type, $typesValides)) $type = '';

$trisValides = ['prix_asc', 'prix_desc', 'compagnie'];
if (!in_array($tri, $trisValides)) $tri = 'prix_asc';

// ----------------------------------------------------------------
//  Ajout au panier
// ----------------------------------------------------------------
$messageCart = '';
if (isset($_POST['ajouter_panier']) && estConnecte()) {
    $refId    = isset($_POST['ref_id'])   && ctype_digit($_POST['ref_id'])  ? (int)$_POST['ref_id']  : 0;
    $prix     = isset($_POST['prix'])     && is_numeric($_POST['prix'])     ? (float)$_POST['prix']  : 0;
    $nomItem  = trim($_POST['nom_item']  ?? '');
    $dateChoisie = isset($_POST['date_voyage']) ? trim($_POST['date_voyage']) : '';

    if ($refId > 0 && $prix > 0) {
        if (!empty($dateChoisie)) {
            $dateFormatee = date('d/m/Y', strtotime($dateChoisie));
            $nomItem .= " (Départ le " . $dateFormatee . ")";
        }

        ajouterAuPanier('transport', $refId, $prix, $nomItem);
        $messageCart = securiser($nomItem) . " ajouté au panier !";
    }
}

// ----------------------------------------------------------------
//  Construction de la requête
// ----------------------------------------------------------------
$conditions = [];
$params     = [];

if ($depart !== '') {
    $conditions[] = "t.lieu_depart LIKE :depart";
    $params[':depart'] = '%' . $depart . '%';
}
if ($destination !== '') {
    $conditions[] = "(d.nom LIKE :dest OR t.lieu_arrivee LIKE :dest2)";
    $params[':dest']  = '%' . $destination . '%';
    $params[':dest2'] = '%' . $destination . '%';
}
if ($type !== '') {
    $conditions[] = "t.type = :type";
    $params[':type'] = $type;
}
if ($budgetMax !== null) {
    $conditions[] = "t.prix <= :budget";
    $params[':budget'] = $budgetMax;
}

$orderBy = match($tri) {
    'prix_desc'  => "t.prix DESC",
    'compagnie'  => "t.compagnie ASC",
    default      => "t.prix ASC",
};

$sql = "SELECT t.*, d.nom AS dest_nom, d.pays AS dest_pays, d.categorie AS dest_cat, d.id AS dest_id
        FROM TRANSPORTS t
        LEFT JOIN DESTINATIONS d ON d.id = t.destination_id";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transports = $stmt->fetchAll();

// Icônes par type
$icones = ['avion' => '✈', 'train' => '🚂', 'bus' => '🚌', 'ferry' => '⛴', 'voiture' => '🚗'];

$titrePage = 'Résultats transport';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container flex-entre">
        <div>
            <h1>✈ Résultats de recherche</h1>
            <p>
                <?= count($transports) ?> transport<?= count($transports) > 1 ? 's' : '' ?> trouvé<?= count($transports) > 1 ? 's' : '' ?>
                <?php if ($depart || $destination): ?>
                    —
                    <?= $depart ? securiser($depart) : '...' ?>
                    →
                    <?= $destination ? securiser($destination) : 'toutes destinations' ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="recherche.php" class="btn btn-secondaire">← Modifier la recherche</a>
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

    <form action="resultats.php" method="GET" style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center; margin-bottom:1.5rem; padding:1rem 1.25rem; background:white; border-radius:var(--rayon); box-shadow:var(--ombre);">
        <input type="hidden" name="depart"      value="<?= securiser($depart) ?>">
        <input type="hidden" name="destination" value="<?= securiser($destination) ?>">
        <input type="hidden" name="budget_max"  value="<?= $budgetMax ?? '' ?>">
        <input type="hidden" name="date_depart" value="<?= securiser($dateDepart) ?>">

        <span style="font-size:0.85rem; font-weight:700; color:var(--texte-doux);">Type :</span>
        <?php
        $filtresType = ['' => 'Tous', 'avion' => '✈ Avion', 'train' => '🚂 Train', 'bus' => '🚌 Bus', 'ferry' => '⛴ Ferry'];
        foreach ($filtresType as $val => $lab):
        ?>
            <a href="resultats.php?depart=<?= urlencode($depart) ?>&destination=<?= urlencode($destination) ?>&date_depart=<?= urlencode($dateDepart) ?>&budget_max=<?= $budgetMax ?? '' ?>&type=<?= $val ?>&tri=<?= $tri ?>"
               style="padding:0.35rem 0.85rem; border-radius:50px; font-size:0.82rem; font-weight:700; text-decoration:none;
                      background:<?= $type === $val ? 'var(--bleu)' : 'var(--fond)' ?>;
                      color:<?= $type === $val ? 'white' : 'var(--texte-doux)' ?>;
                      border:2px solid <?= $type === $val ? 'var(--bleu)' : 'var(--bordure)' ?>;">
                <?= $lab ?>
            </a>
        <?php endforeach; ?>

        <span style="font-size:0.85rem; font-weight:700; color:var(--texte-doux); margin-left:0.5rem;">Tri :</span>
        <select name="tri" class="champ" style="padding:0.35rem 0.7rem; font-size:0.82rem; width:auto;" onchange="this.form.submit()">
            <option value="prix_asc"  <?= $tri === 'prix_asc'  ? 'selected' : '' ?>>Prix ↑</option>
            <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix ↓</option>
            <option value="compagnie" <?= $tri === 'compagnie' ? 'selected' : '' ?>>Compagnie</option>
        </select>
    </form>

    <?php if (empty($transports)): ?>

        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">✈</div>
            <h3>Aucun transport trouvé</h3>
            <p style="color:var(--texte-doux); margin-bottom:1.5rem;">
                Essayez d'élargir vos critères de recherche.
            </p>
            <a href="recherche.php" class="btn btn-primaire">Nouvelle recherche</a>
        </div>

    <?php else: ?>

        <div style="display:flex; flex-direction:column; gap:0.85rem;">
            <?php foreach ($transports as $t): ?>
                <div style="background:white; border-radius:var(--rayon); box-shadow:var(--ombre); padding:1.25rem 1.5rem; display:grid; grid-template-columns:auto 1fr auto auto; gap:1.25rem; align-items:center; transition:box-shadow 0.2s;"
                     onmouseover="this.style.boxShadow='var(--ombre-forte)'"
                     onmouseout="this.style.boxShadow='var(--ombre)'">

                    <div style="font-size:2rem; text-align:center;">
                        <?= $icones[$t['type']] ?? '🚀' ?>
                    </div>

                    <div>
                        <div style="font-family:'Poppins',sans-serif; font-weight:700; font-size:1rem; margin-bottom:0.2rem;">
                            <?= securiser($t['compagnie']) ?>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; color:var(--texte-doux); margin-bottom:0.3rem;">
                            <span style="font-weight:600; color:var(--texte);"><?= securiser($t['lieu_depart']) ?></span>
                            <span>→</span>
                            <span style="font-weight:600; color:var(--texte);"><?= securiser($t['lieu_arrivee']) ?></span>
                        </div>
                        <div style="display:flex; gap:0.75rem; font-size:0.8rem;">
                            <span style="background:var(--fond); padding:0.15rem 0.5rem; border-radius:4px; font-weight:600;">
                                <?= ucfirst(securiser($t['type'])) ?>
                            </span>
                            <?php if ($t['dest_nom']): ?>
                                <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $t['dest_id'] ?>"
                                   style="color:var(--bleu); font-weight:600;">
                                    🌍 <?= securiser($t['dest_nom']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="text-align:center;">
                        <div style="font-family:'Poppins',sans-serif; font-size:1.6rem; font-weight:800; color:var(--bleu);">
                            <?= formatPrix((float)$t['prix']) ?>
                        </div>
                        <div style="font-size:0.75rem; color:var(--texte-doux);">par personne</div>
                    </div>

                    <div>
                        <?php if (estConnecte()): ?>
                            <form action="resultats.php?<?= http_build_query($_GET) ?>" method="POST">
                                <input type="hidden" name="ref_id"   value="<?= $t['id'] ?>">
                                <input type="hidden" name="prix"     value="<?= $t['prix'] ?>">
                                <input type="hidden" name="nom_item" value="<?= htmlspecialchars($t['compagnie'] . ' — ' . $t['lieu_depart'] . ' → ' . $t['lieu_arrivee'], ENT_QUOTES) ?>">
                                <input type="hidden" name="date_voyage" value="<?= securiser($dateDepart) ?>">
                                <button type="submit" name="ajouter_panier" class="btn btn-primaire">
                                    🛒 Réserver
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= URL_BASE ?>/auth/connexion.php" class="btn btn-secondaire">
                                Connexion
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>