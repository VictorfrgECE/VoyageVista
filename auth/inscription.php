<?php
session_start();
require_once '../config/connexion.php';
require_once '../config/constantes.php';
require_once '../includes/fonctions.php';

// Déjà connecté → accueil
if (estConnecte()) {
    rediriger('../index.php');
}

$erreur = '';

// Conserver les valeurs saisies pour les réafficher en cas d'erreur
$champs = [
    'nom'   => '',
    'email' => '',
    'role'  => 'user',
];

// ----------------------------------------------------------------
//  Traitement du formulaire
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = isset($_POST['nom'])    ? trim($_POST['nom'])    : '';
    $email  = isset($_POST['email'])  ? trim($_POST['email'])  : '';
    $mdp    = isset($_POST['mot_de_passe'])         ? $_POST['mot_de_passe']         : '';
    $confirm = isset($_POST['confirmer_mot_de_passe']) ? $_POST['confirmer_mot_de_passe'] : '';
    $role   = isset($_POST['role'])   ? trim($_POST['role'])   : 'user';

    // Garder les valeurs pour le réaffichage
    $champs['nom']   = $nom;
    $champs['email'] = $email;
    $champs['role']  = $role;

    // --- Validations ---
    if (empty($nom) || empty($email) || empty($mdp) || empty($confirm)) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif (strlen($nom) < 2) {
        $erreur = "Le nom doit contenir au moins 2 caractères.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";

    } elseif (strlen($mdp) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";

    } elseif ($mdp !== $confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";

    } elseif (!in_array($role, ['user', 'etudiant', 'prestataire'])) {
        $erreur = "Type de compte invalide.";

    } else {
        // Vérifier que l'email n'est pas déjà utilisé
        $stmt = $pdo->prepare("SELECT id FROM UTILISATEURS WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $erreur = "Cette adresse email est déjà utilisée.";
        } else {
            // Tout est valide → insertion
            $hash = password_hash($mdp, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO UTILISATEURS (nom, email, mot_de_passe, role)
                 VALUES (:nom, :email, :mdp, :role)"
            );
            $stmt->execute([
                ':nom'   => $nom,
                ':email' => $email,
                ':mdp'   => $hash,
                ':role'  => $role,
            ]);

            $newUserId = (int) $pdo->lastInsertId();

            // Créer une notification de bienvenue
            if ($role === 'etudiant') {
                $messageNotif = "Votre compte étudiant a été créé. Accédez à l'Espace Étudiant pour trouver des universités et logements Erasmus.";
            } elseif ($role === 'prestataire') {
                $messageNotif = "Votre compte prestataire a été créé. Vous pouvez maintenant ajouter vos hébergements et transports sur VoyageVista.";
            } else {
                $messageNotif = "Votre compte a été créé avec succès. Explorez nos destinations et planifiez votre prochain voyage !";
            }

            creerNotification($pdo, $newUserId, "Bienvenue sur VoyageVista !", $messageNotif, 'info');

            // Message flash de succès transmis à la page connexion
            $_SESSION['flash_succes'] = "Compte créé avec succès ! Connectez-vous maintenant.";
            rediriger('connexion.php');
        }
    }
}

$titrePage       = 'Inscription';
$descriptionPage = 'Créez votre compte VoyageVista et planifiez votre prochain voyage étudiant.';
require_once '../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-boite">

        <h1>Créer un compte ✈</h1>
        <p class="auth-sous-titre">Rejoignez des milliers d'étudiants voyageurs.</p>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
        <?php endif; ?>

        <form action="inscription.php" method="POST" novalidate>

            <div class="champ-groupe">
                <label for="nom">Nom complet</label>
                <input
                    type="text"
                    id="nom"
                    name="nom"
                    class="champ"
                    value="<?= securiser($champs['nom']) ?>"
                    placeholder="Prénom Nom"
                    required
                    autocomplete="name"
                    minlength="2"
                >
            </div>

            <div class="champ-groupe">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="champ"
                    value="<?= securiser($champs['email']) ?>"
                    placeholder="votre@email.fr"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="champ-groupe">
                <label for="mot_de_passe">Mot de passe</label>
                <input
                    type="password"
                    id="mot_de_passe"
                    name="mot_de_passe"
                    class="champ"
                    placeholder="Minimum 8 caractères"
                    required
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>

            <div class="champ-groupe">
                <label for="confirmer_mot_de_passe">Confirmer le mot de passe</label>
                <input
                    type="password"
                    id="confirmer_mot_de_passe"
                    name="confirmer_mot_de_passe"
                    class="champ"
                    placeholder="Répétez votre mot de passe"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="champ-groupe">
                <label for="role">Type de compte</label>
                <select id="role" name="role" class="champ">
                    <option value="user"        <?= $champs['role'] === 'user'        ? 'selected' : '' ?>>
                        Voyageur — je planifie des séjours
                    </option>
                    <option value="etudiant"   <?= $champs['role'] === 'etudiant'   ? 'selected' : '' ?>>
                        Étudiant — Erasmus / échange universitaire
                    </option>
                    <option value="prestataire" <?= $champs['role'] === 'prestataire' ? 'selected' : '' ?>>
                        Prestataire — je propose des hébergements / transports
                    </option>
                </select>
            </div>

            <button type="submit" name="sinscrire" class="btn btn-primaire btn-bloc btn-grand">
                Créer mon compte
            </button>

        </form>

        <p class="auth-lien">
            Déjà inscrit ?
            <a href="connexion.php"><strong>Se connecter</strong></a>
        </p>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
