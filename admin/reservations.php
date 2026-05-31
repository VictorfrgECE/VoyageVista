<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estAdmin()) {
    rediriger('../index.php');
}

$succes = '';
$erreur = '';

// ----------------------------------------------------------------
//  Changer le statut d'une réservation
// ----------------------------------------------------------------
if (isset($_POST['changer_statut'])) {
    $resaId    = (int)($_POST['resa_id'] ?? 0);
    $statut    = trim($_POST['statut'] ?? '');
    $statutsOk = ['en_attente', 'confirme', 'annule', 'rembourse'];

    if ($resaId <= 0) {
        $erreur = "Réservation invalide.";
    } elseif (!in_array($statut, $statutsOk)) {
        $erreur = "Statut invalide.";
    } else {
        // Récupérer le statut actuel et l'utilisateur
        $stmtResa = $pdo->prepare(
            "SELECT utilisateur_id, statut FROM RESERVATIONS WHERE id = :id"
        );
        $stmtResa->execute([':id' => $resaId]);
        $resaRow = $stmtResa->fetch();

        if (!$resaRow) {
            $erreur = "Réservation introuvable.";
        } else {
            $pdo->prepare("UPDATE RESERVATIONS SET statut = :s WHERE id = :id")
                ->execute([':s' => $statut, ':id' => $resaId]);
            $succes = "Statut de la réservation #$resaId mis à jour.";

            // Notifier l'utilisateur si la réservation est confirmée ou annulée
            if (in_array($statut, ['confirme', 'annule'])) {
                $msg = $statut === 'confirme'
                    ? "Votre réservation #$resaId a été confirmée par l'administration."
                    : "Votre réservation #$resaId a été annulée. Contactez-nous pour plus d'informations.";
                creerNotification(
                    $pdo,
                    (int)$resaRow['utilisateur_id'],
                    "Réservation #$resaId — " . ($statut === 'confirme' ? "Confirmée" : "Annulée"),
                    $msg,
                    $statut === 'confirme' ? 'confirmation' : 'alerte'
                );
            }
        }
    }
}

// ----------------------------------------------------------------
//  Filtres
// ----------------------------------------------------------------
$filtreStatut = isset($_GET['statut'])    ? trim($_GET['statut'])    : '';
$filtreType   = isset($_GET['type'])      ? trim($_GET['type'])      : '';
$recherche    = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';

$statutsValides = ['en_attente', 'confirme', 'annule', 'rembourse'];
$typesValides   = ['transport', 'hebergement', 'activite'];

if ($filtreStatut !== '' && !in_array($filtreStatut, $statutsValides)) $filtreStatut = '';
if ($filtreType   !== '' && !in_array($filtreType,   $typesValides))   $filtreType   = '';

$conditions = [];
$params     = [];

if ($filtreStatut !== '') {
    $conditions[] = "r.statut = :statut";
    $params[':statut'] = $filtreStatut;
}
if ($filtreType !== '') {
    $conditions[] = "r.type = :type";
    $params[':type'] = $filtreType;
}
if ($recherche !== '') {
    $conditions[] = "(u.nom LIKE :rech OR u.email LIKE :rech2)";
    $params[':rech']  = '%' . $recherche . '%';
    $params[':rech2'] = '%' . $recherche . '%';
}

$sql = "SELECT r.*, u.nom AS user_nom, u.email AS user_email
        FROM RESERVATIONS r
        JOIN UTILISATEURS u ON u.id = r.utilisateur_id";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY r.date_reservation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Stats par statut (toutes réservations, sans filtre)
$statsRaw = $pdo->query(
    "SELECT statut, COUNT(*) AS nb, COALESCE(SUM(prix_total), 0) AS total
     FROM RESERVATIONS GROUP BY statut"
)->fetchAll();

$statsResa = [];
foreach ($statsRaw as $row) {
    $statsResa[$row['statut']] = $row;
}

// Chiffre d'affaires total (réservations confirmées uniquement)
$caTotal = (float)$pdo->query(
    "SELECT COALESCE(SUM(prix_total), 0) FROM RESERVATIONS WHERE statut = 'confirme'"
)->fetchColumn();

$statutsConfig = [
    'en_attente' => ['label' => 'En attente', 'classe' => 'statut-attente'],
    'confirme'   => ['label' => 'Confirmé',   'classe' => 'statut-confirme'],
    'annule'     => ['label' => 'Annulé',     'classe' => 'statut-annule'],
    'rembourse'  => ['label' => 'Remboursé',  'classe' => 'statut-brouillon'],
];

$typeIcones = ['transport' => '&#9992;', 'hebergement' => '&#127968;', 'activite' => '&#127914;'];
$typeLabels = ['transport' => 'Transport', 'hebergement' => 'Hébergement', 'activite' => 'Activité'];

$titrePage = 'Admin — Réservations';
require_once '../includes/header.php';
?>

<!-- Barre admin -->
<div style="background:#1A1A2E; color:white; padding:0.6rem 0; font-size:0.82rem;">
    <div class="container flex-entre">
        <span>&#9881; Espace Administration</span>
        <div style="display:flex; gap:1rem;">
            <a href="index.php"        style="color:rgba(255,255,255,0.7);">Tableau de bord</a>
            <a href="destinations.php" style="color:rgba(255,255,255,0.7);">Destinations</a>
            <a href="utilisateurs.php" style="color:rgba(255,255,255,0.7);">Utilisateurs</a>
            <a href="reservations.php" style="color:white; font-weight:700;">R&eacute;servations</a>
        </div>
    </div>
</div>

<div class="page-entete">
    <div class="container flex-entre">
        <div>
            <h1>Suivi des r&eacute;servations</h1>
            <p>
                <?= count($reservations) ?> r&eacute;servation<?= count($reservations) > 1 ? 's' : '' ?>
                affich&eacute;<?= count($reservations) > 1 ? 'es' : 'e' ?>
                &mdash; CA confirm&eacute; : <strong><?= formatPrix($caTotal) ?></strong>
            </p>
        </div>
    </div>
</div>

<div class="container section">

    <?php if ($succes !== ''): ?>
        <div class="alerte alerte-succes"><?= securiser($succes) ?></div>
    <?php endif; ?>
    <?php if ($erreur !== ''): ?>
        <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
    <?php endif; ?>

    <!-- Boutons filtres par statut -->
    <div style="display:flex; gap:0.6rem; flex-wrap:wrap; margin-bottom:1.5rem;">
        <?php
        $statsBadges = [
            ''           => 'Toutes',
            'en_attente' => 'En attente',
            'confirme'   => 'Confirm&eacute;es',
            'annule'     => 'Annul&eacute;es',
            'rembourse'  => 'Rembours&eacute;es',
        ];
        $totalToutesResas = array_sum(array_column($statsRaw, 'nb'));
        foreach ($statsBadges as $val => $label):
            $nb    = $val === '' ? $totalToutesResas : (isset($statsResa[$val]) ? $statsResa[$val]['nb'] : 0);
            $actif = $filtreStatut === $val;
            $url   = 'reservations.php?statut=' . urlencode($val)
                   . ($filtreType  !== '' ? '&type='      . urlencode($filtreType)  : '')
                   . ($recherche   !== '' ? '&recherche=' . urlencode($recherche)   : '');
        ?>
            <a href="<?= $url ?>"
               style="display:inline-flex; align-items:center; gap:0.35rem;
                      padding:0.45rem 1rem; border-radius:50px; font-size:0.85rem;
                      font-weight:700; text-decoration:none;
                      background:<?= $actif ? 'var(--bleu)' : 'white' ?>;
                      color:<?= $actif ? 'white' : 'var(--texte-doux)' ?>;
                      border:2px solid <?= $actif ? 'var(--bleu)' : 'var(--bordure)' ?>;">
                <?= $label ?>
                <span style="background:<?= $actif ? 'rgba(255,255,255,0.25)' : 'var(--fond)' ?>;
                             padding:0.1rem 0.45rem; border-radius:50px; font-size:0.75rem;">
                    <?= $nb ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtres secondaires (type + recherche) -->
    <form action="reservations.php" method="GET" class="filtres" style="margin-bottom:1.5rem;">
        <?php if ($filtreStatut !== ''): ?>
            <input type="hidden" name="statut" value="<?= securiser($filtreStatut) ?>">
        <?php endif; ?>
        <div class="filtre-groupe" style="flex:2; min-width:180px;">
            <label for="f-rech">Utilisateur</label>
            <input type="text" id="f-rech" name="recherche" class="champ"
                   placeholder="Nom ou email..."
                   value="<?= securiser($recherche) ?>">
        </div>
        <div class="filtre-groupe" style="min-width:160px;">
            <label for="f-type">Type</label>
            <select id="f-type" name="type" class="champ">
                <option value="">Tous les types</option>
                <option value="transport"   <?= $filtreType === 'transport'   ? 'selected' : '' ?>>Transport</option>
                <option value="hebergement" <?= $filtreType === 'hebergement' ? 'selected' : '' ?>>H&eacute;bergement</option>
                <option value="activite"    <?= $filtreType === 'activite'    ? 'selected' : '' ?>>Activit&eacute;</option>
            </select>
        </div>
        <div class="filtre-groupe" style="flex:0; min-width:auto;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primaire">Filtrer</button>
        </div>
        <?php if ($recherche !== '' || $filtreType !== ''): ?>
            <div class="filtre-groupe" style="flex:0; min-width:auto;">
                <label>&nbsp;</label>
                <a href="reservations.php<?= $filtreStatut !== '' ? '?statut=' . urlencode($filtreStatut) : '' ?>"
                   class="btn btn-secondaire">&#10005;</a>
            </div>
        <?php endif; ?>
    </form>

    <!-- Tableau des réservations -->
    <?php if (empty($reservations)): ?>
        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">&#127915;</div>
            <h3>Aucune r&eacute;servation trouv&eacute;e</h3>
            <p style="color:var(--texte-doux);">Essayez de modifier vos filtres.</p>
        </div>
    <?php else: ?>

        <!-- Résumé chiffre d'affaires filtré -->
        <?php $totalFiltre = array_sum(array_column($reservations, 'prix_total')); ?>
        <div style="text-align:right; margin-bottom:0.75rem; font-size:0.88rem; color:var(--texte-doux);">
            Total affich&eacute; :
            <strong style="color:var(--bleu); font-size:1rem;"><?= formatPrix($totalFiltre) ?></strong>
        </div>

        <div class="tableau-conteneur">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Utilisateur</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Modifier statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r):
                        $cfg = $statutsConfig[$r['statut']] ?? ['label' => securiser($r['statut']), 'classe' => ''];
                    ?>
                        <tr>
                            <td style="color:var(--texte-doux); font-size:0.82rem;">#<?= $r['id'] ?></td>
                            <td>
                                <div style="font-weight:700; font-size:0.88rem;"><?= securiser($r['user_nom']) ?></div>
                                <div style="font-size:0.75rem; color:var(--texte-doux);"><?= securiser($r['user_email']) ?></div>
                            </td>
                            <td>
                                <?= $typeIcones[$r['type']] ?? '' ?>
                                <?= securiser($typeLabels[$r['type']] ?? $r['type']) ?>
                                <div style="font-size:0.75rem; color:var(--texte-doux);">
                                    r&eacute;f. #<?= (int)$r['reference_id'] ?>
                                </div>
                            </td>
                            <td style="font-weight:700; color:var(--bleu); white-space:nowrap;">
                                <?= formatPrix((float)$r['prix_total']) ?>
                            </td>
                            <td>
                                <span class="statut <?= $cfg['classe'] ?>">
                                    <?= $cfg['label'] ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem; white-space:nowrap;">
                                <?= date('d/m/Y H:i', strtotime($r['date_reservation'])) ?>
                            </td>
                            <td>
                                <form action="reservations.php" method="POST"
                                      style="display:flex; gap:0.35rem; align-items:center;">
                                    <!-- Conserver les filtres actifs après submit -->
                                    <?php if ($filtreStatut !== ''): ?>
                                        <input type="hidden" name="_statut_filtre" value="<?= securiser($filtreStatut) ?>">
                                    <?php endif; ?>
                                    <input type="hidden" name="resa_id" value="<?= $r['id'] ?>">
                                    <select name="statut" class="champ"
                                            style="padding:0.3rem 0.5rem; font-size:0.78rem; width:auto;">
                                        <?php foreach ($statutsConfig as $val => $scfg): ?>
                                            <option value="<?= $val ?>"
                                                <?= $r['statut'] === $val ? 'selected' : '' ?>>
                                                <?= $scfg['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="changer_statut"
                                            class="btn btn-secondaire btn-petit"
                                            title="Appliquer le statut">&#10003;</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right; font-size:0.88rem; color:var(--texte-doux);">
                            Sous-total :
                        </td>
                        <td style="font-weight:800; color:var(--bleu); white-space:nowrap;">
                            <?= formatPrix($totalFiltre) ?>
                        </td>
                        <td colspan="3" style="font-size:0.82rem; color:var(--texte-doux);">
                            <?= count($reservations) ?> r&eacute;servation<?= count($reservations) > 1 ? 's' : '' ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align:right; font-size:0.88rem;">
                            <strong>CA confirm&eacute; (total) :</strong>
                        </td>
                        <td colspan="4" style="font-weight:800; color:var(--succes); white-space:nowrap;">
                            <?= formatPrix($caTotal) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
