<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estAdmin()) {
    rediriger('../index.php');
}

// ----------------------------------------------------------------
//  Statistiques globales
// ----------------------------------------------------------------
$stats = [
    'utilisateurs'   => (int)$pdo->query("SELECT COUNT(*) FROM UTILISATEURS")->fetchColumn(),
    'destinations'   => (int)$pdo->query("SELECT COUNT(*) FROM DESTINATIONS")->fetchColumn(),
    'hebergements'   => (int)$pdo->query("SELECT COUNT(*) FROM HEBERGEMENTS")->fetchColumn(),
    'activites'      => (int)$pdo->query("SELECT COUNT(*) FROM ACTIVITES")->fetchColumn(),
    'reservations'   => (int)$pdo->query("SELECT COUNT(*) FROM RESERVATIONS")->fetchColumn(),
    'res_confirme'   => (int)$pdo->query("SELECT COUNT(*) FROM RESERVATIONS WHERE statut = 'confirme'")->fetchColumn(),
    'res_attente'    => (int)$pdo->query("SELECT COUNT(*) FROM RESERVATIONS WHERE statut = 'en_attente'")->fetchColumn(),
    'itineraires'    => (int)$pdo->query("SELECT COUNT(*) FROM ITINERAIRES")->fetchColumn(),
    'notifications'  => (int)$pdo->query("SELECT COUNT(*) FROM NOTIFICATIONS WHERE est_lu = 0")->fetchColumn(),
    'ca_total'       => (float)$pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM RESERVATIONS WHERE statut = 'confirme'")->fetchColumn(),
];

// Répartition des rôles
$roles = $pdo->query("SELECT role, COUNT(*) AS nb FROM UTILISATEURS GROUP BY role")->fetchAll();

// 5 dernières réservations
$dernieresResas = $pdo->query(
    "SELECT r.*, u.nom AS user_nom, u.email AS user_email
     FROM RESERVATIONS r
     JOIN UTILISATEURS u ON u.id = r.utilisateur_id
     ORDER BY r.date_reservation DESC
     LIMIT 5"
)->fetchAll();

// 5 derniers utilisateurs inscrits
$derniersUsers = $pdo->query(
    "SELECT * FROM UTILISATEURS ORDER BY date_inscription DESC LIMIT 5"
)->fetchAll();

// Destinations les plus réservées
$topDestinations = $pdo->query(
    "SELECT d.nom, d.pays, COUNT(r.id) AS nb_reservations
     FROM RESERVATIONS r
     JOIN TRANSPORTS t    ON r.type = 'transport'   AND r.reference_id = t.id
     JOIN DESTINATIONS d  ON t.destination_id = d.id
     GROUP BY d.id, d.nom, d.pays
     ORDER BY nb_reservations DESC
     LIMIT 5"
)->fetchAll();

$titrePage = 'Administration — Tableau de bord';
require_once '../includes/header.php';
?>

<!-- Barre admin -->
<div style="background:#1A1A2E; color:white; padding:0.6rem 0; font-size:0.82rem;">
    <div class="container flex-entre">
        <span>⚙ Espace Administration</span>
        <div style="display:flex; gap:1rem;">
            <a href="destinations.php"  style="color:rgba(255,255,255,0.7);">Destinations</a>
            <a href="utilisateurs.php"  style="color:rgba(255,255,255,0.7);">Utilisateurs</a>
            <a href="reservations.php"  style="color:rgba(255,255,255,0.7);">Réservations</a>
        </div>
    </div>
</div>

<div class="page-entete">
    <div class="container">
        <h1>⚙ Tableau de bord</h1>
        <p>Bienvenue <?= securiser($_SESSION['nom']) ?> — Aperçu global de VoyageVista</p>
    </div>
</div>

<div class="container section">

    <!-- ============================================================
         STATS PRINCIPALES
    ============================================================ -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1.25rem; margin-bottom:2.5rem;">
        <?php
        $carteStats = [
            ['icone' => '👥', 'valeur' => $stats['utilisateurs'],  'label' => 'Utilisateurs',  'couleur' => '#0077B6', 'lien' => 'utilisateurs.php'],
            ['icone' => '🌍', 'valeur' => $stats['destinations'],  'label' => 'Destinations',  'couleur' => '#4338CA', 'lien' => 'destinations.php'],
            ['icone' => '🎫', 'valeur' => $stats['reservations'],  'label' => 'Réservations',  'couleur' => '#2D9E4E', 'lien' => 'reservations.php'],
            ['icone' => '💶', 'valeur' => formatPrix($stats['ca_total']), 'label' => 'CA confirmé', 'couleur' => '#F4A261', 'lien' => 'reservations.php'],
        ];
        foreach ($carteStats as $cs):
        ?>
            <a href="<?= $cs['lien'] ?>" style="text-decoration:none;">
                <div style="background:white; border-radius:var(--rayon); padding:1.5rem; box-shadow:var(--ombre); border-top:4px solid <?= $cs['couleur'] ?>; transition:transform 0.2s;"
                     onmouseover="this.style.transform='translateY(-3px)'"
                     onmouseout="this.style.transform=''">
                    <div style="font-size:1.8rem; margin-bottom:0.5rem;"><?= $cs['icone'] ?></div>
                    <div style="font-family:'Poppins',sans-serif; font-size:1.6rem; font-weight:800; color:<?= $cs['couleur'] ?>;">
                        <?= $cs['valeur'] ?>
                    </div>
                    <div style="font-size:0.82rem; color:var(--texte-doux); font-weight:600; text-transform:uppercase; letter-spacing:0.05em; margin-top:0.25rem;">
                        <?= $cs['label'] ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Stats secondaires -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:2.5rem;">
        <?php
        $statsSecondaires = [
            ['icone' => '🏨', 'valeur' => $stats['hebergements'],  'label' => 'Hébergements'],
            ['icone' => '🎭', 'valeur' => $stats['activites'],     'label' => 'Activités'],
            ['icone' => '✅', 'valeur' => $stats['res_confirme'],  'label' => 'Rés. confirmées'],
            ['icone' => '🔔', 'valeur' => $stats['notifications'], 'label' => 'Notifs non lues'],
        ];
        foreach ($statsSecondaires as $ss):
        ?>
            <div style="background:white; border-radius:var(--rayon); padding:1rem 1.25rem; box-shadow:var(--ombre); display:flex; align-items:center; gap:0.85rem;">
                <span style="font-size:1.5rem;"><?= $ss['icone'] ?></span>
                <div>
                    <div style="font-family:'Poppins',sans-serif; font-size:1.2rem; font-weight:700;"><?= $ss['valeur'] ?></div>
                    <div style="font-size:0.78rem; color:var(--texte-doux);"><?= $ss['label'] ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:2rem;">

        <!-- Dernières réservations -->
        <div>
            <div class="flex-entre mb-2">
                <h2 style="font-size:1.1rem;">🎫 Dernières réservations</h2>
                <a href="reservations.php" class="btn btn-secondaire btn-petit">Tout voir</a>
            </div>
            <div class="tableau-conteneur">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dernieresResas)): ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--texte-doux);">Aucune réservation</td></tr>
                        <?php else: ?>
                            <?php foreach ($dernieresResas as $r):
                                $statutClasses = ['confirme' => 'statut-confirme', 'en_attente' => 'statut-attente', 'annule' => 'statut-annule', 'rembourse' => 'statut-brouillon'];
                                $statutLabels  = ['confirme' => 'Confirmé', 'en_attente' => 'En attente', 'annule' => 'Annulé', 'rembourse' => 'Remboursé'];
                                $typeIcones    = ['transport' => '✈', 'hebergement' => '🏨', 'activite' => '🎭'];
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; font-size:0.88rem;"><?= securiser($r['user_nom']) ?></div>
                                        <div style="font-size:0.78rem; color:var(--texte-doux);"><?= securiser($r['user_email']) ?></div>
                                    </td>
                                    <td><?= ($typeIcones[$r['type']] ?? '') . ' ' . ucfirst(securiser($r['type'])) ?></td>
                                    <td style="font-weight:700; color:var(--bleu);"><?= formatPrix((float)$r['prix_total']) ?></td>
                                    <td><span class="statut <?= $statutClasses[$r['statut']] ?? '' ?>"><?= $statutLabels[$r['statut']] ?? $r['statut'] ?></span></td>
                                    <td style="font-size:0.82rem;"><?= date('d/m/Y', strtotime($r['date_reservation'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Colonne droite -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">

            <!-- Derniers inscrits -->
            <div>
                <div class="flex-entre mb-2">
                    <h2 style="font-size:1.1rem;">👥 Derniers inscrits</h2>
                    <a href="utilisateurs.php" class="btn btn-secondaire btn-petit">Tout voir</a>
                </div>
                <div class="boite" style="padding:0.75rem;">
                    <?php foreach ($derniersUsers as $u):
                        $roleColors = ['admin' => '#D62828', 'user' => '#0077B6', 'etudiant' => '#2D9E4E'];
                    ?>
                        <div style="display:flex; align-items:center; gap:0.6rem; padding:0.5rem 0; border-bottom:1px solid var(--bordure); font-size:0.85rem;">
                            <div style="width:32px; height:32px; border-radius:50%; background:var(--bleu); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.82rem; flex-shrink:0;">
                                <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= securiser($u['nom']) ?></div>
                                <div style="font-size:0.75rem; color:var(--texte-doux);"><?= date('d/m/Y', strtotime($u['date_inscription'])) ?></div>
                            </div>
                            <span style="font-size:0.72rem; font-weight:700; padding:0.15rem 0.5rem; border-radius:50px; background:<?= $roleColors[$u['role']] ?? '#999' ?>20; color:<?= $roleColors[$u['role']] ?? '#999' ?>;">
                                <?= $u['role'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Répartition rôles -->
            <div class="boite">
                <h3 style="font-size:0.95rem; margin-bottom:0.85rem;">Répartition des rôles</h3>
                <?php foreach ($roles as $r):
                    $pct = $stats['utilisateurs'] > 0 ? round($r['nb'] / $stats['utilisateurs'] * 100) : 0;
                    $colors = ['admin' => '#D62828', 'user' => '#0077B6', 'etudiant' => '#2D9E4E'];
                    $color  = $colors[$r['role']] ?? '#999';
                ?>
                    <div style="margin-bottom:0.6rem;">
                        <div style="display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:0.2rem;">
                            <span style="font-weight:700;"><?= ucfirst($r['role']) ?></span>
                            <span style="color:var(--texte-doux);"><?= $r['nb'] ?> (<?= $pct ?>%)</span>
                        </div>
                        <div style="background:var(--bordure); border-radius:4px; height:6px;">
                            <div style="width:<?= $pct ?>%; height:100%; background:<?= $color ?>; border-radius:4px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- Actions rapides -->
    <div style="margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--bordure);">
        <h2 style="font-size:1.1rem; margin-bottom:1rem;">Actions rapides</h2>
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <a href="destinations.php?action=ajouter" class="btn btn-primaire">+ Nouvelle destination</a>
            <a href="destinations.php" class="btn btn-secondaire">🌍 Gérer les destinations</a>
            <a href="utilisateurs.php" class="btn btn-secondaire">👥 Gérer les utilisateurs</a>
            <a href="reservations.php" class="btn btn-secondaire">🎫 Voir les réservations</a>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
