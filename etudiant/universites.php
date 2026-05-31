<?php
session_start();
require_once '../config/connexion.php';
require_once '../config/constantes.php';
require_once '../includes/fonctions.php';

// Page accessible à tous (connecté ou non)

// ----------------------------------------------------------------
//  Filtres GET
// ----------------------------------------------------------------
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';
$pays      = isset($_GET['pays'])      ? trim($_GET['pays'])      : '';
$langue    = isset($_GET['langue'])    ? trim($_GET['langue'])    : '';
$destId    = isset($_GET['destination_id']) && ctype_digit($_GET['destination_id'])
             ? (int)$_GET['destination_id'] : 0;

// ----------------------------------------------------------------
//  Requête avec filtres dynamiques
// ----------------------------------------------------------------
$conditions = [];
$params     = [];

if ($recherche !== '') {
    $conditions[] = "(u.nom LIKE :rech OR u.ville LIKE :rech2 OR u.pays LIKE :rech3)";
    $params[':rech']  = '%' . $recherche . '%';
    $params[':rech2'] = '%' . $recherche . '%';
    $params[':rech3'] = '%' . $recherche . '%';
}
if ($pays !== '') {
    $conditions[] = "u.pays = :pays";
    $params[':pays'] = $pays;
}
if ($langue !== '') {
    $conditions[] = "u.langue LIKE :langue";
    $params[':langue'] = '%' . $langue . '%';
}
if ($destId > 0) {
    $conditions[] = "u.destination_id = :dest_id";
    $params[':dest_id'] = $destId;
}

$sql = "SELECT u.*, d.nom AS dest_nom, d.pays AS dest_pays
        FROM UNIVERSITES u
        LEFT JOIN DESTINATIONS d ON d.id = u.destination_id";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY u.pays, u.nom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$universites = $stmt->fetchAll();

// Listes pour les filtres
$listePays   = $pdo->query("SELECT DISTINCT pays FROM UNIVERSITES ORDER BY pays")->fetchAll(PDO::FETCH_COLUMN);
$listeDestinations = $pdo->query("SELECT id, nom FROM DESTINATIONS ORDER BY nom")->fetchAll();

// Langues principales (extraites des données)
$languesBrutes = $pdo->query("SELECT DISTINCT langue FROM UNIVERSITES ORDER BY langue")->fetchAll(PDO::FETCH_COLUMN);
// Décomposer les langues composites "Espagnol/Catalan" → ["Espagnol", "Catalan"]
$langues = [];
foreach ($languesBrutes as $l) {
    foreach (explode('/', $l) as $lang) {
        $lang = trim($lang);
        if ($lang && !in_array($lang, $langues)) {
            $langues[] = $lang;
        }
    }
}
sort($langues);

$titrePage = 'Universités Erasmus';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🎓 Universités partenaires Erasmus</h1>
        <p>Trouvez votre université d'accueil parmi nos partenaires internationaux</p>
    </div>
</div>

<div class="container section">

    <!-- ============================================================
         FILTRES
    ============================================================ -->
    <form action="universites.php" method="GET" class="filtres">

        <div class="filtre-groupe" style="flex:2; min-width:200px;">
            <label for="f-recherche">Rechercher</label>
            <input type="text" id="f-recherche" name="recherche" class="champ"
                   placeholder="🔍 Université, ville…"
                   value="<?= securiser($recherche) ?>">
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
            <label for="f-langue">Langue d'enseignement</label>
            <select id="f-langue" name="langue" class="champ">
                <option value="">Toutes les langues</option>
                <?php foreach ($langues as $l): ?>
                    <option value="<?= securiser($l) ?>" <?= $langue === $l ? 'selected' : '' ?>>
                        <?= securiser($l) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

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

        <div class="filtre-groupe" style="flex:0;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primaire">Filtrer</button>
        </div>

        <?php if ($recherche || $pays || $langue || $destId): ?>
            <div class="filtre-groupe" style="flex:0;">
                <label>&nbsp;</label>
                <a href="universites.php" class="btn btn-secondaire">✕ Réinitialiser</a>
            </div>
        <?php endif; ?>

    </form>

    <!-- Compteur résultats -->
    <p style="color:var(--texte-doux); font-size:0.88rem; margin-bottom:1.5rem;">
        <?= count($universites) ?> université<?= count($universites) > 1 ? 's' : '' ?> trouvée<?= count($universites) > 1 ? 's' : '' ?>
    </p>

    <!-- ============================================================
         RÉSULTATS
    ============================================================ -->
    <?php if (empty($universites)): ?>

        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">🎓</div>
            <h3>Aucune université trouvée</h3>
            <p style="color:var(--texte-doux); margin:0.5rem 0 1.5rem;">
                Modifiez vos critères de recherche.
            </p>
            <a href="universites.php" class="btn btn-primaire">Voir toutes les universités</a>
        </div>

    <?php else: ?>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
            <?php foreach ($universites as $u): ?>
                <div class="carte" style="border-top:4px solid var(--bleu);">
                    <div class="carte-corps">

                        <!-- En-tête -->
                        <div style="display:flex; align-items:flex-start; gap:0.75rem; margin-bottom:0.85rem;">
                            <div style="font-size:2rem; flex-shrink:0;">🏫</div>
                            <div>
                                <h3 style="font-size:0.95rem; line-height:1.3; margin-bottom:0.2rem;">
                                    <?= securiser($u['nom']) ?>
                                </h3>
                                <div style="font-size:0.82rem; color:var(--texte-doux);">
                                    📍 <?= securiser($u['ville']) ?>, <?= securiser($u['pays']) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Infos -->
                        <div style="display:flex; flex-direction:column; gap:0.4rem; margin-bottom:1rem; font-size:0.85rem;">
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <span>🗣</span>
                                <span><?= securiser($u['langue']) ?></span>
                            </div>
                            <?php if ($u['code_erasmus']): ?>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <span>🔖</span>
                                    <code style="background:#EEF2FF; color:#4338CA; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.8rem;">
                                        <?= securiser($u['code_erasmus']) ?>
                                    </code>
                                </div>
                            <?php endif; ?>
                            <?php if ($u['dest_nom']): ?>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <span>🌍</span>
                                    <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $u['destination_id'] ?>"
                                       style="color:var(--bleu); font-weight:700;">
                                        <?= securiser($u['dest_nom']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:auto;">
                            <?php if ($u['destination_id']): ?>
                                <a href="logements.php?universite_id=<?= $u['id'] ?>"
                                   class="btn btn-primaire btn-petit">
                                    🏠 Logements
                                </a>
                                <a href="<?= URL_BASE ?>/destinations/detail.php?id=<?= $u['destination_id'] ?>"
                                   class="btn btn-secondaire btn-petit">
                                    🌍 Destination
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<!-- Bloc d'aide -->
<div style="background:linear-gradient(135deg,#EBF5FD,#F0FDF4); border-top:1px solid var(--bordure); padding:2.5rem 0; margin-top:2rem;">
    <div class="container" style="max-width:700px; text-align:center;">
        <h2 style="margin-bottom:0.75rem;">Vous ne trouvez pas votre université ?</h2>
        <p style="color:var(--texte-doux); margin-bottom:1.5rem;">
            Consultez la base officielle Erasmus+ sur le site de la Commission Européenne
            ou renseignez-vous auprès du service des Relations Internationales de votre établissement.
        </p>
        <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
            <a href="budget.php" class="btn btn-primaire">💶 Estimer mon budget</a>
            <a href="logements.php" class="btn btn-secondaire">🏠 Voir les logements</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
