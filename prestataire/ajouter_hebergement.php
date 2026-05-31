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
    'nom'            => '',
    'type'           => '',
    'prix_par_nuit'  => '',
    'etoiles'        => '',
    'description'    => '',
    'destination_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soumettre'])) {
    $champs['nom']            = trim($_POST['nom']            ?? '');
    $champs['type']           = trim($_POST['type']           ?? '');
    $champs['prix_par_nuit']  = trim($_POST['prix_par_nuit']  ?? '');
    $champs['etoiles']        = trim($_POST['etoiles']        ?? '');
    $champs['description']    = trim($_POST['description']    ?? '');
    $champs['destination_id'] = trim($_POST['destination_id'] ?? '');

    $typesValides = ['hotel', 'auberge', 'appartement', 'villa', 'camping', 'residence'];

    if (empty($champs['nom'])) {
        $erreur = "Le nom de l'hébergement est obligatoire.";
    } elseif (!in_array($champs['type'], $typesValides)) {
        $erreur = "Veuillez sélectionner un type d'hébergement valide.";
    } elseif (!is_numeric($champs['prix_par_nuit']) || (float)$champs['prix_par_nuit'] <= 0) {
        $erreur = "Le prix par nuit doit être un nombre positif.";
    } elseif (empty($champs['destination_id']) || !ctype_digit($champs['destination_id'])) {
        $erreur = "Veuillez sélectionner une destination.";
    } else {
        $etoilesVal = ($champs['etoiles'] !== '' && ctype_digit($champs['etoiles'])
                       && (int)$champs['etoiles'] >= 1 && (int)$champs['etoiles'] <= 5)
                      ? (int)$champs['etoiles'] : null;

        $stmt = $pdo->prepare(
            "INSERT INTO HEBERGEMENTS (nom, type, prix_par_nuit, etoiles, description, destination_id)
             VALUES (:nom, :type, :prix, :etoiles, :description, :dest_id)"
        );
        $stmt->execute([
            ':nom'         => $champs['nom'],
            ':type'        => $champs['type'],
            ':prix'        => (float)$champs['prix_par_nuit'],
            ':etoiles'     => $etoilesVal,
            ':description' => $champs['description'] !== '' ? $champs['description'] : null,
            ':dest_id'     => (int)$champs['destination_id'],
        ]);

        $succes = "L'hébergement « " . securiser($champs['nom']) . " » a été ajouté avec succès.";
        $champs = array_fill_keys(array_keys($champs), '');
    }
}

$titrePage = 'Ajouter un hébergement';
require_once '../includes/header.php';
?>

<div class="page-entete">
    <div class="container">
        <h1>🏨 Ajouter un hébergement</h1>
        <p>Proposez votre offre aux voyageurs VoyageVista</p>
    </div>
</div>

<div class="container section" style="max-width:700px;">

    <nav class="breadcrumb" style="margin-bottom:1.5rem;">
        <a href="<?= URL_BASE ?>/index.php">Accueil</a>
        <span class="breadcrumb-sep">›</span>
        <a href="index.php">Espace Prestataire</a>
        <span class="breadcrumb-sep">›</span>
        <span>Ajouter un hébergement</span>
    </nav>

    <?php if ($succes): ?>
        <div class="alerte alerte-succes">✅ <?= $succes ?>
            — <a href="index.php">Retour à l'espace prestataire</a>
            ou <a href="ajouter_hebergement.php">ajouter un autre hébergement</a>.
        </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
    <?php endif; ?>

    <div class="boite">
        <form action="ajouter_hebergement.php" method="POST" novalidate>

            <div class="champ-groupe">
                <label for="nom">Nom de l'hébergement *</label>
                <input type="text" id="nom" name="nom" class="champ"
                       value="<?= securiser($champs['nom']) ?>"
                       placeholder="Ex : Hôtel Soleil, Auberge du Port…"
                       required maxlength="150">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                <div class="champ-groupe">
                    <label for="type">Type *</label>
                    <select id="type" name="type" class="champ" required>
                        <option value="">— Choisir —</option>
                        <?php
                        $types = [
                            'hotel'       => 'Hôtel',
                            'auberge'     => 'Auberge de jeunesse',
                            'appartement' => 'Appartement',
                            'villa'       => 'Villa',
                            'camping'     => 'Camping',
                            'residence'   => 'Résidence',
                        ];
                        foreach ($types as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $champs['type'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ-groupe">
                    <label for="prix_par_nuit">Prix par nuit (€) *</label>
                    <input type="number" id="prix_par_nuit" name="prix_par_nuit" class="champ"
                           value="<?= securiser($champs['prix_par_nuit']) ?>"
                           min="0.01" step="0.01" placeholder="Ex : 45.00" required>
                </div>

            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                <div class="champ-groupe">
                    <label for="destination_id">Destination *</label>
                    <select id="destination_id" name="destination_id" class="champ" required>
                        <option value="">— Choisir une destination —</option>
                        <?php foreach ($destinations as $dest): ?>
                            <option value="<?= $dest['id'] ?>"
                                <?= $champs['destination_id'] == $dest['id'] ? 'selected' : '' ?>>
                                <?= securiser($dest['nom']) ?> (<?= securiser($dest['pays']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ-groupe">
                    <label for="etoiles">Classement étoiles <span style="font-weight:400; color:var(--texte-doux);">(optionnel)</span></label>
                    <select id="etoiles" name="etoiles" class="champ">
                        <option value="">— Sans classement —</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $champs['etoiles'] == $i ? 'selected' : '' ?>>
                                <?= str_repeat('★', $i) ?> (<?= $i ?> étoile<?= $i > 1 ? 's' : '' ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

            </div>

            <div class="champ-groupe">
                <label for="description">Description <span style="font-weight:400; color:var(--texte-doux);">(optionnel)</span></label>
                <textarea id="description" name="description" class="champ" rows="4"
                          placeholder="Décrivez votre hébergement : équipements, ambiance, points forts…"
                          style="resize:vertical;"><?= securiser($champs['description']) ?></textarea>
            </div>

            <div style="display:flex; gap:1rem; margin-top:0.5rem;">
                <button type="submit" name="soumettre" class="btn btn-orange btn-grand">
                    ✅ Publier l'hébergement
                </button>
                <a href="index.php" class="btn btn-secondaire btn-grand">Annuler</a>
            </div>

        </form>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
