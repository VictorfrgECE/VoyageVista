<?php
session_start();
require_once '../config/connexion.php';
require_once '../config/constantes.php';
require_once '../includes/fonctions.php';

// Page accessible à tous (connecté ou non)

// ----------------------------------------------------------------
//  Filtres GET
// ----------------------------------------------------------------
$destId    = isset($_GET['destination_id'])  && ctype_digit($_GET['destination_id'])  ? (int)$_GET['destination_id']  : 0;
$univId    = isset($_GET['universite_id'])    && ctype_digit($_GET['universite_id'])    ? (int)$_GET['universite_id']    : 0;
$typeLog   = isset($_GET['type'])            ? trim($_GET['type'])            : '';
$budgetMax = isset($_GET['budget_max'])      && is_numeric($_GET['budget_max']) ? (float)$_GET['budget_max'] : null;

$typesValides = ['residence', 'colocation', 'studio', 'famille'];
if ($typeLog && !in_array($typeLog, $typesValides)) {
    $typeLog = '';
}

// ----------------------------------------------------------------
//  Requête avec filtres
// ----------------------------------------------------------------
$conditions = [];
$params     = [];

if ($destId > 0) {
    $conditions[] = "l.destination_id = :dest_id";
    $params[':dest_id'] = $destId;
}
if ($univId > 0) {
    $conditions[] = "l.universite_id = :univ_id";
    $params[':univ_id'] = $univId;
}
if ($typeLog !== '') {
    $conditions[] = "l.type = :type";
    $params[':type'] = $typeLog;
}
if ($budgetMax !== null) {
    $conditions[] = "l.prix_par_mois <= :budget";
    $params[':budget'] = $budgetMax;
}

$sql = "SELECT l.*,
               d.nom  AS dest_nom,
               u.nom  AS univ_nom,
               u.ville AS univ_ville
        FROM LOGEMENTS_ETUDIANTS l
        JOIN DESTINATIONS d ON d.id = l.destination_id
        LEFT JOIN UNIVERSITES u ON u.id = l.universite_id";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY l.prix_par_mois ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logements = $stmt->fetchAll();

// Listes pour les filtres
$listeDestinations = $pdo->query(
    "SELECT DISTINCT d.id, d.nom FROM DESTINATIONS d
     JOIN LOGEMENTS_ETUDIANTS l ON l.destination_id = d.id
     ORDER BY d.nom"
)->fetchAll();

$listeUniversites = $pdo->query(
    "SELECT DISTINCT u.id, u.nom, u.ville FROM UNIVERSITES u
     JOIN LOGEMENTS_ETUDIANTS l ON l.universite_id = u.id
     ORDER BY u.nom"
)->fetchAll();

// Icônes par type
$icones = ['residence' => '🏫', 'colocation' => '🏠', 'studio' => '🏢', 'famille' => '🏡'];
$labels = ['residence' => 'Résidence', 'colocation' => 'Colocation', 'studio' => 'Studio', 'famille' => 'Famille d\'accueil'];

$titrePage = 'Logements étudiants';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🏠 Logements étudiants</h1>
        <p>Trouvez un logement proche de votre campus pour votre mobilité Erasmus</p>
    </div>
</div>

<div class="container section">

    <!-- ============================================================
         FILTRES
    ============================================================ -->
    <form action="logements.php" method="GET" class="filtres">

        <div class="filtre-groupe">
            <label for="f-dest">Destination</label>
            <select id="f-dest" name="destination_id" class="champ">
                <option value="">Toutes les destinations</option>
                <?php foreach ($listeDestinations as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $destId === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= securiser($d['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-univ">Université</label>
            <select id="f-univ" name="universite_id" class="champ">
                <option value="">Toutes les universités</option>
                <?php foreach ($listeUniversites as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $univId === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= securiser($u['nom']) ?> — <?= securiser($u['ville']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-type">Type de logement</label>
            <select id="f-type" name="type" class="champ">
                <option value="">Tous les types</option>
                <?php foreach ($typesValides as $t): ?>
                    <option value="<?= $t ?>" <?= $typeLog === $t ? 'selected' : '' ?>>
                        <?= $icones[$t] ?> <?= $labels[$t] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe">
            <label for="f-budget">Budget max / mois</label>
            <select id="f-budget" name="budget_max" class="champ">
                <option value="">Tous budgets</option>
                <?php foreach ([300, 400, 500, 600, 700] as $b): ?>
                    <option value="<?= $b ?>" <?= $budgetMax == $b ? 'selected' : '' ?>>
                        Moins de <?= $b ?> €/mois
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtre-groupe" style="flex:0;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primaire">Filtrer</button>
        </div>

        <?php if ($destId || $univId || $typeLog || $budgetMax): ?>
            <div class="filtre-groupe" style="flex:0;">
                <label>&nbsp;</label>
                <a href="logements.php" class="btn btn-secondaire">✕ Réinitialiser</a>
            </div>
        <?php endif; ?>

    </form>

    <!-- Compteur -->
    <p style="color:var(--texte-doux); font-size:0.88rem; margin-bottom:1.5rem;">
        <?= count($logements) ?> logement<?= count($logements) > 1 ? 's' : '' ?> disponible<?= count($logements) > 1 ? 's' : '' ?>
    </p>

    <!-- ============================================================
         RÉSULTATS
    ============================================================ -->
    <?php if (empty($logements)): ?>

        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">🏠</div>
            <h3>Aucun logement trouvé</h3>
            <p style="color:var(--texte-doux); margin:0.5rem 0 1.5rem;">
                Modifiez vos critères ou consultez toutes les destinations disponibles.
            </p>
            <a href="logements.php" class="btn btn-primaire">Voir tous les logements</a>
        </div>

    <?php else: ?>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
            <?php foreach ($logements as $l): ?>
                <div class="carte">
                    <div class="carte-corps">

                        <!-- En-tête -->
                        <div style="display:flex; align-items:flex-start; gap:0.75rem; margin-bottom:0.85rem;">
                            <div style="font-size:2rem; flex-shrink:0;"><?= $icones[$l['type']] ?></div>
                            <div>
                                <h3 style="font-size:0.95rem; line-height:1.3; margin-bottom:0.2rem;">
                                    <?= securiser($l['nom']) ?>
                                </h3>
                                <span style="font-size:0.78rem; background:#EEF2FF; color:#4338CA; padding:0.15rem 0.5rem; border-radius:4px; font-weight:700;">
                                    <?= $labels[$l['type']] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Infos -->
                        <div style="display:flex; flex-direction:column; gap:0.45rem; margin-bottom:1rem; font-size:0.85rem; color:var(--texte-doux);">
                            <div>📍 <?= securiser($l['dest_nom']) ?></div>
                            <?php if ($l['univ_nom']): ?>
                                <div>🎓 <?= securiser($l['univ_nom']) ?></div>
                            <?php endif; ?>
                            <div>
                                🚶 <strong style="color:var(--texte);"><?= number_format((float)$l['distance_campus_km'], 1) ?> km</strong>
                                du campus
                            </div>
                        </div>

                        <!-- Prix + action -->
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:auto;">
                            <div>
                                <span style="font-family:'Poppins',sans-serif; font-weight:800; color:var(--bleu); font-size:1.2rem;">
                                    <?= formatPrix((float)$l['prix_par_mois']) ?>
                                </span>
                                <span style="font-size:0.78rem; color:var(--texte-doux);">/mois</span>
                            </div>
                            <a href="budget.php?destination=<?= urlencode($l['dest_nom']) ?>&logement=<?= $l['prix_par_mois'] ?>"
                               class="btn btn-primaire btn-petit">
                                💶 Estimer
                            </a>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<!-- Lien vers universités -->
<div style="background:var(--fond); border-top:1px solid var(--bordure); padding:2rem 0;">
    <div class="container centrer">
        <p style="color:var(--texte-doux); margin-bottom:1rem;">Vous cherchez encore votre université ?</p>
        <a href="universites.php" class="btn btn-secondaire">🎓 Voir les universités partenaires</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
