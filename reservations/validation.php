<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

// Panier vide → retour au panier
$panier = getPanier();
if (empty($panier)) {
    rediriger('panier.php');
}

$total  = totalPanier();
$erreur = '';

// Données pré-remplies depuis la session
$champsDefaut = [
    'nom'     => $_SESSION['nom']   ?? '',
    'email'   => $_SESSION['email'] ?? '',
    'tel'     => '',
    'adresse' => '',
    'ville'   => '',
    'pays'    => 'France',
    'demandes'=> '',
];
$champs = $champsDefaut;

// ----------------------------------------------------------------
//  Validation et passage à la confirmation
// ----------------------------------------------------------------
if (isset($_POST['confirmer'])) {
    $champs['nom']           = trim($_POST['nom']           ?? '');
    $champs['email']         = trim($_POST['email']         ?? '');
    $champs['tel']           = trim($_POST['tel']           ?? '');
    $champs['adresse']       = trim($_POST['adresse']       ?? '');
    $champs['ville']         = trim($_POST['ville']         ?? '');
    $champs['pays']          = trim($_POST['pays']          ?? '');
    $champs['demandes']      = trim($_POST['demandes']      ?? '');
    $rawItinId               = trim($_POST['itineraire_id'] ?? '');
    $champs['itineraire_id'] = ($rawItinId !== '' && ctype_digit($rawItinId)) ? (int)$rawItinId : null;

    if (empty($champs['nom']) || empty($champs['email']) || empty($champs['tel'])
        || empty($champs['adresse']) || empty($champs['ville'])) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";

    } elseif (!filter_var($champs['email'], FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";

    } elseif (!preg_match('/^[0-9+\s\-().]{7,20}$/', $champs['tel'])) {
        $erreur = "Le numéro de téléphone n'est pas valide.";

    } else {
        // Tout est valide → stocker en session et passer à la confirmation
        $_SESSION['commande'] = $champs;
        rediriger('confirmation.php');
    }
}

// Récupérer les itinéraires de l'utilisateur pour le lien optionnel
$stmtItins = $pdo->prepare(
    "SELECT id, titre FROM ITINERAIRES
     WHERE utilisateur_id = :uid AND statut IN ('brouillon','confirme')
     ORDER BY date_debut DESC"
);
$stmtItins->execute([':uid' => $_SESSION['utilisateur_id']]);
$itineraires = $stmtItins->fetchAll();

$icones = ['transport' => '✈', 'hebergement' => '🏨', 'activite' => '🎭'];

$titrePage = 'Validation de commande';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>✅ Validation de votre commande</h1>
        <p>Vérifiez vos informations avant de confirmer</p>
    </div>
</div>

<!-- Indicateur d'étapes -->
<div style="background:white; border-bottom:1px solid var(--bordure); padding:1rem 0;">
    <div class="container">
        <div style="display:flex; align-items:center; justify-content:center; gap:0; max-width:500px; margin:0 auto;">
            <?php
            $etapes = ['🛒 Panier', '📋 Validation', '🎉 Confirmation'];
            foreach ($etapes as $i => $etape):
                $actif = $i === 1;
                $passe = $i === 0;
            ?>
                <div style="display:flex; align-items:center; gap:0;">
                    <div style="display:flex; align-items:center; gap:0.4rem; padding:0.4rem 0.85rem; border-radius:50px;
                                background:<?= $actif ? 'var(--bleu)' : ($passe ? '#E6F7EC' : 'var(--fond)') ?>;
                                color:<?= $actif ? 'white' : ($passe ? 'var(--succes)' : 'var(--texte-doux)') ?>;
                                font-size:0.85rem; font-weight:700;">
                        <?= $etape ?>
                    </div>
                    <?php if ($i < count($etapes) - 1): ?>
                        <div style="width:2rem; height:2px; background:var(--bordure);"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container section">
    <div style="display:grid; grid-template-columns:3fr 2fr; gap:2rem; align-items:start;">

        <!-- ============================================================
             FORMULAIRE COORDONNÉES
        ============================================================ -->
        <div>
            <?php if ($erreur): ?>
                <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
            <?php endif; ?>

            <form action="validation.php" method="POST" novalidate>

                <!-- Informations personnelles -->
                <div class="boite mb-3">
                    <h2 style="font-size:1.1rem; margin-bottom:1.25rem;">👤 Vos coordonnées</h2>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="champ-groupe">
                            <label for="nom">Nom complet *</label>
                            <input type="text" id="nom" name="nom" class="champ"
                                   value="<?= securiser($champs['nom']) ?>" required>
                        </div>
                        <div class="champ-groupe">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="champ"
                                   value="<?= securiser($champs['email']) ?>" required>
                        </div>
                    </div>

                    <div class="champ-groupe">
                        <label for="tel">Téléphone *</label>
                        <input type="tel" id="tel" name="tel" class="champ"
                               value="<?= securiser($champs['tel']) ?>"
                               placeholder="06 12 34 56 78" required>
                    </div>

                    <div class="champ-groupe">
                        <label for="adresse">Adresse *</label>
                        <input type="text" id="adresse" name="adresse" class="champ"
                               value="<?= securiser($champs['adresse']) ?>"
                               placeholder="123 rue de la Paix" required>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="champ-groupe">
                            <label for="ville">Ville *</label>
                            <input type="text" id="ville" name="ville" class="champ"
                                   value="<?= securiser($champs['ville']) ?>" required>
                        </div>
                        <div class="champ-groupe">
                            <label for="pays">Pays</label>
                            <input type="text" id="pays" name="pays" class="champ"
                                   value="<?= securiser($champs['pays']) ?>">
                        </div>
                    </div>

                    <div class="champ-groupe" style="margin-bottom:0;">
                        <label for="demandes">Demandes spéciales <span style="font-weight:400; color:var(--texte-doux);">(optionnel)</span></label>
                        <textarea id="demandes" name="demandes" class="champ"
                                  rows="3"
                                  placeholder="Régime alimentaire, accessibilité, préférences de chambre…"
                                  style="resize:vertical;"><?= securiser($champs['demandes']) ?></textarea>
                    </div>
                </div>

                <!-- Lien itinéraire optionnel -->
                <?php if (!empty($itineraires)): ?>
                    <div class="boite mb-3">
                        <h2 style="font-size:1.1rem; margin-bottom:0.75rem;">🗺 Lier à un itinéraire <span style="font-size:0.8rem; font-weight:400; color:var(--texte-doux);">(optionnel)</span></h2>
                        <select name="itineraire_id" class="champ">
                            <option value="">— Ne pas lier à un itinéraire —</option>
                            <?php foreach ($itineraires as $it): ?>
                                <option value="<?= $it['id'] ?>">
                                    <?= securiser($it['titre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="itineraire_id" value="">
                <?php endif; ?>

                <!-- Bouton confirmation -->
                <button type="submit" name="confirmer" class="btn btn-orange btn-bloc btn-grand">
                    🔒 Confirmer et payer <?= formatPrix($total) ?>
                </button>
                <p style="font-size:0.78rem; color:var(--texte-doux); text-align:center; margin-top:0.5rem;">
                    Simulation de paiement — aucune transaction réelle
                </p>

            </form>
        </div>

        <!-- ============================================================
             RÉCAPITULATIF COMMANDE
        ============================================================ -->
        <div style="position:sticky; top:80px;">
            <div class="boite">
                <h3 style="font-size:1rem; margin-bottom:1.1rem;">🧾 Récapitulatif</h3>

                <?php foreach ($panier as $item): ?>
                    <div style="display:flex; align-items:center; gap:0.6rem; padding:0.6rem 0; border-bottom:1px solid var(--bordure); font-size:0.88rem;">
                        <span><?= $icones[$item['type']] ?? '📦' ?></span>
                        <span style="flex:1; color:var(--texte-doux);"><?= securiser($item['nom']) ?></span>
                        <strong><?= formatPrix((float)$item['prix']) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem; padding-top:0.75rem; border-top:2px solid var(--bleu);">
                    <span style="font-family:'Poppins',sans-serif; font-weight:700;">Total</span>
                    <span style="font-family:'Poppins',sans-serif; font-size:1.4rem; font-weight:800; color:var(--bleu);">
                        <?= formatPrix($total) ?>
                    </span>
                </div>
            </div>

            <a href="panier.php" class="btn btn-secondaire btn-bloc mt-2">
                ← Modifier le panier
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
