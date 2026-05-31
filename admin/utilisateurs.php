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
//  Changer le rôle d'un utilisateur
// ----------------------------------------------------------------
if (isset($_POST['changer_role'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $role   = trim($_POST['role'] ?? '');

    if ($userId <= 0) {
        $erreur = "Utilisateur invalide.";
    } elseif (!in_array($role, ['admin', 'user', 'etudiant'])) {
        $erreur = "Rôle invalide.";
    } elseif ($userId === (int)$_SESSION['utilisateur_id']) {
        $erreur = "Vous ne pouvez pas modifier votre propre rôle.";
    } else {
        // Vérifier que l'utilisateur existe
        $stmtCheck = $pdo->prepare("SELECT nom FROM UTILISATEURS WHERE id = :id");
        $stmtCheck->execute([':id' => $userId]);
        $nomUser = $stmtCheck->fetchColumn();
        if (!$nomUser) {
            $erreur = "Utilisateur introuvable.";
        } else {
            $pdo->prepare("UPDATE UTILISATEURS SET role = :role WHERE id = :id")
                ->execute([':role' => $role, ':id' => $userId]);
            $succes = "Rôle de « $nomUser » mis à jour vers « $role ».";
        }
    }
}

// ----------------------------------------------------------------
//  Supprimer un utilisateur
// ----------------------------------------------------------------
if (isset($_POST['supprimer'])) {
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        $erreur = "Utilisateur invalide.";
    } elseif ($userId === (int)$_SESSION['utilisateur_id']) {
        $erreur = "Impossible de supprimer votre propre compte.";
    } else {
        // Vérifier qu'il n'a pas de réservations confirmées
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM RESERVATIONS WHERE utilisateur_id = :id AND statut = 'confirme'"
        );
        $stmt->execute([':id' => $userId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $erreur = "Impossible de supprimer : cet utilisateur a des réservations confirmées.";
        } else {
            // Récupérer le nom avant suppression
            $stmtNom = $pdo->prepare("SELECT nom FROM UTILISATEURS WHERE id = :id");
            $stmtNom->execute([':id' => $userId]);
            $nomUser = $stmtNom->fetchColumn();

            $pdo->prepare("DELETE FROM UTILISATEURS WHERE id = :id")->execute([':id' => $userId]);
            $succes = $nomUser ? "Utilisateur « $nomUser » supprimé." : "Utilisateur supprimé.";
        }
    }
}

// ----------------------------------------------------------------
//  Filtres et recherche
// ----------------------------------------------------------------
$recherche  = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';
$filtreRole = isset($_GET['role'])      ? trim($_GET['role'])      : '';

$conditions = [];
$params     = [];

if ($recherche !== '') {
    $conditions[] = "(u.nom LIKE :rech OR u.email LIKE :rech2)";
    $params[':rech']  = '%' . $recherche . '%';
    $params[':rech2'] = '%' . $recherche . '%';
}
if ($filtreRole !== '' && in_array($filtreRole, ['admin', 'user', 'etudiant'])) {
    $conditions[] = "u.role = :role";
    $params[':role'] = $filtreRole;
}

$sql = "SELECT u.*,
               COUNT(DISTINCT r.id) AS nb_reservations,
               COUNT(DISTINCT i.id) AS nb_itineraires
        FROM UTILISATEURS u
        LEFT JOIN RESERVATIONS r ON r.utilisateur_id = u.id
        LEFT JOIN ITINERAIRES  i ON i.utilisateur_id = u.id";

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " GROUP BY u.id ORDER BY u.date_inscription DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

// Nombre total d'utilisateurs (sans filtre)
$totalUtilisateurs = (int)$pdo->query("SELECT COUNT(*) FROM UTILISATEURS")->fetchColumn();

$roleColors = ['admin' => '#D62828', 'user' => '#0077B6', 'etudiant' => '#2D9E4E'];

$titrePage = 'Admin — Utilisateurs';
require_once '../includes/header.php';
?>

<!-- Barre admin -->
<div style="background:#1A1A2E; color:white; padding:0.6rem 0; font-size:0.82rem;">
    <div class="container flex-entre">
        <span>&#9881; Espace Administration</span>
        <div style="display:flex; gap:1rem;">
            <a href="index.php"        style="color:rgba(255,255,255,0.7);">Tableau de bord</a>
            <a href="destinations.php" style="color:rgba(255,255,255,0.7);">Destinations</a>
            <a href="utilisateurs.php" style="color:white; font-weight:700;">Utilisateurs</a>
            <a href="reservations.php" style="color:rgba(255,255,255,0.7);">R&eacute;servations</a>
        </div>
    </div>
</div>

<div class="page-entete">
    <div class="container flex-entre">
        <div>
            <h1>Gestion des utilisateurs</h1>
            <p>
                <?= count($utilisateurs) ?> r&eacute;sultat<?= count($utilisateurs) > 1 ? 's' : '' ?>
                affich&eacute;<?= count($utilisateurs) > 1 ? 's' : '' ?>
                &mdash; <?= $totalUtilisateurs ?> au total
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

    <!-- Filtres -->
    <form action="utilisateurs.php" method="GET" class="filtres" style="margin-bottom:1.5rem;">
        <div class="filtre-groupe" style="flex:2; min-width:200px;">
            <label for="f-rech">Rechercher</label>
            <input type="text" id="f-rech" name="recherche" class="champ"
                   placeholder="Nom ou email..."
                   value="<?= securiser($recherche) ?>">
        </div>
        <div class="filtre-groupe" style="min-width:150px;">
            <label for="f-role">R&ocirc;le</label>
            <select id="f-role" name="role" class="champ">
                <option value="">Tous les r&ocirc;les</option>
                <option value="admin"    <?= $filtreRole === 'admin'    ? 'selected' : '' ?>>Admin</option>
                <option value="user"     <?= $filtreRole === 'user'     ? 'selected' : '' ?>>Utilisateur</option>
                <option value="etudiant" <?= $filtreRole === 'etudiant' ? 'selected' : '' ?>>&Eacute;tudiant</option>
            </select>
        </div>
        <div class="filtre-groupe" style="flex:0; min-width:auto;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primaire">Filtrer</button>
        </div>
        <?php if ($recherche !== '' || $filtreRole !== ''): ?>
            <div class="filtre-groupe" style="flex:0; min-width:auto;">
                <label>&nbsp;</label>
                <a href="utilisateurs.php" class="btn btn-secondaire">&#10005; R&eacute;initialiser</a>
            </div>
        <?php endif; ?>
    </form>

    <!-- Tableau -->
    <?php if (empty($utilisateurs)): ?>
        <div class="boite centrer" style="padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">&#128100;</div>
            <h3>Aucun utilisateur trouv&eacute;</h3>
            <p style="color:var(--texte-doux);">Essayez de modifier vos filtres.</p>
        </div>
    <?php else: ?>
        <div class="tableau-conteneur">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Utilisateur</th>
                        <th>R&ocirc;le</th>
                        <th style="text-align:center;">R&eacute;servations</th>
                        <th style="text-align:center;">Itin&eacute;raires</th>
                        <th>Inscrit le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $u):
                        $color  = $roleColors[$u['role']] ?? '#999';
                        $estMoi = (int)$u['id'] === (int)$_SESSION['utilisateur_id'];
                    ?>
                        <tr <?= $estMoi ? 'style="background:#F0F7FF;"' : '' ?>>
                            <td style="color:var(--texte-doux); font-size:0.82rem;"><?= $u['id'] ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.6rem;">
                                    <!-- Avatar initiale -->
                                    <div style="width:36px; height:36px; border-radius:50%;
                                                background:<?= $color ?>; color:white;
                                                display:flex; align-items:center; justify-content:center;
                                                font-weight:700; font-size:0.88rem; flex-shrink:0;">
                                        <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;">
                                            <?= securiser($u['nom']) ?>
                                            <?php if ($estMoi): ?>
                                                <span style="font-size:0.72rem; color:var(--bleu);
                                                             background:#E8F4FD; padding:0.1rem 0.45rem;
                                                             border-radius:4px; margin-left:0.3rem;">Vous</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.78rem; color:var(--texte-doux);">
                                            <?= securiser($u['email']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <!-- Formulaire changement de rôle -->
                                <form action="utilisateurs.php" method="POST"
                                      style="display:flex; gap:0.35rem; align-items:center;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="champ"
                                            style="padding:0.3rem 0.6rem; font-size:0.82rem; width:auto;"
                                            <?= $estMoi ? 'disabled' : '' ?>>
                                        <option value="admin"    <?= $u['role'] === 'admin'    ? 'selected' : '' ?>>admin</option>
                                        <option value="user"     <?= $u['role'] === 'user'     ? 'selected' : '' ?>>user</option>
                                        <option value="etudiant" <?= $u['role'] === 'etudiant' ? 'selected' : '' ?>>étudiant</option>
                                    </select>
                                    <?php if (!$estMoi): ?>
                                        <button type="submit" name="changer_role"
                                                class="btn btn-secondaire btn-petit"
                                                title="Enregistrer le rôle">&#10003;</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                            <td style="text-align:center;"><?= (int)$u['nb_reservations'] ?></td>
                            <td style="text-align:center;"><?= (int)$u['nb_itineraires'] ?></td>
                            <td style="font-size:0.82rem;">
                                <?= date('d/m/Y', strtotime($u['date_inscription'])) ?>
                            </td>
                            <td>
                                <?php if (!$estMoi): ?>
                                    <form action="utilisateurs.php" method="POST"
                                          onsubmit="return confirm('Supprimer le compte de <?= securiser($u['nom']) ?> ?\nCette action est irréversible.')">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="supprimer"
                                                class="btn btn-danger btn-petit">
                                            &#10005; Supprimer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:0.78rem; color:var(--texte-doux);">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="font-size:0.82rem; color:var(--texte-doux);">
                            Total : <strong><?= $totalUtilisateurs ?></strong> utilisateur<?= $totalUtilisateurs > 1 ? 's' : '' ?> inscrits
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
