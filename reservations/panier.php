<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

// ----------------------------------------------------------------
//  Supprimer un article du panier
// ----------------------------------------------------------------
if (isset($_POST['supprimer']) && isset($_POST['cle'])) {
    supprimerDuPanier($_POST['cle']);
    rediriger('panier.php');
}

// ----------------------------------------------------------------
//  Vider tout le panier
// ----------------------------------------------------------------
if (isset($_POST['vider_panier'])) {
    viderPanier();
    rediriger('panier.php');
}

$panier = getPanier();
$total  = totalPanier();

// Icônes par type d'article
$icones = ['transport' => '✈', 'hebergement' => '🏨', 'activite' => '🎭'];

$titrePage = 'Mon panier';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🛒 Mon panier</h1>
        <p>
            <?= count($panier) ?> article<?= count($panier) > 1 ? 's' : '' ?>
            sélectionné<?= count($panier) > 1 ? 's' : '' ?>
        </p>
    </div>
</div>

<div class="container section">
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:2rem; align-items:start;">

        <!-- ============================================================
             LISTE DES ARTICLES
        ============================================================ -->
        <div>
            <?php if (empty($panier)): ?>

                <div class="boite centrer" style="padding:4rem 2rem;">
                    <div style="font-size:4rem; margin-bottom:1rem;">🛒</div>
                    <h2 style="margin-bottom:0.5rem;">Votre panier est vide</h2>
                    <p style="color:var(--texte-doux); margin-bottom:2rem;">
                        Explorez nos destinations et ajoutez des transports, hébergements ou activités.
                    </p>
                    <a href="<?= URL_BASE ?>/destinations/liste.php" class="btn btn-primaire btn-grand">
                        Découvrir les destinations
                    </a>
                </div>

            <?php else: ?>

                <!-- Bouton vider tout -->
                <div class="flex-entre mb-2">
                    <h2 style="font-size:1.1rem;">Articles sélectionnés</h2>
                    <form action="panier.php" method="POST"
                          onsubmit="return confirm('Vider tout le panier ?')">
                        <button type="submit" name="vider_panier" class="btn btn-danger btn-petit">
                            ✕ Tout vider
                        </button>
                    </form>
                </div>

                <?php foreach ($panier as $cle => $item): ?>
                    <div class="panier-item">
                        <div style="font-size:1.8rem; flex-shrink:0;">
                            <?= $icones[$item['type']] ?? '📦' ?>
                        </div>

                        <div class="panier-item-info">
                            <div class="panier-item-nom"><?= securiser($item['nom']) ?></div>
                            <div class="panier-item-type">
                                <?php
                                $labels = ['transport' => 'Transport', 'hebergement' => 'Hébergement', 'activite' => 'Activité'];
                                echo $labels[$item['type']] ?? $item['type'];
                                ?>
                            </div>
                            <?php if ($item['type'] === 'hebergement' && !empty($item['date_arrivee'])): ?>
                                <div style="font-size:0.78rem; color:var(--texte-doux); margin-top:0.2rem;">
                                    📅 <?= securiser(formatDate($item['date_arrivee'])) ?> → <?= securiser(formatDate($item['date_depart'])) ?>
                                    — <?= (int)$item['nb_nuits'] ?> nuit<?= (int)$item['nb_nuits'] > 1 ? 's' : '' ?>
                                </div>
                            <?php elseif ($item['type'] === 'activite' && !empty($item['date_activite'])): ?>
                                <div style="font-size:0.78rem; color:var(--texte-doux); margin-top:0.2rem;">
                                    📅 <?= securiser(formatDate($item['date_activite'])) ?>
                                    — <?= (int)$item['nb_personnes'] ?> personne<?= (int)$item['nb_personnes'] > 1 ? 's' : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="panier-item-prix">
                            <?= formatPrix((float)$item['prix']) ?>
                        </div>

                        <form action="panier.php" method="POST">
                            <input type="hidden" name="cle" value="<?= securiser($cle) ?>">
                            <button type="submit" name="supprimer"
                                    class="btn btn-danger btn-petit"
                                    title="Retirer du panier">
                                ✕
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>

        <!-- ============================================================
             RÉCAPITULATIF & ACTIONS
        ============================================================ -->
        <div style="position:sticky; top:80px;">
            <div class="boite">
                <h3 style="font-size:1rem; margin-bottom:1.25rem;">Récapitulatif</h3>

                <!-- Sous-totaux par type -->
                <?php
                $sousTotaux = ['transport' => 0, 'hebergement' => 0, 'activite' => 0];
                foreach ($panier as $item) {
                    if (isset($sousTotaux[$item['type']])) {
                        $sousTotaux[$item['type']] += $item['prix'];
                    }
                }
                $labels = ['transport' => '✈ Transports', 'hebergement' => '🏨 Hébergements', 'activite' => '🎭 Activités'];
                foreach ($sousTotaux as $type => $montant):
                    if ($montant <= 0) continue;
                ?>
                    <div style="display:flex; justify-content:space-between; font-size:0.88rem; color:var(--texte-doux); margin-bottom:0.5rem;">
                        <span><?= $labels[$type] ?></span>
                        <span><?= formatPrix($montant) ?></span>
                    </div>
                <?php endforeach; ?>

                <div style="border-top:2px solid var(--bordure); margin:0.75rem 0; padding-top:0.75rem;">
                    <div class="panier-total" style="border-radius:var(--rayon-petit);">
                        <span class="panier-total-label">Total</span>
                        <span class="panier-total-montant"><?= formatPrix($total) ?></span>
                    </div>
                </div>

                <?php if (!empty($panier)): ?>
                    <a href="validation.php" class="btn btn-orange btn-bloc btn-grand" style="margin-top:0.75rem;">
                        Procéder au paiement →
                    </a>
                    <p style="font-size:0.78rem; color:var(--texte-doux); text-align:center; margin-top:0.75rem; margin-bottom:0;">
                        🔒 Paiement 100 % sécurisé · Simulation uniquement
                    </p>
                <?php endif; ?>
            </div>

            <a href="<?= URL_BASE ?>/destinations/liste.php"
               class="btn btn-secondaire btn-bloc mt-2">
                ← Continuer à explorer
            </a>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
