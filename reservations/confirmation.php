<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

// Sécurité : on arrive ici seulement si les données de commande sont en session
if (empty($_SESSION['commande']) || empty(getPanier())) {
    rediriger('panier.php');
}

$uid      = $_SESSION['utilisateur_id'];
$commande = $_SESSION['commande'];
$panier   = getPanier();
$total    = totalPanier();

// ----------------------------------------------------------------
//  Traitement : enregistrer les réservations en base
// ----------------------------------------------------------------
$itinId    = !empty($_SESSION['commande']['itineraire_id'])
             ? (int) $_SESSION['commande']['itineraire_id']
             : null;

// Référence unique de commande : VV + 8 caractères alphanumériques
$reference = 'VV-' . strtoupper(substr(md5(uniqid($uid . time(), true)), 0, 8));

$stmtInsert = $pdo->prepare(
    "INSERT INTO RESERVATIONS (type, reference_id, statut, prix_total, utilisateur_id, itineraire_id)
     VALUES (:type, :ref, 'confirme', :prix, :uid, :itin)"
);

foreach ($panier as $item) {
    $stmtInsert->execute([
        ':type' => $item['type'],
        ':ref'  => $item['reference_id'],
        ':prix' => $item['prix'],
        ':uid'  => $uid,
        ':itin' => $itinId,
    ]);
}

// Notification de confirmation
$nbArticles = count($panier);
creerNotification(
    $pdo,
    $uid,
    "Réservation confirmée — Réf. $reference",
    "Votre commande de $nbArticles article(s) pour un total de " . formatPrix($total) . " a été confirmée. Référence : $reference.",
    'confirmation'
);

// Générer un faux numéro de carte bancaire pour l'affichage
$fausseCarteVisa = '4532 ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);
$dernierChiffres = substr(str_replace(' ', '', $fausseCarteVisa), -4);

// Vider le panier et les données de commande de la session
viderPanier();
unset($_SESSION['commande']);

// ----------------------------------------------------------------
//  Affichage
// ----------------------------------------------------------------
$icones = ['transport' => '✈', 'hebergement' => '🏨', 'activite' => '🎭'];
$labels = ['transport' => 'Transport', 'hebergement' => 'Hébergement', 'activite' => 'Activité'];

$titrePage = 'Réservation confirmée';
require_once '../includes/header.php';
?>

<!-- Indicateur d'étapes -->
<div style="background:white; border-bottom:1px solid var(--bordure); padding:1rem 0;">
    <div class="container">
        <div style="display:flex; align-items:center; justify-content:center; gap:0; max-width:500px; margin:0 auto;">
            <?php
            $etapes = ['🛒 Panier', '📋 Validation', '🎉 Confirmation'];
            foreach ($etapes as $i => $etape):
                $actif = $i === 2;
                $passe = $i < 2;
            ?>
                <div style="display:flex; align-items:center; gap:0;">
                    <div style="display:flex; align-items:center; gap:0.4rem; padding:0.4rem 0.85rem; border-radius:50px;
                                background:<?= $actif ? 'var(--succes)' : '#E6F7EC' ?>;
                                color:<?= $actif ? 'white' : 'var(--succes)' ?>;
                                font-size:0.85rem; font-weight:700;">
                        <?= $etape ?>
                    </div>
                    <?php if ($i < count($etapes) - 1): ?>
                        <div style="width:2rem; height:2px; background:#b7e4c7;"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container section" style="max-width:760px; margin-left:auto; margin-right:auto;">

    <!-- Bannière succès -->
    <div style="background:linear-gradient(135deg,#2D9E4E,#1a7a38); border-radius:var(--rayon); padding:2.5rem; text-align:center; color:white; margin-bottom:2rem;">
        <div style="font-size:4rem; margin-bottom:0.75rem;">🎉</div>
        <h1 style="color:white; font-size:1.8rem; margin-bottom:0.5rem;">Réservation confirmée !</h1>
        <p style="color:rgba(255,255,255,0.85); margin-bottom:1.25rem;">
            Merci <?= securiser($commande['nom']) ?>, votre voyage est planifié.
        </p>
        <div style="background:rgba(255,255,255,0.15); border-radius:var(--rayon-petit); display:inline-block; padding:0.6rem 1.5rem;">
            <span style="font-size:0.82rem; opacity:0.85;">Référence de commande</span><br>
            <span style="font-family:'Poppins',sans-serif; font-size:1.3rem; font-weight:800; letter-spacing:0.08em;">
                <?= securiser($reference) ?>
            </span>
        </div>
    </div>

    <!-- ============================================================
         FAUSSE CARTE BANCAIRE
    ============================================================ -->
    <div class="boite mb-3">
        <h2 style="font-size:1.1rem; margin-bottom:1.25rem;">💳 Paiement traité</h2>

        <!-- Visual carte -->
        <div style="background:linear-gradient(135deg, var(--bleu), #023E8A); border-radius:16px; padding:1.5rem 1.75rem; color:white; max-width:340px; font-family:'Poppins',sans-serif; margin-bottom:1.25rem;">
            <div style="font-size:0.75rem; letter-spacing:0.12em; opacity:0.7; margin-bottom:1rem;">VISA</div>
            <div style="font-size:1.15rem; letter-spacing:0.18em; margin-bottom:1rem;">
                **** **** **** <?= securiser($dernierChiffres) ?>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:0.78rem; opacity:0.8;">
                <span><?= securiser(strtoupper($commande['nom'])) ?></span>
                <span>EXP 12/28</span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; font-size:0.88rem;">
            <div>
                <span style="color:var(--texte-doux);">Montant débité</span><br>
                <strong style="color:var(--bleu); font-size:1.1rem;"><?= formatPrix($total) ?></strong>
            </div>
            <div>
                <span style="color:var(--texte-doux);">Statut</span><br>
                <span class="statut statut-confirme">✓ Approuvé</span>
            </div>
            <div>
                <span style="color:var(--texte-doux);">Date</span><br>
                <strong><?= date('d/m/Y à H:i') ?></strong>
            </div>
            <div>
                <span style="color:var(--texte-doux);">Référence</span><br>
                <strong><?= securiser($reference) ?></strong>
            </div>
        </div>

        <div style="background:#FFF8E1; border:1px solid #FFE082; border-radius:var(--rayon-petit); padding:0.75rem 1rem; margin-top:1rem; font-size:0.82rem; color:#856404;">
            ⚠ Simulation pédagogique — Aucune transaction bancaire réelle n'a été effectuée.
        </div>
    </div>

    <!-- ============================================================
         RÉCAPITULATIF DES ARTICLES RÉSERVÉS
    ============================================================ -->
    <div class="boite mb-3">
        <h2 style="font-size:1.1rem; margin-bottom:1.1rem;">🧾 Articles réservés (<?= $nbArticles ?>)</h2>

        <?php foreach ($panier as $item): ?>
            <div style="display:flex; align-items:center; gap:1rem; padding:0.75rem 0; border-bottom:1px solid var(--bordure);">
                <span style="font-size:1.4rem;"><?= $icones[$item['type']] ?? '📦' ?></span>
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:0.92rem;"><?= securiser($item['nom']) ?></div>
                    <div style="font-size:0.8rem; color:var(--texte-doux);">
                        <?= $labels[$item['type']] ?? $item['type'] ?>
                        &nbsp;·&nbsp;
                        <span class="statut statut-confirme" style="font-size:0.72rem;">Confirmé</span>
                    </div>
                </div>
                <strong style="color:var(--bleu);"><?= formatPrix((float)$item['prix']) ?></strong>
            </div>
        <?php endforeach; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem; padding-top:0.75rem; border-top:2px solid var(--bleu);">
            <span style="font-family:'Poppins',sans-serif; font-weight:700; font-size:1rem;">Total payé</span>
            <span style="font-family:'Poppins',sans-serif; font-size:1.4rem; font-weight:800; color:var(--bleu);">
                <?= formatPrix($total) ?>
            </span>
        </div>
    </div>

    <!-- ============================================================
         COORDONNÉES DE LIVRAISON
    ============================================================ -->
    <div class="boite mb-3">
        <h2 style="font-size:1.1rem; margin-bottom:1rem;">📬 Confirmation envoyée à</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; font-size:0.9rem;">
            <div><span style="color:var(--texte-doux);">Nom</span><br><strong><?= securiser($commande['nom']) ?></strong></div>
            <div><span style="color:var(--texte-doux);">Email</span><br><strong><?= securiser($commande['email']) ?></strong></div>
            <div><span style="color:var(--texte-doux);">Téléphone</span><br><strong><?= securiser($commande['tel']) ?></strong></div>
            <div><span style="color:var(--texte-doux);">Adresse</span><br><strong><?= securiser($commande['adresse']) ?>, <?= securiser($commande['ville']) ?></strong></div>
        </div>
        <?php if (!empty($commande['demandes'])): ?>
            <div style="margin-top:0.75rem; font-size:0.88rem;">
                <span style="color:var(--texte-doux);">Demandes spéciales :</span><br>
                <em><?= securiser($commande['demandes']) ?></em>
            </div>
        <?php endif; ?>
    </div>

    <!-- Actions suivantes -->
    <div style="display:flex; gap:1rem; flex-wrap:wrap; justify-content:center;">
        <a href="<?= URL_BASE ?>/itineraires/mon_itineraire.php" class="btn btn-primaire btn-grand">
            🗺 Voir mes itinéraires
        </a>
        <a href="<?= URL_BASE ?>/notifications/mes_notifications.php" class="btn btn-secondaire btn-grand">
            🔔 Mes notifications
        </a>
        <a href="<?= URL_BASE ?>/destinations/liste.php" class="btn btn-secondaire btn-grand">
            ✈ Continuer à explorer
        </a>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
