<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// ----------------------------------------------------------------
//  Récupération et validation des filtres GET
// ----------------------------------------------------------------
$categoriesValides = ['plage', 'montagne', 'ville', 'nature', 'aventure', 'culture'];

$recherche  = isset($_GET['recherche'])  ? trim($_GET['recherche'])  : '';
$categorie  = isset($_GET['categorie'])  ? trim($_GET['categorie'])  : '';
$pays       = isset($_GET['pays'])       ? trim($_GET['pays'])       : '';
$budgetMax  = isset($_GET['budget_max']) && is_numeric($_GET['budget_max']) ? (float)$_GET['budget_max'] : null;
$tri        = isset($_GET['tri'])        ? trim($_GET['tri'])        : 'nom';

// Sécuriser la catégorie
if ($categorie && !in_array($categorie, $categoriesValides)) {
    $categorie = '';
}

// Sécuriser le tri
$trisValides = ['nom', 'prix_asc', 'prix_desc', 'pays'];
if (!in_array($tri, $trisValides)) {
    $tri = 'nom';
}

// ----------------------------------------------------------------
//  Construction de la requête avec filtres dynamiques
// ----------------------------------------------------------------
$conditions = [];
$params     = [];

if ($recherche !== '') {
    $conditions[] = "(d.nom LIKE :rech_nom OR d.pays LIKE :rech_pays OR d.description LIKE :rech_desc)";
    $params[':rech_nom']  = '%' . $recherche . '%';
    $params[':rech_pays'] = '%' . $recherche . '%';
    $params[':rech_desc'] = '%' . $recherche . '%';
}

if ($categorie !== '') {
    $conditions[] = "d.categorie = :categorie";
    $params[':categorie'] = $categorie;
}

if ($pays !== '') {
    $conditions[] = "d.pays = :pays";
    $params[':pays'] = $pays;
}

$sql = "SELECT d.*, MIN(t.prix) AS prix_depuis
        FROM DESTINATIONS d
        LEFT JOIN TRANSPORTS t ON t.destination_id = d.id";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY d.id";

// Filtre budget sur le résultat groupé
if ($budgetMax !== null) {
    $sql .= " HAVING prix_depuis <= :budget_max OR prix_depuis IS NULL";
    $params[':budget_max'] = $budgetMax;
}

// Tri
switch ($tri) {
    case 'prix_asc':  $sql .= " ORDER BY prix_depuis ASC";  break;
    case 'prix_desc': $sql .= " ORDER BY prix_depuis DESC"; break;
    case 'pays':      $sql .= " ORDER BY d.pays, d.nom";    break;
    default:          $sql .= " ORDER BY d.nom";            break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// ----------------------------------------------------------------
//  Liste des pays pour le filtre (pour le <select>)
// ----------------------------------------------------------------
$stmtPays = $pdo->query("SELECT DISTINCT pays FROM DESTINATIONS ORDER BY pays");
$listePays = $stmtPays->fetchAll(PDO::FETCH_COLUMN);

// Emojis par catégorie
$emojisCategorie = [
    'plage'    => '🏖',
    'ville'    => '🏙',
    'culture'  => '🏛',
    'nature'   => '🌿',
    'montagne' => '⛰',
    'aventure' => '🧗',
];

$titrePage = 'Destinations';
require_once '../includes/header.php';
?>

<!-- En-tête de page -->
<div class="page-entete">
    <div class="container">
        <h1>🌍 Toutes les destinations</h1>
        <p>Découvrez nos <?= count($destinations) ?> destination<?= count($destinations) > 1 ? 's' : '' ?> pour étudiants voyageurs</p>
    </div>
</div>

<div class="container section">

    <!-- ============================================================
         FILTRES
    ============================================================ -->
    <form action="liste.php" method="GET" class="filtres">

        <div class="filtre-groupe" style="flex:2; min-width:200px;">
            <label for="f-recherche">Rechercher</label>
            <input
                type="text"
                id="f-recherche"
                name="recherche"
                class="champ"
                placeholder="🔍 Destination, pays…"
                value="<?= securiser($recherche) ?>"
            >
        </div>

        <div class="filtre-groupe">
            <label for="f-categorie">Catégorie</label>
            <select id="f-categorie" name="categorie" class="champ">
                <option value="">Toutes</option>
                <?php foreach ($categoriesValides as $cat): ?>
                    <option value="<?= $cat ?>" <?= $categorie === $cat ? 'selected' : '' ?>>
                        <?= $emojisCategorie[$cat] ?? '' ?> <?= ucfirst($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-pays">Pays</label>
            <select id="f-pays" name="pays" class="champ">
                <option value="">Tous les pays</option>
                <?php foreach ($listePays as $p): ?>
                    <option value="<?= securiser($p) ?>" <?= $pays === $p ? 'selected' : '' ?>>
                        <?= securiser($p) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-budget">Budget max transport</label>
            <select id="f-budget" name="budget_max" class="champ">
                <option value="">Tous budgets</option>
                <?php
                $budgets = [50 => '50 €', 100 => '100 €', 200 => '200 €', 500 => '500 €', 700 => '700 €'];
                foreach ($budgets as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $budgetMax == $val ? 'selected' : '' ?>>
                        Moins de <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-tri">Trier par</label>
            <select id="f-tri" name="tri" class="champ">
                <option value="nom"       <?= $tri === 'nom'       ? 'selected' : '' ?>>Nom A–Z</option>
                <option value="prix_asc"  <?= $tri === 'prix_asc'  ? 'selected' : '' ?>>Prix croissant</option>
                <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                <option value="pays"      <?= $tri === 'pays'      ? 'selected' : '' ?>>Pays</option>
            </select>
        </div>

        <div class="filtre-groupe" style="flex:0;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primaire">Filtrer</button>
        </div>

        <?php if ($recherche || $categorie || $pays || $budgetMax): ?>
            <div class="filtre-groupe" style="flex:0;">
                <label>&nbsp;</label>
                <a href="liste.php" class="btn btn-secondaire">✕ Réinitialiser</a>
            </div>
        <?php endif; ?>

    </form>

    <!-- ============================================================
         RÉSULTATS
    ============================================================ -->
    <?php if (empty($destinations)): ?>

        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
            <h3>Aucune destination trouvée</h3>
            <p style="color:var(--texte-doux); margin:0.5rem 0 1.5rem;">
                Essayez de modifier vos critères de recherche.
            </p>
            <a href="liste.php" class="btn btn-primaire">Voir toutes les destinations</a>
        </div>

    <?php else: ?>

        <div class="grille">
            <?php foreach ($destinations as $dest): ?>
                <a href="detail.php?id=<?= $dest['id'] ?>" style="text-decoration:none; color:inherit;">
                    <article class="carte">

                        <!-- Image ou placeholder -->
                        <?php if ($dest['image_url'] && file_exists('../' . $dest['image_url'])): ?>
                            <img
                                src="<?= URL_BASE . '/' . securiser($dest['image_url']) ?>"
                                alt="<?= securiser($dest['nom']) ?>"
                                class="carte-image"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="carte-image-placeholder">
                                <?= $emojisCategorie[$dest['categorie']] ?? '🌍' ?>
                            </div>
                        <?php endif; ?>

                        <div class="carte-corps">
                            <div class="flex-entre" style="margin-bottom:0.4rem;">
                                <span class="carte-pays"><?= securiser($dest['pays']) ?></span>
                                <span class="badge-categorie badge-<?= securiser($dest['categorie']) ?>">
                                    <?= securiser($dest['categorie']) ?>
                                </span>
                            </div>

                            <h3 class="carte-nom"><?= securiser($dest['nom']) ?></h3>

                            <p class="carte-description"><?= securiser($dest['description']) ?></p>

                            <div class="carte-pied">
                                <div class="carte-prix">
                                    <span class="depuis">Transport depuis</span>
                                    <span class="montant">
                                        <?= $dest['prix_depuis'] ? formatPrix((float)$dest['prix_depuis']) : 'Variable' ?>
                                    </span>
                                </div>
                                <span class="btn btn-primaire btn-petit">Découvrir →</span>
                            </div>
                        </div>

                    </article>
                </a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
