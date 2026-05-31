<?php
session_start();
require_once '../config/connexion.php';
require_once '../config/constantes.php';
require_once '../includes/fonctions.php';

if (!estConnecte()) {
    rediriger('../auth/connexion.php');
}

$uid     = $_SESSION['utilisateur_id'];
$succes  = '';
$erreur  = '';
$resultat = null;

// Pré-remplissage depuis logements.php (lien direct)
$preDestination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$preLogement    = isset($_GET['logement'])    && is_numeric($_GET['logement']) ? (float)$_GET['logement'] : 0;

$champs = [
    'nom_destination' => $preDestination,
    'nb_jours'        => 7,
    'transport'       => 0,
    'logement_nuit'   => $preLogement > 0 ? round($preLogement / 30, 2) : 0,
    'repas_jour'      => 20,
    'activites'       => 0,
];

// ----------------------------------------------------------------
//  Suppression d'une estimation
// ----------------------------------------------------------------
if (isset($_POST['supprimer']) && isset($_POST['estim_id'])) {
    $estimId = (int) $_POST['estim_id'];

    $stmt = $pdo->prepare("SELECT id FROM ESTIMATIONS_BUDGET WHERE id = :id AND utilisateur_id = :uid");
    $stmt->execute([':id' => $estimId, ':uid' => $uid]);

    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM ESTIMATIONS_BUDGET WHERE id = :id")->execute([':id' => $estimId]);
        $succes = "Estimation supprimée.";
    }
    rediriger('budget.php');
}

// ----------------------------------------------------------------
//  Calcul du budget (déclenché par "Calculer" OU "Calculer & sauvegarder")
// ----------------------------------------------------------------
if (isset($_POST['calculer']) || isset($_POST['sauvegarder'])) {
    $champs['nom_destination'] = trim($_POST['nom_destination'] ?? '');
    $champs['nb_jours']        = isset($_POST['nb_jours'])      && ctype_digit($_POST['nb_jours'])        ? (int)$_POST['nb_jours']        : 0;
    $champs['transport']       = isset($_POST['transport'])      && is_numeric($_POST['transport'])        ? (float)$_POST['transport']      : 0;
    $champs['logement_nuit']   = isset($_POST['logement_nuit'])  && is_numeric($_POST['logement_nuit'])    ? (float)$_POST['logement_nuit']  : 0;
    $champs['repas_jour']      = isset($_POST['repas_jour'])     && is_numeric($_POST['repas_jour'])       ? (float)$_POST['repas_jour']     : 0;
    $champs['activites']       = isset($_POST['activites'])      && is_numeric($_POST['activites'])        ? (float)$_POST['activites']      : 0;

    if (empty($champs['nom_destination'])) {
        $erreur = "Veuillez indiquer une destination.";
    } elseif ($champs['nb_jours'] < 1 || $champs['nb_jours'] > 365) {
        $erreur = "Le nombre de jours doit être compris entre 1 et 365.";
    } elseif ($champs['transport'] < 0 || $champs['logement_nuit'] < 0 || $champs['repas_jour'] < 0 || $champs['activites'] < 0) {
        $erreur = "Les montants ne peuvent pas être négatifs.";
    } else {
        // Calcul détaillé
        $coutTransport  = $champs['transport'];
        $coutLogement   = $champs['logement_nuit']  * $champs['nb_jours'];
        $coutRepas      = $champs['repas_jour']      * $champs['nb_jours'];
        $coutActivites  = $champs['activites'];
        $total          = $coutTransport + $coutLogement + $coutRepas + $coutActivites;

        $resultat = [
            'transport'  => $coutTransport,
            'logement'   => $coutLogement,
            'repas'      => $coutRepas,
            'activites'  => $coutActivites,
            'total'      => $total,
            'par_jour'   => $champs['nb_jours'] > 0 ? $total / $champs['nb_jours'] : 0,
        ];

        // Sauvegarder en BDD si l'utilisateur le demande
        if (isset($_POST['sauvegarder'])) {
            $pdo->prepare(
                "INSERT INTO ESTIMATIONS_BUDGET
                    (nom_destination, transport, logement, activites, nb_jours, total_calcule, utilisateur_id)
                 VALUES (:dest, :transport, :logement, :activites, :jours, :total, :uid)"
            )->execute([
                ':dest'      => $champs['nom_destination'],
                ':transport' => $coutTransport,
                ':logement'  => $coutLogement,
                ':activites' => $coutActivites + $coutRepas, // repas groupé dans activites
                ':jours'     => $champs['nb_jours'],
                ':total'     => $total,
                ':uid'       => $uid,
            ]);
            $succes = "Estimation sauvegardée !";
        }
    }
}

// ----------------------------------------------------------------
//  Historique des estimations de l'utilisateur
// ----------------------------------------------------------------
$stmtHisto = $pdo->prepare(
    "SELECT * FROM ESTIMATIONS_BUDGET
     WHERE utilisateur_id = :uid
     ORDER BY id DESC
     LIMIT 10"
);
$stmtHisto->execute([':uid' => $uid]);
$historique = $stmtHisto->fetchAll();

// Données de référence : prix min transport par destination (pour aide à la saisie)
$refPrix = $pdo->query(
    "SELECT d.nom, MIN(t.prix) AS transport_min, MIN(h.prix_par_nuit) AS hebergement_min
     FROM DESTINATIONS d
     LEFT JOIN TRANSPORTS t ON t.destination_id = d.id
     LEFT JOIN HEBERGEMENTS h ON h.destination_id = d.id
     GROUP BY d.id, d.nom
     ORDER BY d.nom"
)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

$titrePage = 'Calculateur de budget';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>💶 Calculateur de budget étudiant</h1>
        <p>Estimez le coût total de votre séjour à l'étranger</p>
    </div>
</div>

<div class="container section">
    <div style="display:grid; grid-template-columns:3fr 2fr; gap:2rem; align-items:start;">

        <!-- ============================================================
             FORMULAIRE
        ============================================================ -->
        <div>

            <?php if ($succes): ?>
                <div class="alerte alerte-succes"><?= securiser($succes) ?></div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
            <?php endif; ?>

            <form action="budget.php" method="POST" novalidate>
                <div class="boite">
                    <h2 style="font-size:1.1rem; margin-bottom:1.5rem;">Votre séjour</h2>

                    <div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
                        <div class="champ-groupe">
                            <label for="nom_destination">Destination *</label>
                            <input type="text" id="nom_destination" name="nom_destination" class="champ"
                                   value="<?= securiser($champs['nom_destination']) ?>"
                                   placeholder="Ex : Barcelone, Prague…"
                                   list="liste-destinations" required>
                            <!-- Aide à la saisie avec datalist -->
                            <datalist id="liste-destinations">
                                <?php foreach ($refPrix as $nom => $infos): ?>
                                    <option value="<?= securiser($nom) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="champ-groupe">
                            <label for="nb_jours">Durée (jours) *</label>
                            <input type="number" id="nb_jours" name="nb_jours" class="champ"
                                   value="<?= (int)$champs['nb_jours'] ?>"
                                   min="1" max="365" required>
                        </div>
                    </div>

                    <!-- Séparateur -->
                    <div style="border-top:1px solid var(--bordure); margin:1rem 0; padding-top:1rem;">
                        <h3 style="font-size:0.9rem; color:var(--texte-doux); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:1rem;">
                            Coûts estimés
                        </h3>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                        <div class="champ-groupe">
                            <label for="transport">✈ Transport aller-retour (€)</label>
                            <input type="number" id="transport" name="transport" class="champ"
                                   value="<?= number_format($champs['transport'], 2, '.', '') ?>"
                                   min="0" step="0.01" placeholder="0.00">
                            <small style="color:var(--texte-doux); font-size:0.78rem;">Total aller-retour</small>
                        </div>

                        <div class="champ-groupe">
                            <label for="logement_nuit">🏨 Logement par nuit (€)</label>
                            <input type="number" id="logement_nuit" name="logement_nuit" class="champ"
                                   value="<?= number_format($champs['logement_nuit'], 2, '.', '') ?>"
                                   min="0" step="0.01" placeholder="0.00">
                            <small style="color:var(--texte-doux); font-size:0.78rem;">Sera multiplié par le nb de jours</small>
                        </div>

                        <div class="champ-groupe">
                            <label for="repas_jour">🍽 Repas par jour (€)</label>
                            <input type="number" id="repas_jour" name="repas_jour" class="champ"
                                   value="<?= number_format($champs['repas_jour'], 2, '.', '') ?>"
                                   min="0" step="0.01" placeholder="20.00">
                            <small style="color:var(--texte-doux); font-size:0.78rem;">Sera multiplié par le nb de jours</small>
                        </div>

                        <div class="champ-groupe">
                            <label for="activites">🎭 Activités & sorties (€)</label>
                            <input type="number" id="activites" name="activites" class="champ"
                                   value="<?= number_format($champs['activites'], 2, '.', '') ?>"
                                   min="0" step="0.01" placeholder="0.00">
                            <small style="color:var(--texte-doux); font-size:0.78rem;">Total pour le séjour</small>
                        </div>

                    </div>

                    <!-- Boutons -->
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:0.5rem;">
                        <button type="submit" name="calculer" class="btn btn-primaire btn-grand">
                            Calculer mon budget
                        </button>
                        <button type="submit" name="sauvegarder" class="btn btn-secondaire"
                                title="Calculer ET sauvegarder cette estimation">
                            💾 Calculer & sauvegarder
                        </button>
                    </div>
                </div>
            </form>

            <!-- ============================================================
                 RÉSULTAT DU CALCUL
            ============================================================ -->
            <?php if ($resultat): ?>
                <div class="boite mt-3" style="border-top:4px solid var(--bleu);">
                    <h2 style="font-size:1.1rem; margin-bottom:1.25rem;">
                        📊 Estimation pour <em><?= securiser($champs['nom_destination']) ?></em>
                        — <?= (int)$champs['nb_jours'] ?> jours
                    </h2>

                    <!-- Barres de proportion -->
                    <?php
                    $postes = [
                        ['label' => '✈ Transport',    'montant' => $resultat['transport'], 'couleur' => '#0077B6'],
                        ['label' => '🏨 Logement',    'montant' => $resultat['logement'],  'couleur' => '#4338CA'],
                        ['label' => '🍽 Repas',       'montant' => $resultat['repas'],     'couleur' => '#16A34A'],
                        ['label' => '🎭 Activités',   'montant' => $resultat['activites'], 'couleur' => '#F4A261'],
                    ];
                    ?>
                    <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.5rem;">
                        <?php foreach ($postes as $p):
                            $pourcent = $resultat['total'] > 0
                                ? round($p['montant'] / $resultat['total'] * 100)
                                : 0;
                        ?>
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                                    <span><?= $p['label'] ?></span>
                                    <span style="font-weight:700;"><?= formatPrix($p['montant']) ?> (<?= $pourcent ?>%)</span>
                                </div>
                                <div style="background:var(--bordure); border-radius:4px; height:8px; overflow:hidden;">
                                    <div style="width:<?= $pourcent ?>%; height:100%; background:<?= $p['couleur'] ?>; transition:width 0.5s ease;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Total -->
                    <div style="background:var(--bleu); color:white; border-radius:var(--rayon-petit); padding:1.25rem; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-size:0.82rem; opacity:0.8;">Total estimé</div>
                            <div style="font-family:'Poppins',sans-serif; font-size:1.8rem; font-weight:800;">
                                <?= formatPrix($resultat['total']) ?>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.82rem; opacity:0.8;">Soit par jour</div>
                            <div style="font-family:'Poppins',sans-serif; font-size:1.2rem; font-weight:700;">
                                <?= formatPrix($resultat['par_jour']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Conseils budget étudiant -->
                    <?php
                    $conseil = '';
                    $parJour = $resultat['par_jour'];
                    if ($parJour < 40)       $conseil = "🟢 Budget serré — Vous voyagez économe, parfait pour une bourse Erasmus !";
                    elseif ($parJour < 80)   $conseil = "🟡 Budget modéré — Raisonnable pour un étudiant avec un peu d'économies.";
                    elseif ($parJour < 150)  $conseil = "🟠 Budget confortable — Pensez à vérifier les aides disponibles.";
                    else                     $conseil = "🔴 Budget élevé — Cherchez des alternatives moins chères pour réduire les coûts.";
                    ?>
                    <div style="margin-top:0.85rem; padding:0.75rem 1rem; background:var(--fond); border-radius:var(--rayon-petit); font-size:0.88rem;">
                        <?= $conseil ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- ============================================================
             COLONNE LATÉRALE
        ============================================================ -->
        <div style="display:flex; flex-direction:column; gap:1.25rem; position:sticky; top:80px;">

            <!-- Prix de référence -->
            <div class="boite">
                <h3 style="font-size:1rem; margin-bottom:0.85rem;">📌 Prix de référence</h3>
                <p style="font-size:0.82rem; color:var(--texte-doux); margin-bottom:0.75rem;">
                    Cliquez sur une destination pour pré-remplir le formulaire.
                </p>
                <div style="max-height:320px; overflow-y:auto;">
                    <?php foreach ($refPrix as $nom => $infos): ?>
                        <div style="padding:0.5rem 0; border-bottom:1px solid var(--bordure); font-size:0.82rem; cursor:pointer;"
                             onclick="preremplir('<?= addslashes($nom) ?>',
                                                 <?= (float)($infos['transport_min'] ?? 0) ?>,
                                                 <?= (float)($infos['hebergement_min'] ?? 0) ?>)">
                            <div style="font-weight:700; color:var(--texte);"><?= securiser($nom) ?></div>
                            <div style="color:var(--texte-doux);">
                                ✈ dès <?= $infos['transport_min'] ? formatPrix((float)$infos['transport_min']) : '—' ?>
                                &nbsp;·&nbsp;
                                🏨 dès <?= $infos['hebergement_min'] ? formatPrix((float)$infos['hebergement_min']) . '/nuit' : '—' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Liens utiles -->
            <div class="boite">
                <h3 style="font-size:1rem; margin-bottom:0.85rem;">🔗 Ressources</h3>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <a href="universites.php" class="btn btn-secondaire btn-bloc btn-petit">🎓 Universités Erasmus</a>
                    <a href="logements.php"   class="btn btn-secondaire btn-bloc btn-petit">🏠 Logements étudiants</a>
                    <a href="<?= URL_BASE ?>/destinations/liste.php" class="btn btn-secondaire btn-bloc btn-petit">🌍 Destinations</a>
                </div>
            </div>

        </div>
    </div>

    <!-- ================================================================
         HISTORIQUE DES ESTIMATIONS
    ================================================================ -->
    <?php if (!empty($historique)): ?>
        <div style="margin-top:3rem;">
            <h2 style="font-size:1.2rem; margin-bottom:1.25rem;">📂 Mes estimations sauvegardées</h2>

            <div class="tableau-conteneur">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Destination</th>
                            <th>Durée</th>
                            <th>Transport</th>
                            <th>Logement</th>
                            <th>Activités</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $h): ?>
                            <tr>
                                <td><strong><?= securiser($h['nom_destination']) ?></strong></td>
                                <td><?= (int)$h['nb_jours'] ?> jours</td>
                                <td><?= formatPrix((float)$h['transport']) ?></td>
                                <td><?= formatPrix((float)$h['logement']) ?></td>
                                <td><?= formatPrix((float)$h['activites']) ?></td>
                                <td><strong style="color:var(--bleu);"><?= formatPrix((float)$h['total_calcule']) ?></strong></td>
                                <td>
                                    <form action="budget.php" method="POST"
                                          onsubmit="return confirm('Supprimer cette estimation ?')">
                                        <input type="hidden" name="estim_id" value="<?= $h['id'] ?>">
                                        <button type="submit" name="supprimer" class="btn btn-danger btn-petit">
                                            ✕
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
// Pré-remplit le formulaire avec les prix de référence d'une destination
function preremplir(destination, transportMin, logementMin) {
    document.getElementById('nom_destination').value = destination;
    if (transportMin > 0) {
        document.getElementById('transport').value = transportMin.toFixed(2);
    }
    if (logementMin > 0) {
        document.getElementById('logement_nuit').value = logementMin.toFixed(2);
    }
    // Scroller vers le formulaire
    document.getElementById('nom_destination').scrollIntoView({ behavior: 'smooth', block: 'center' });
    document.getElementById('nom_destination').focus();
}
</script>

<?php require_once '../includes/footer.php'; ?>
