<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// ----------------------------------------------------------------
//  Filtres GET
// ----------------------------------------------------------------
$recherche  = isset($_GET['recherche'])  ? trim($_GET['recherche'])  : '';
$destId     = isset($_GET['dest_id'])    && ctype_digit($_GET['dest_id'])    ? (int)$_GET['dest_id']    : 0;
$type       = isset($_GET['type'])       ? trim($_GET['type'])       : '';
$budgetMax  = isset($_GET['budget_max']) && is_numeric($_GET['budget_max'])  ? (float)$_GET['budget_max'] : null;
$etoilesMin = isset($_GET['etoiles'])    && ctype_digit($_GET['etoiles'])    ? (int)$_GET['etoiles']    : 0;
$tri        = isset($_GET['tri'])        ? trim($_GET['tri'])        : 'prix_asc';

$typesValides = ['hotel', 'auberge', 'appartement', 'villa', 'camping', 'residence'];
if ($type && !in_array($type, $typesValides)) $type = '';
if (!in_array($tri, ['prix_asc', 'prix_desc', 'nom', 'etoiles'])) $tri = 'prix_asc';

// ----------------------------------------------------------------
//  Requête
// ----------------------------------------------------------------
$conditions = [];
$params     = [];

if ($recherche !== '') {
    $conditions[] = "(h.nom LIKE :rech OR d.nom LIKE :rech2)";
    $params[':rech']  = '%' . $recherche . '%';
    $params[':rech2'] = '%' . $recherche . '%';
}
if ($destId > 0) {
    $conditions[] = "h.destination_id = :dest_id";
    $params[':dest_id'] = $destId;
}
if ($type !== '') {
    $conditions[] = "h.type = :type";
    $params[':type'] = $type;
}
if ($budgetMax !== null) {
    $conditions[] = "h.prix_par_nuit <= :budget";
    $params[':budget'] = $budgetMax;
}
if ($etoilesMin > 0) {
    $conditions[] = "h.etoiles >= :etoiles";
    $params[':etoiles'] = $etoilesMin;
}

$orderBy = match($tri) {
    'prix_desc' => "h.prix_par_nuit DESC",
    'nom'       => "h.nom ASC",
    'etoiles'   => "h.etoiles DESC, h.prix_par_nuit ASC",
    default     => "h.prix_par_nuit ASC",
};

$sql = "SELECT h.*, d.nom AS dest_nom, d.pays AS dest_pays, d.id AS dest_id
        FROM HEBERGEMENTS h
        JOIN DESTINATIONS d ON d.id = h.destination_id";

if ($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);
$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hebergements = $stmt->fetchAll();

// Listes pour les filtres
$listeDestinations = $pdo->query("SELECT id, nom FROM DESTINATIONS ORDER BY nom")->fetchAll();

$icones = ['hotel' => '🏨', 'auberge' => '🏠', 'appartement' => '🏢', 'villa' => '🏡', 'camping' => '⛺', 'residence' => '🏫'];
$labels = ['hotel' => 'Hôtel', 'auberge' => 'Auberge', 'appartement' => 'Appartement', 'villa' => 'Villa', 'camping' => 'Camping', 'residence' => 'Résidence'];

$titrePage = 'Hébergements';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🏨 Hébergements</h1>
        <p><?= count($hebergements) ?> hébergement<?= count($hebergements) > 1 ? 's' : '' ?> disponible<?= count($hebergements) > 1 ? 's' : '' ?></p>
    </div>
</div>

<div class="container section">

    <!-- Filtres -->
    <form action="liste.php" method="GET" class="filtres">

        <div class="filtre-groupe" style="flex:2; min-width:200px;">
            <label for="f-rech">Rechercher</label>
            <input type="text" id="f-rech" name="recherche" class="champ"
                   placeholder="🔍 Nom, destination…"
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
            <label for="f-type">Type</label>
            <select id="f-type" name="type" class="champ">
                <option value="">Tous les types</option>
                <?php foreach ($typesValides as $t): ?>
                    <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>>
                        <?= $icones[$t] ?> <?= $labels[$t] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-budget">Budget max / nuit</label>
            <select id="f-budget" name="budget_max" class="champ">
                <option value="">Tous budgets</option>
                <?php foreach ([30 => '30 €', 50 => '50 €', 80 => '80 €', 120 => '120 €'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $budgetMax == $val ? 'selected' : '' ?>>
                        Moins de <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-etoiles">Étoiles min</label>
            <select id="f-etoiles" name="etoiles" class="champ">
                <option value="0">Indifférent</option>
                <option value="2" <?= $etoilesMin === 2 ? 'selected' : '' ?>>★★ 2+</option>
                <option value="3" <?= $etoilesMin === 3 ? 'selected' : '' ?>>★★★ 3+</option>
                <option value="4" <?= $etoilesMin === 4 ? 'selected' : '' ?>>★★★★ 4+</option>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-tri">Tri</label>
            <select id="f-tri" name="tri" class="champ">
                <option value="prix_asc"  <?= $tri === 'prix_asc'  ? 'selected' : '' ?>>Prix ↑</option>
                <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix ↓</option>
                <option value="etoiles"   <?= $tri === 'etoiles'   ? 'selected' : '' ?>>Étoiles</option>
                <option value="nom"       <?= $tri === 'nom'       ? 'selected' : '' ?>>Nom A–Z</option>
            </select>
        </div>

        <div class="filtre-groupe" style="flex:0;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primaire">Filtrer</button>
        </div>

        <?php if ($recherche || $destId || $type || $budgetMax || $etoilesMin || $tri !== 'prix_asc'): ?>
            <div class="filtre-groupe" style="flex:0;">
                <label>&nbsp;</label>
                <a href="liste.php" class="btn btn-secondaire">✕</a>
            </div>
        <?php endif; ?>

    </form>

    <!-- Résultats -->
    <?php if (empty($hebergements)): ?>

        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">🏨</div>
            <h3>Aucun hébergement trouvé</h3>
            <p style="color:var(--texte-doux); margin-bottom:1.5rem;">Essayez d'élargir vos critères.</p>
            <a href="liste.php" class="btn btn-primaire">Voir tous les hébergements</a>
        </div>

    <?php else: ?>

        <div class="grille">
            <?php foreach ($hebergements as $h): ?>
                <a href="detail.php?id=<?= $h['id'] ?>" style="text-decoration:none; color:inherit;">
                    <article class="carte">

                        <?php if (imageExiste($h['image_url'] ?? '')): ?>
                            <img
                                src="<?= srcImage($h['image_url']) ?>"
                                alt="<?= securiser($h['nom']) ?>"
                                class="carte-image"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="carte-image-placeholder">
                                <?= $icones[$h['type']] ?? '🏠' ?>
                            </div>
                        <?php endif; ?>

                        <div class="carte-corps">
                            <div class="flex-entre" style="margin-bottom:0.3rem;">
                                <span style="font-size:0.78rem; font-weight:700; color:var(--bleu); text-transform:uppercase; letter-spacing:0.05em;">
                                    <?= securiser($h['dest_nom']) ?>
                                </span>
                                <span style="font-size:0.75rem; background:#EEF2FF; color:#4338CA; padding:0.15rem 0.5rem; border-radius:4px; font-weight:700;">
                                    <?= $labels[$h['type']] ?? $h['type'] ?>
                                </span>
                            </div>

                            <h3 class="carte-nom"><?= securiser($h['nom']) ?></h3>

                            <?php if ($h['etoiles']): ?>
                                <div style="margin-bottom:0.4rem;"><?= afficherEtoiles((int)$h['etoiles']) ?></div>
                            <?php endif; ?>

                            <?php if ($h['description']): ?>
                                <p class="carte-description"><?= securiser($h['description']) ?></p>
                            <?php endif; ?>

                            <div class="carte-pied">
                                <div class="carte-prix">
                                    <span class="depuis">À partir de</span>
                                    <span class="montant"><?= formatPrix((float)$h['prix_par_nuit']) ?></span>
                                    <span style="font-size:0.75rem; color:var(--texte-doux);">/nuit</span>
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
