<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

$uid    = $_SESSION['utilisateur_id'];
$itinId = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;

if ($itinId <= 0) {
    rediriger('mon_itineraire.php');
}

// Vérifier que l'itinéraire appartient à l'utilisateur connecté
$stmt = $pdo->prepare("SELECT * FROM ITINERAIRES WHERE id = :id AND utilisateur_id = :uid");
$stmt->execute([':id' => $itinId, ':uid' => $uid]);
$itin = $stmt->fetch();

if (!$itin) {
    rediriger('mon_itineraire.php');
}

$succes = '';
$erreur = '';

// ----------------------------------------------------------------
//  Changer le statut
// ----------------------------------------------------------------
if (isset($_POST['changer_statut'])) {
    $statutsValides = ['brouillon', 'confirme', 'termine', 'annule'];
    $nouveauStatut  = $_POST['statut'] ?? '';

    if (in_array($nouveauStatut, $statutsValides)) {
        $pdo->prepare("UPDATE ITINERAIRES SET statut = :s WHERE id = :id")
            ->execute([':s' => $nouveauStatut, ':id' => $itinId]);
        $itin['statut'] = $nouveauStatut;
        $succes = "Statut mis à jour.";
    }
}

// ----------------------------------------------------------------
//  Ajouter un élément à l'itinéraire
// ----------------------------------------------------------------
if (isset($_POST['ajouter_element'])) {
    $type      = $_POST['type_element'] ?? '';
    $refId     = isset($_POST['reference_id']) && ctype_digit($_POST['reference_id']) ? (int)$_POST['reference_id'] : 0;
    $jour      = isset($_POST['numero_jour'])  && ctype_digit($_POST['numero_jour'])  ? (int)$_POST['numero_jour']  : 1;
    $typesOk   = ['transport', 'hebergement', 'activite'];

    if (!in_array($type, $typesOk) || $refId <= 0) {
        $erreur = "Sélection invalide.";
    } else {
        // Vérifier que la référence existe dans la bonne table
        $tables = ['transport' => 'TRANSPORTS', 'hebergement' => 'HEBERGEMENTS', 'activite' => 'ACTIVITES'];
        $table  = $tables[$type];
        $check  = $pdo->prepare("SELECT id FROM $table WHERE id = :id");
        $check->execute([':id' => $refId]);

        if (!$check->fetch()) {
            $erreur = "Élément introuvable.";
        } else {
            // Calculer la prochaine position dans ce jour
            $stmtPos = $pdo->prepare(
                "SELECT COALESCE(MAX(position_ordre), 0) + 1
                 FROM ELEMENTS_ITINERAIRE
                 WHERE itineraire_id = :iid AND numero_jour = :jour"
            );
            $stmtPos->execute([':iid' => $itinId, ':jour' => $jour]);
            $position = (int) $stmtPos->fetchColumn();

            $pdo->prepare(
                "INSERT INTO ELEMENTS_ITINERAIRE
                    (type_element, reference_id, numero_jour, position_ordre, itineraire_id)
                 VALUES (:type, :ref, :jour, :pos, :iid)"
            )->execute([
                ':type' => $type,
                ':ref'  => $refId,
                ':jour' => $jour,
                ':pos'  => $position,
                ':iid'  => $itinId,
            ]);

            // Recalculer le budget total de l'itinéraire
            recalculerBudget($pdo, $itinId);
            $succes = "Élément ajouté au jour $jour !";
        }
    }
}

// ----------------------------------------------------------------
//  Supprimer un élément
// ----------------------------------------------------------------
if (isset($_POST['supprimer_element'])) {
    $elemId = isset($_POST['elem_id']) && ctype_digit($_POST['elem_id']) ? (int)$_POST['elem_id'] : 0;

    if ($elemId > 0) {
        // Vérifier que l'élément appartient bien à cet itinéraire
        $check = $pdo->prepare(
            "SELECT id FROM ELEMENTS_ITINERAIRE WHERE id = :eid AND itineraire_id = :iid"
        );
        $check->execute([':eid' => $elemId, ':iid' => $itinId]);

        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM ELEMENTS_ITINERAIRE WHERE id = :id")->execute([':id' => $elemId]);
            recalculerBudget($pdo, $itinId);
            $succes = "Élément supprimé.";
        }
    }
}

// ----------------------------------------------------------------
//  Fonction locale : recalcule le budget total de l'itinéraire
// ----------------------------------------------------------------
function recalculerBudget(PDO $pdo, int $itinId): void
{
    $stmt = $pdo->prepare(
        "SELECT ei.type_element, ei.reference_id
         FROM ELEMENTS_ITINERAIRE ei
         WHERE ei.itineraire_id = :iid"
    );
    $stmt->execute([':iid' => $itinId]);
    $elements = $stmt->fetchAll();

    $total = 0.0;
    foreach ($elements as $el) {
        switch ($el['type_element']) {
            case 'transport':
                $r = $pdo->prepare("SELECT prix FROM TRANSPORTS WHERE id = :id");
                break;
            case 'hebergement':
                $r = $pdo->prepare("SELECT prix_par_nuit AS prix FROM HEBERGEMENTS WHERE id = :id");
                break;
            case 'activite':
                $r = $pdo->prepare("SELECT prix FROM ACTIVITES WHERE id = :id");
                break;
            default:
                continue 2;
        }
        $r->execute([':id' => $el['reference_id']]);
        $row = $r->fetch();
        if ($row) {
            $total += (float) $row['prix'];
        }
    }

    $pdo->prepare("UPDATE ITINERAIRES SET budget_total = :total WHERE id = :id")
        ->execute([':total' => $total, ':id' => $itinId]);
}

// ----------------------------------------------------------------
//  Récupérer les éléments de l'itinéraire avec les détails
// ----------------------------------------------------------------
$stmtElems = $pdo->prepare(
    "SELECT
        ei.id          AS elem_id,
        ei.type_element,
        ei.reference_id,
        ei.numero_jour,
        ei.position_ordre,
        -- Transports
        t.compagnie     AS t_compagnie,
        t.lieu_depart   AS t_depart,
        t.lieu_arrivee  AS t_arrivee,
        t.type          AS t_type,
        t.prix          AS t_prix,
        -- Hébergements
        h.nom           AS h_nom,
        h.type          AS h_type,
        h.prix_par_nuit AS h_prix,
        h.etoiles       AS h_etoiles,
        -- Activités
        a.nom           AS a_nom,
        a.categorie     AS a_categorie,
        a.prix          AS a_prix,
        a.duree_heures  AS a_duree
     FROM ELEMENTS_ITINERAIRE ei
     LEFT JOIN TRANSPORTS   t ON ei.type_element = 'transport'   AND ei.reference_id = t.id
     LEFT JOIN HEBERGEMENTS h ON ei.type_element = 'hebergement' AND ei.reference_id = h.id
     LEFT JOIN ACTIVITES    a ON ei.type_element = 'activite'    AND ei.reference_id = a.id
     WHERE ei.itineraire_id = :iid
     ORDER BY ei.numero_jour, ei.position_ordre"
);
$stmtElems->execute([':iid' => $itinId]);
$elements = $stmtElems->fetchAll();

// Grouper par jour pour l'affichage
$parJour = [];
foreach ($elements as $el) {
    $parJour[$el['numero_jour']][] = $el;
}

// Recharger le budget après éventuelles modifs
$stmtItin = $pdo->prepare("SELECT * FROM ITINERAIRES WHERE id = :id");
$stmtItin->execute([':id' => $itinId]);
$itin = $stmtItin->fetch();

// Nombre de jours du séjour
$nbJours = (int) ceil((strtotime($itin['date_fin']) - strtotime($itin['date_debut'])) / 86400) + 1;

// Listes pour le formulaire d'ajout
$allTransports   = $pdo->query("SELECT t.id, t.compagnie, t.lieu_depart, t.lieu_arrivee, t.prix, d.nom AS destination FROM TRANSPORTS t JOIN DESTINATIONS d ON d.id = t.destination_id ORDER BY d.nom, t.prix")->fetchAll();
$allHebergements = $pdo->query("SELECT h.id, h.nom, h.type, h.prix_par_nuit, d.nom AS destination FROM HEBERGEMENTS h JOIN DESTINATIONS d ON d.id = h.destination_id ORDER BY d.nom, h.prix_par_nuit")->fetchAll();
$allActivites    = $pdo->query("SELECT a.id, a.nom, a.categorie, a.prix, d.nom AS destination FROM ACTIVITES a JOIN DESTINATIONS d ON d.id = a.destination_id ORDER BY d.nom, a.prix")->fetchAll();

$statuts = [
    'brouillon' => ['label' => 'Brouillon',  'classe' => 'statut-brouillon'],
    'confirme'  => ['label' => 'Confirmé',   'classe' => 'statut-confirme'],
    'termine'   => ['label' => 'Terminé',    'classe' => 'statut-termine'],
    'annule'    => ['label' => 'Annulé',     'classe' => 'statut-annule'],
];
$statutInfo = $statuts[$itin['statut']] ?? $statuts['brouillon'];

$titrePage = securiser($itin['titre']);
require_once '../includes/header.php';
?>

<div style="background:white; border-bottom:1px solid var(--bordure);">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= URL_BASE ?>/index.php">Accueil</a>
            <span class="breadcrumb-sep">›</span>
            <a href="mon_itineraire.php">Mes itinéraires</a>
            <span class="breadcrumb-sep">›</span>
            <span><?= securiser($itin['titre']) ?></span>
        </nav>
    </div>
</div>

<!-- En-tête itinéraire -->
<div style="background:linear-gradient(135deg,var(--bleu),#023E8A); color:white; padding:2.5rem 0;">
    <div class="container flex-entre">
        <div>
            <span class="statut <?= $statutInfo['classe'] ?>" style="margin-bottom:0.6rem; display:inline-block;">
                <?= $statutInfo['label'] ?>
            </span>
            <h1 style="color:white; margin-bottom:0.4rem;"><?= securiser($itin['titre']) ?></h1>
            <p style="color:rgba(255,255,255,0.8); margin:0;">
                📅 <?= formatDate($itin['date_debut']) ?> → <?= formatDate($itin['date_fin']) ?>
                &nbsp;·&nbsp; <?= $nbJours ?> jour<?= $nbJours > 1 ? 's' : '' ?>
                &nbsp;·&nbsp; 📌 <?= count($elements) ?> élément<?= count($elements) > 1 ? 's' : '' ?>
            </p>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.8rem; color:rgba(255,255,255,0.7); margin-bottom:0.25rem;">Budget total estimé</div>
            <div style="font-family:'Poppins',sans-serif; font-size:2rem; font-weight:800;">
                <?= formatPrix((float)$itin['budget_total']) ?>
            </div>
        </div>
    </div>
</div>

<?php if ($succes): ?>
    <div class="alerte alerte-succes" style="margin:0; border-radius:0; border-left:none; border-bottom:1px solid #b7e4c7; text-align:center;">
        ✅ <?= securiser($succes) ?>
    </div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alerte alerte-erreur" style="margin:0; border-radius:0; border-left:none; border-bottom:1px solid #f5c6c6; text-align:center;">
        ✕ <?= securiser($erreur) ?>
    </div>
<?php endif; ?>

<div class="container section">
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:2rem; align-items:start;">

        <!-- ============================================================
             PROGRAMME JOUR PAR JOUR
        ============================================================ -->
        <div>
            <div class="flex-entre mb-3">
                <h2 style="font-size:1.2rem;">Programme du séjour</h2>
                <span style="font-size:0.85rem; color:var(--texte-doux);">
                    <?= $nbJours ?> jour<?= $nbJours > 1 ? 's' : '' ?> de voyage
                </span>
            </div>

            <?php if (empty($elements)): ?>
                <div class="boite centrer" style="padding:2.5rem; border:2px dashed var(--bordure);">
                    <div style="font-size:2.5rem; margin-bottom:0.75rem;">📋</div>
                    <p style="color:var(--texte-doux); margin:0;">
                        Aucun élément pour l'instant.<br>
                        Utilisez le formulaire ci-contre pour ajouter des transports, hébergements et activités.
                    </p>
                </div>
            <?php else: ?>
                <?php for ($j = 1; $j <= max(array_keys($parJour)); $j++): ?>
                    <?php if (!isset($parJour[$j])) continue; ?>

                    <!-- Bloc jour -->
                    <div style="margin-bottom:1.5rem;">
                        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                            <div style="background:var(--bleu); color:white; font-family:'Poppins',sans-serif; font-weight:700; font-size:0.82rem; padding:0.3rem 0.85rem; border-radius:50px; white-space:nowrap;">
                                Jour <?= $j ?>
                            </div>
                            <div style="flex:1; height:1px; background:var(--bordure);"></div>
                        </div>

                        <?php foreach ($parJour[$j] as $el): ?>
                            <div style="display:flex; align-items:center; gap:1rem; padding:0.85rem 1rem; background:white; border:1px solid var(--bordure); border-radius:var(--rayon-petit); margin-bottom:0.5rem;">

                                <?php if ($el['type_element'] === 'transport'): ?>
                                    <span style="font-size:1.4rem;">✈</span>
                                    <div style="flex:1;">
                                        <div style="font-weight:700; font-size:0.92rem;">
                                            <?= securiser($el['t_compagnie']) ?>
                                        </div>
                                        <div style="font-size:0.8rem; color:var(--texte-doux);">
                                            <?= securiser($el['t_depart']) ?> → <?= securiser($el['t_arrivee']) ?>
                                            · <?= ucfirst(securiser($el['t_type'])) ?>
                                        </div>
                                    </div>
                                    <span style="font-weight:700; color:var(--bleu);"><?= formatPrix((float)$el['t_prix']) ?></span>

                                <?php elseif ($el['type_element'] === 'hebergement'): ?>
                                    <span style="font-size:1.4rem;">🏨</span>
                                    <div style="flex:1;">
                                        <div style="font-weight:700; font-size:0.92rem;">
                                            <?= securiser($el['h_nom']) ?>
                                        </div>
                                        <div style="font-size:0.8rem; color:var(--texte-doux);">
                                            <?= ucfirst(securiser($el['h_type'])) ?>
                                            <?= $el['h_etoiles'] ? ' · ' . afficherEtoiles((int)$el['h_etoiles']) : '' ?>
                                        </div>
                                    </div>
                                    <span style="font-weight:700; color:var(--bleu);"><?= formatPrix((float)$el['h_prix']) ?>/nuit</span>

                                <?php elseif ($el['type_element'] === 'activite'): ?>
                                    <span style="font-size:1.4rem;">🎭</span>
                                    <div style="flex:1;">
                                        <div style="font-weight:700; font-size:0.92rem;">
                                            <?= securiser($el['a_nom']) ?>
                                        </div>
                                        <div style="font-size:0.8rem; color:var(--texte-doux);">
                                            <?= securiser($el['a_categorie']) ?> · ⏱ <?= securiser((string)$el['a_duree']) ?>h
                                        </div>
                                    </div>
                                    <span style="font-weight:700; color:var(--bleu);">
                                        <?= $el['a_prix'] > 0 ? formatPrix((float)$el['a_prix']) : 'Gratuit' ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Bouton supprimer -->
                                <form action="detail.php?id=<?= $itinId ?>" method="POST">
                                    <input type="hidden" name="elem_id" value="<?= $el['elem_id'] ?>">
                                    <button type="submit" name="supprimer_element"
                                            class="btn btn-danger btn-petit"
                                            onclick="return confirm('Retirer cet élément ?')">
                                        ✕
                                    </button>
                                </form>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>

        <!-- ============================================================
             COLONNE LATÉRALE
        ============================================================ -->
        <div style="display:flex; flex-direction:column; gap:1.25rem; position:sticky; top:80px;">

            <!-- Changer le statut -->
            <div class="boite">
                <h3 style="font-size:1rem; margin-bottom:0.85rem;">⚙ Statut du séjour</h3>
                <form action="detail.php?id=<?= $itinId ?>" method="POST">
                    <div class="champ-groupe" style="margin-bottom:0.75rem;">
                        <select name="statut" class="champ">
                            <?php foreach ($statuts as $val => $info): ?>
                                <option value="<?= $val ?>" <?= $itin['statut'] === $val ? 'selected' : '' ?>>
                                    <?= $info['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="changer_statut" class="btn btn-secondaire btn-bloc">
                        Mettre à jour
                    </button>
                </form>
            </div>

            <!-- Ajouter un élément -->
            <div class="boite">
                <h3 style="font-size:1rem; margin-bottom:0.85rem;">➕ Ajouter un élément</h3>
                <form action="detail.php?id=<?= $itinId ?>" method="POST" id="formAjout">

                    <div class="champ-groupe">
                        <label for="type_element">Type</label>
                        <select id="type_element" name="type_element" class="champ" onchange="basculerListe(this.value)">
                            <option value="transport">✈ Transport</option>
                            <option value="hebergement">🏨 Hébergement</option>
                            <option value="activite">🎭 Activité</option>
                        </select>
                    </div>

                    <!-- Liste transports -->
                    <div id="liste-transport" class="champ-groupe">
                        <label for="ref-transport">Transport</label>
                        <select id="ref-transport" name="reference_id" class="champ">
                            <?php foreach ($allTransports as $t): ?>
                                <option value="<?= $t['id'] ?>">
                                    <?= securiser($t['destination']) ?> — <?= securiser($t['compagnie']) ?>
                                    (<?= formatPrix((float)$t['prix']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Liste hébergements (cachée par défaut) -->
                    <div id="liste-hebergement" class="champ-groupe" style="display:none;">
                        <label for="ref-hebergement">Hébergement</label>
                        <select id="ref-hebergement" class="champ">
                            <?php foreach ($allHebergements as $h): ?>
                                <option value="<?= $h['id'] ?>">
                                    <?= securiser($h['destination']) ?> — <?= securiser($h['nom']) ?>
                                    (<?= formatPrix((float)$h['prix_par_nuit']) ?>/nuit)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Liste activités (cachée par défaut) -->
                    <div id="liste-activite" class="champ-groupe" style="display:none;">
                        <label for="ref-activite">Activité</label>
                        <select id="ref-activite" class="champ">
                            <?php foreach ($allActivites as $a): ?>
                                <option value="<?= $a['id'] ?>">
                                    <?= securiser($a['destination']) ?> — <?= securiser($a['nom']) ?>
                                    (<?= $a['prix'] > 0 ? formatPrix((float)$a['prix']) : 'Gratuit' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="champ-groupe">
                        <label for="numero_jour">Jour du séjour</label>
                        <select id="numero_jour" name="numero_jour" class="champ">
                            <?php for ($j = 1; $j <= $nbJours; $j++): ?>
                                <option value="<?= $j ?>">Jour <?= $j ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" name="ajouter_element" class="btn btn-primaire btn-bloc">
                        Ajouter au programme
                    </button>
                </form>
            </div>

            <!-- Lien panier -->
            <a href="<?= URL_BASE ?>/reservations/panier.php" class="btn btn-orange btn-bloc">
                🛒 Aller au panier
            </a>

        </div>
    </div>
</div>

<script>
// Synchronise le select visible avec le champ name="reference_id" du formulaire
function basculerListe(type) {
    const listes = ['transport', 'hebergement', 'activite'];
    listes.forEach(function (t) {
        const div = document.getElementById('liste-' + t);
        div.style.display = t === type ? 'block' : 'none';
    });

    // Mettre à jour le select actif pour qu'il ait name="reference_id"
    listes.forEach(function (t) {
        const sel = document.getElementById('ref-' + t);
        sel.name = t === type ? 'reference_id' : '';
    });
}

// Initialisation au chargement
basculerListe('transport');
</script>

<?php require_once '../includes/footer.php'; ?>
