<?php
session_start();
require_once '../config/connexion.php';
require_once '../config/constantes.php';
require_once '../includes/fonctions.php';

if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

$uid = $_SESSION['utilisateur_id'];

// ----------------------------------------------------------------
//  Actions POST
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Marquer une notification comme lue
    if (isset($_POST['marquer_lu']) && isset($_POST['notif_id'])) {
        $nid = (int)$_POST['notif_id'];
        $pdo->prepare("UPDATE NOTIFICATIONS SET est_lu = 1 WHERE id = :id AND utilisateur_id = :uid")
            ->execute([':id' => $nid, ':uid' => $uid]);
    }

    // Marquer toutes comme lues
    if (isset($_POST['tout_lire'])) {
        $pdo->prepare("UPDATE NOTIFICATIONS SET est_lu = 1 WHERE utilisateur_id = :uid")
            ->execute([':uid' => $uid]);
    }

    // Supprimer une notification
    if (isset($_POST['supprimer']) && isset($_POST['notif_id'])) {
        $nid = (int)$_POST['notif_id'];
        $pdo->prepare("DELETE FROM NOTIFICATIONS WHERE id = :id AND utilisateur_id = :uid")
            ->execute([':id' => $nid, ':uid' => $uid]);
    }

    // Supprimer toutes les notifications lues
    if (isset($_POST['supprimer_lues'])) {
        $pdo->prepare("DELETE FROM NOTIFICATIONS WHERE utilisateur_id = :uid AND est_lu = 1")
            ->execute([':uid' => $uid]);
    }

    rediriger('mes_notifications.php' . (isset($_GET['type']) ? '?type=' . urlencode($_GET['type']) : ''));
}

// ----------------------------------------------------------------
//  Filtre par type
// ----------------------------------------------------------------
$typesValides = ['info', 'alerte', 'confirmation', 'rappel'];
$filtreType   = isset($_GET['type']) && in_array($_GET['type'], $typesValides) ? $_GET['type'] : '';

$conditions = ["utilisateur_id = :uid"];
$params     = [':uid' => $uid];

if ($filtreType !== '') {
    $conditions[] = "type = :type";
    $params[':type'] = $filtreType;
}

$sql = "SELECT * FROM NOTIFICATIONS WHERE " . implode(" AND ", $conditions) . " ORDER BY date_creation DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Compteurs
$nbNonLues = $pdo->prepare("SELECT COUNT(*) FROM NOTIFICATIONS WHERE utilisateur_id = :uid AND est_lu = 0");
$nbNonLues->execute([':uid' => $uid]);
$nbNonLues = (int)$nbNonLues->fetchColumn();

// Icônes et couleurs par type
$typesConfig = [
    'info'         => ['icone' => 'ℹ',  'couleur' => '#0077B6', 'label' => 'Info'],
    'alerte'       => ['icone' => '⚠',  'couleur' => '#D62828', 'label' => 'Alerte'],
    'confirmation' => ['icone' => '✅', 'couleur' => '#2D9E4E', 'label' => 'Confirmation'],
    'rappel'       => ['icone' => '🔔', 'couleur' => '#F4A261', 'label' => 'Rappel'],
];

$titrePage = 'Mes notifications';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container flex-entre">
        <div>
            <h1>🔔 Mes notifications</h1>
            <p>
                <?= count($notifications) ?> notification<?= count($notifications) > 1 ? 's' : '' ?>
                <?php if ($nbNonLues > 0): ?>
                    — <strong style="color:var(--orange);"><?= $nbNonLues ?> non lue<?= $nbNonLues > 1 ? 's' : '' ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <!-- Actions globales -->
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <?php if ($nbNonLues > 0): ?>
                <form action="mes_notifications.php" method="POST">
                    <button type="submit" name="tout_lire" class="btn btn-secondaire btn-petit">
                        ✓ Tout marquer comme lu
                    </button>
                </form>
            <?php endif; ?>
            <form action="mes_notifications.php" method="POST"
                  onsubmit="return confirm('Supprimer toutes les notifications lues ?')">
                <button type="submit" name="supprimer_lues" class="btn btn-danger btn-petit">
                    ✕ Supprimer les lues
                </button>
            </form>
        </div>
    </div>
</div>

<div class="container section">
    <div style="display:grid; grid-template-columns:200px 1fr; gap:2rem; align-items:start;">

        <!-- Filtres par type (colonne gauche) -->
        <div style="position:sticky; top:80px;">
            <div class="boite" style="padding:1rem;">
                <h3 style="font-size:0.88rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--texte-doux); margin-bottom:0.75rem;">
                    Filtrer
                </h3>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:0.25rem;">
                    <li>
                        <a href="mes_notifications.php"
                           style="display:flex; align-items:center; gap:0.5rem; padding:0.5rem 0.75rem; border-radius:var(--rayon-petit); font-size:0.88rem; font-weight:600; text-decoration:none;
                                  background:<?= $filtreType === '' ? 'var(--bleu)' : 'transparent' ?>;
                                  color:<?= $filtreType === '' ? 'white' : 'var(--texte-doux)' ?>;">
                            📋 Toutes
                        </a>
                    </li>
                    <?php foreach ($typesConfig as $type => $cfg): ?>
                        <?php
                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM NOTIFICATIONS WHERE utilisateur_id = :uid AND type = :t");
                        $stmtCount->execute([':uid' => $uid, ':t' => $type]);
                        $count = (int)$stmtCount->fetchColumn();
                        if ($count === 0) continue;
                        ?>
                        <li>
                            <a href="mes_notifications.php?type=<?= $type ?>"
                               style="display:flex; align-items:center; justify-content:space-between; padding:0.5rem 0.75rem; border-radius:var(--rayon-petit); font-size:0.88rem; font-weight:600; text-decoration:none;
                                      background:<?= $filtreType === $type ? 'var(--bleu)' : 'transparent' ?>;
                                      color:<?= $filtreType === $type ? 'white' : 'var(--texte-doux)' ?>;">
                                <span><?= $cfg['icone'] ?> <?= $cfg['label'] ?></span>
                                <span style="font-size:0.75rem; background:rgba(0,0,0,0.1); padding:0.1rem 0.4rem; border-radius:50px;">
                                    <?= $count ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Liste des notifications -->
        <div>
            <?php if (empty($notifications)): ?>
                <div class="boite centrer" style="padding:3rem;">
                    <div style="font-size:3.5rem; margin-bottom:1rem;">🔕</div>
                    <h3>Aucune notification</h3>
                    <p style="color:var(--texte-doux);">Vous êtes à jour !</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n):
                    $cfg   = $typesConfig[$n['type']] ?? $typesConfig['info'];
                    $nonLu = !(bool)$n['est_lu'];
                ?>
                    <div class="notif-item <?= $nonLu ? 'non-lue' : '' ?>"
                         style="border-left:4px solid <?= $nonLu ? $cfg['couleur'] : 'var(--bordure)' ?>;">

                        <div class="notif-icone"><?= $cfg['icone'] ?></div>

                        <div class="notif-corps">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;">
                                <div class="notif-titre">
                                    <?php if ($nonLu): ?>
                                        <span style="display:inline-block; width:8px; height:8px; background:<?= $cfg['couleur'] ?>; border-radius:50%; margin-right:0.4rem; vertical-align:middle;"></span>
                                    <?php endif; ?>
                                    <?= securiser($n['titre']) ?>
                                </div>
                                <div class="notif-date" style="flex-shrink:0;">
                                    <?= date('d/m/Y H:i', strtotime($n['date_creation'])) ?>
                                </div>
                            </div>

                            <?php if ($n['message']): ?>
                                <p class="notif-message" style="margin:0.3rem 0 0.6rem;">
                                    <?= securiser($n['message']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.5rem;">
                                <?php if ($nonLu): ?>
                                    <form action="mes_notifications.php" method="POST">
                                        <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                        <button type="submit" name="marquer_lu"
                                                class="btn btn-secondaire btn-petit"
                                                style="font-size:0.75rem;">
                                            ✓ Marquer comme lu
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form action="mes_notifications.php" method="POST">
                                    <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                    <button type="submit" name="supprimer"
                                            class="btn btn-danger btn-petit"
                                            style="font-size:0.75rem;"
                                            onclick="return confirm('Supprimer cette notification ?')">
                                        ✕ Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
