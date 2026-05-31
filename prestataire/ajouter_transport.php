<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

if (!estConnecte() || (!estPrestataire() && !estAdmin())) {
    rediriger('../index.php');
}

$erreur = '';
$succes = '';

// Liste des destinations pour le menu déroulant
$destinations = $pdo->query("SELECT id, nom, pays FROM DESTINATIONS ORDER BY pays, nom")->fetchAll();

// Conserver les valeurs saisies en cas d'erreur
$champs = [
    'type'           => '',
    'compagnie'      => '',
    'lieu_depart'    => '',
    'lieu_arrivee'   => '',
    'prix'           => '',
    'destination_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soumettre'])) {
    $champs['type']           = trim($_POST['type']           ?? '');
    $champs['compagnie']      = trim($_POST['compagnie']      ?? '');
    $champs['lieu_depart']    = trim($_POST['lieu_depart']    ?? '');
    $champs['lieu_arrivee']   = trim($_POST['lieu_arrivee']   ?? '');
    $champs['prix']           = trim($_POST['prix']           ?? '');
    $champs['destination_id'] = trim($_POST['destination_id'] ?? '');

    $typesValides = ['avion', 'train', 'bus', 'ferry', 'voiture'];

    if (!in_array($champs['type'], $typesValides)) {
        $erreur = "Veuillez sélectionner un type de transport valide.";
    } elseif (empty($champs['compagnie'])) {
        $erreur = "Le nom de la compagnie est obligatoire.";
    } elseif (empty($champs['lieu_depart'])) {
        $erreur = "Le lieu de départ est obligatoire.";
    } elseif (empty($champs['lieu_arrivee'])) {
        $erreur = "Le lieu d'arrivée est obligatoire.";
    } elseif (!is_numeric($champs['prix']) || (float)$champs['prix'] <= 0) {
        $erreur = "Le prix doit être un nombre positif.";
    } else {
        $destId = ($champs['destination_id'] !== '' && ctype_digit($champs['destination_id']))
                  ? (int)$champs['destination_id'] : null;

        $stmt = $pdo->prepare(
            "INSERT INTO TRANSPORTS (type, compagnie, lieu_depart, lieu_arrivee, prix, destination_id)
             VALUES (:type, :compagnie, :depart, :arrivee, :prix, :dest_id)"
        );
        $stmt->execute([
            ':type'      => $champs['type'],
            ':compagnie' => $champs['compagnie'],
            ':depart'    => $champs['lieu_depart'],
            ':arrivee'   => $champs['lieu_arrivee'],
            ':prix'      => (float)$champs['prix'],
            ':dest_id'   => $destId,
        ]);

        $succes = "Le transport « " . securiser($champs['compagnie']) . " — " . securiser($champs['lieu_depart']) . " → " . securiser($champs['lieu_arrivee']) . " » a été ajouté avec succès.";
        $champs = array_fill_keys(array_keys($champs), '');
    }
}

$titrePage = 'Ajouter un transport';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>✈ Ajouter un transport</h1>
        <p>Proposez votre offre de transport aux voyageurs VoyageVista</p>
    </div>
</div>

<div class="container section" style="max-width:700px;">

    <nav class="breadcrumb" style="margin-bottom:1.5rem;">
        <a href="<?= URL_BASE ?>/index.php">Accueil</a>
        <span class="breadcrumb-sep">›</span>
        <a href="index.php">Espace Prestataire</a>
        <span class="breadcrumb-sep">›</span>
        <span>Ajouter un transport</span>
    </nav>

    <?php if ($succes): ?>
        <div class="alerte alerte-succes">✅ <?= $succes ?>
            — <a href="index.php">Retour à l'espace prestataire</a>
            ou <a href="ajouter_transport.php">ajouter un autre transport</a>.
        </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
    <?php endif; ?>

    <div class="boite">
        <form action="ajouter_transport.php" method="POST" novalidate>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                <div class="champ-groupe">
                    <label for="type">Type de transport *</label>
                    <select id="type" name="type" class="champ" required>
                        <option value="">— Choisir —</option>
                        <?php
                        $types = [
                            'avion'   => '✈ Avion',
                            'train'   => '🚂 Train',
                            'bus'     => '🚌 Bus',
                            'ferry'   => '⛴ Ferry',
                            'voiture' => '🚗 Voiture',
                        ];
                        foreach ($types as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $champs['type'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ-groupe">
                    <label for="compagnie">Compagnie / Opérateur *</label>
                    <input type="text" id="compagnie" name="compagnie" class="champ"
                           value="<?= securiser($champs['compagnie']) ?>"
                           placeholder="Ex : Air France, SNCF, FlixBus…"
                           required maxlength="100">
                </div>

            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                <div class="champ-groupe">
                    <label for="lieu_depart">Lieu de départ *</label>
                    <input type="text" id="lieu_depart" name="lieu_depart" class="champ"
                           value="<?= securiser($champs['lieu_depart']) ?>"
                           placeholder="Ex : Paris CDG, Gare de Lyon…"
                           required maxlength="150">
                </div>

                <div class="champ-groupe">
                    <label for="lieu_arrivee">Lieu d'arrivée *</label>
                    <input type="text" id="lieu_arrivee" name="lieu_arrivee" class="champ"
                           value="<?= securiser($champs['lieu_arrivee']) ?>"
                           placeholder="Ex : Barcelone, Rome Termini…"
                           required maxlength="150">
                </div>

            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                <div class="champ-groupe">
                    <label for="prix">Prix par personne (€) *</label>
                    <input type="number" id="prix" name="prix" class="champ"
                           value="<?= securiser($champs['prix']) ?>"
                           min="0.01" step="0.01" placeholder="Ex : 89.00" required>
                </div>

                <div class="champ-groupe">
                    <label for="destination_id">Destination liée <span style="font-weight:400; color:var(--texte-doux);">(optionnel)</span></label>
                    <select id="destination_id" name="destination_id" class="champ">
                        <option value="">— Aucune destination liée —</option>
                        <?php foreach ($destinations as $dest): ?>
                            <option value="<?= $dest['id'] ?>"
                                <?= $champs['destination_id'] == $dest['id'] ? 'selected' : '' ?>>
                                <?= securiser($dest['nom']) ?> (<?= securiser($dest['pays']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div style="display:flex; gap:1rem; margin-top:0.5rem;">
                <button type="submit" name="soumettre" class="btn btn-orange btn-grand">
                    ✅ Publier le transport
                </button>
                <a href="index.php" class="btn btn-secondaire btn-grand">Annuler</a>
            </div>

        </form>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
