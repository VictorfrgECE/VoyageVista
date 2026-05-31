<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// ----------------------------------------------------------------
//  Filtres GET
// ----------------------------------------------------------------
$recherche  = isset($_GET['recherche'])  ? trim($_GET['recherche'])  : '';
$destId     = isset($_GET['dest_id'])    && ctype_digit($_GET['dest_id'])   ? (int)$_GET['dest_id']   : 0;
$categorie  = isset($_GET['categorie'])  ? trim($_GET['categorie'])  : '';
$budgetMax  = isset($_GET['budget_max']) && is_numeric($_GET['budget_max']) ? (float)$_GET['budget_max'] : null;
$dureeMax   = isset($_GET['duree_max'])  && is_numeric($_GET['duree_max'])  ? (float)$_GET['duree_max']  : null;
$tri        = isset($_GET['tri'])        ? trim($_GET['tri'])        : 'prix_asc';

if (!in_array($tri, ['prix_asc', 'prix_desc', 'nom', 'duree'])) $tri = 'prix_asc';

// ----------------------------------------------------------------
//  Requête
// ----------------------------------------------------------------
$conditions = [];
$params     = [];

if ($recherche !== '') {
    $conditions[] = "(a.nom LIKE :rech OR a.categorie LIKE :rech2 OR d.nom LIKE :rech3)";
    $params[':rech']  = '%' . $recherche . '%';
    $params[':rech2'] = '%' . $recherche . '%';
    $params[':rech3'] = '%' . $recherche . '%';
}
if ($destId > 0) {
    $conditions[] = "a.destination_id = :dest_id";
    $params[':dest_id'] = $destId;
}
if ($categorie !== '') {
    $conditions[] = "a.categorie = :cat";
    $params[':cat'] = $categorie;
}
if ($budgetMax !== null) {
    $conditions[] = "a.prix <= :budget";
    $params[':budget'] = $budgetMax;
}
if ($dureeMax !== null) {
    $conditions[] = "a.duree_heures <= :duree";
    $params[':duree'] = $dureeMax;
}

$orderBy = match($tri) {
    'prix_desc' => "a.prix DESC",
    'nom'       => "a.nom ASC",
    'duree'     => "a.duree_heures ASC",
    default     => "a.prix ASC",
};

$sql = "SELECT a.*, d.nom AS dest_nom, d.pays AS dest_pays, d.id AS dest_id
        FROM ACTIVITES a
        JOIN DESTINATIONS d ON d.id = a.destination_id";

if ($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);
$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activites = $stmt->fetchAll();

// Listes filtres
$listeDestinations = $pdo->query("SELECT id, nom FROM DESTINATIONS ORDER BY nom")->fetchAll();
$listeCategories   = $pdo->query("SELECT DISTINCT categorie FROM ACTIVITES ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

// Emojis par catégorie
$emojisCategorie = [
    'culture'      => '🏛', 'sport'      => '🏄', 'gastronomie' => '🍽',
    'tourisme'     => '📸', 'bien-être'  => '🧘', 'aventure'    => '🧗',
    'nature'       => '🌿', 'nightlife'  => '🎵',
];

$titrePage = 'Activités';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🎭 Activités & expériences</h1>
        <p><?= count($activites) ?> activité<?= count($activites) > 1 ? 's' : '' ?> disponible<?= count($activites) > 1 ? 's' : '' ?></p>
    </div>
</div>

<div class="container section">

    <!-- Filtres -->
    <form action="liste.php" method="GET" class="filtres">

        <div class="filtre-groupe" style="flex:2; min-width:200px;">
            <label for="f-rech">Rechercher</label>
            <input type="text" id="f-rech" name="recherche" class="champ"
                   placeholder="🔍 Activité, catégorie…"
                   value="<?= securiser($recherche) ?>">
        </div>

        <div class="filtre-groupe">
            <label for="f-dest">Destination</label>
            <select id="f-dest" name="dest_id" class="champ">
                <option value="">Toutes</option>
                <?php foreach ($listeDestinations as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $destId === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= securiser($d['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-cat">Catégorie</label>
            <select id="f-cat" name="categorie" class="champ">
                <option value="">Toutes</option>
                <?php foreach ($listeCategories as $cat): ?>
                    <option value="<?= securiser($cat) ?>" <?= $categorie === $cat ? 'selected' : '' ?>>
                        <?= ($emojisCategorie[$cat] ?? '🎭') . ' ' . ucfirst(securiser($cat)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-budget">Prix max</label>
            <select id="f-budget" name="budget_max" class="champ">
                <option value="">Tous</option>
                <option value="0"  <?= $budgetMax === 0.0 ? 'selected' : '' ?>>Gratuites</option>
                <option value="15" <?= $budgetMax == 15   ? 'selected' : '' ?>>Moins de 15 €</option>
                <option value="30" <?= $budgetMax == 30   ? 'selected' : '' ?>>Moins de 30 €</option>
                <option value="60" <?= $budgetMax == 60   ? 'selected' : '' ?>>Moins de 60 €</option>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-duree">Durée max</label>
            <select id="f-duree" name="duree_max" class="champ">
                <option value="">Indifférent</option>
                <option value="1"  <?= $dureeMax == 1  ? 'selected' : '' ?>>&lt; 1 heure</option>
                <option value="2"  <?= $dureeMax == 2  ? 'selected' : '' ?>>&lt; 2 heures</option>
                <option value="3"  <?= $dureeMax == 3  ? 'selected' : '' ?>>&lt; 3 heures</option>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-tri">Tri</label>
            <select id="f-tri" name="tri" class="champ">
                <option value="prix_asc"  <?= $tri === 'prix_asc'  ? 'selected' : '' ?>>Prix ↑</option>
                <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix ↓</option>
                <option value="duree"     <?= $tri === 'duree'     ? 'selected' : '' ?>>Durée ↑</option>
                <option value="nom"       <?= $tri === 'nom'       ? 'selected' : '' ?>>Nom A–Z</option>
            </select>
        </div>

        <div class="filtre-groupe" style="flex:0;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primaire">Filtrer</button>
        </div>

        <?php if ($recherche || $destId || $categorie || $budgetMax !== null || $dureeMax !== null || $tri !== 'prix_asc'): ?>
            <div class="filtre-groupe" style="flex:0;">
                <label>&nbsp;</label>
                <a href="liste.php" class="btn btn-secondaire">✕</a>
            </div>
        <?php endif; ?>

    </form>

    <!-- Résultats -->
    <?php if (empty($activites)): ?>

        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">🎭</div>
            <h3>Aucune activité trouvée</h3>
            <p style="color:var(--texte-doux); margin-bottom:1.5rem;">Élargissez vos critères.</p>
            <a href="liste.php" class="btn btn-primaire">Voir toutes les activités</a>
        </div>

    <?php else: ?>

        <div class="grille">
            <?php foreach ($activites as $a): ?>
                <a href="detail.php?id=<?= $a['id'] ?>" style="text-decoration:none; color:inherit;">
                    <article class="carte">

                        <?php if (imageExiste($a['image_url'] ?? '')): ?>
                            <img
                                src="<?= srcImage($a['image_url']) ?>"
                                alt="<?= securiser($a['nom']) ?>"
                                class="carte-image"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="carte-image-placeholder">
                                <?= $emojisCategorie[$a['categorie']] ?? '🎭' ?>
                            </div>
                        <?php endif; ?>

                        <div class="carte-corps">
                            <div class="flex-entre" style="margin-bottom:0.3rem;">
                                <span style="font-size:0.78rem; font-weight:700; color:var(--bleu); text-transform:uppercase; letter-spacing:0.05em;">
                                    <?= securiser($a['dest_nom']) ?>
                                </span>
                                <span style="font-size:0.75rem; background:#FDF4FF; color:#9333EA; padding:0.15rem 0.5rem; border-radius:4px; font-weight:700;">
                                    <?= securiser($a['categorie']) ?>
                                </span>
                            </div>

                            <h3 class="carte-nom"><?= securiser($a['nom']) ?></h3>

                            <div style="display:flex; gap:0.75rem; font-size:0.82rem; color:var(--texte-doux); margin-bottom:0.75rem;">
                                <span>⏱ <?= securiser((string)$a['duree_heures']) ?>h</span>
                                <span>📍 <?= securiser($a['dest_pays']) ?></span>
                            </div>

                            <?php if ($a['description']): ?>
                                <p class="carte-description"><?= securiser($a['description']) ?></p>
                            <?php endif; ?>

                            <div class="carte-pied">
                                <div class="carte-prix">
                                    <span class="depuis">Prix</span>
                                    <span class="montant" style="<?= $a['prix'] == 0 ? 'color:var(--succes)' : '' ?>">
                                        <?= $a['prix'] > 0 ? formatPrix((float)$a['prix']) : 'Gratuit' ?>
                                    </span>
                                </div>
                                <span class="btn btn-primaire btn-petit">Voir →</span>
                            </div>
                        </div>

                    </article>
                </a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
