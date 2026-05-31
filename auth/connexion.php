<?php
session_start();
require_once '../config/connexion.php';
require_once '../includes/fonctions.php';

// Déjà connecté → accueil
if (estConnecte()) {
    rediriger('../index.php');
}

$erreur      = '';
$emailSaisie = '';

// Récupérer un éventuel message de succès venant de l'inscription
$succes = '';
if (isset($_SESSION['flash_succes'])) {
    $succes = $_SESSION['flash_succes'];
    unset($_SESSION['flash_succes']);
}

// ----------------------------------------------------------------
//  Traitement du formulaire
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mdp   = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    $emailSaisie = $email;

    if (empty($email) || empty($mdp)) {
        $erreur = "Veuillez remplir tous les champs.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";

    } else {
        $stmt = $pdo->prepare("SELECT * FROM UTILISATEURS WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
            $erreur = "Email ou mot de passe incorrect.";
        } else {
            // Connexion réussie — on remplit la session
            $_SESSION['utilisateur_id'] = $user['id'];
            $_SESSION['nom']            = $user['nom'];
            $_SESSION['email']          = $user['email'];
            $_SESSION['role']           = $user['role'];

            rediriger('../index.php');
        }
    }
}

$titrePage        = 'Connexion';
$descriptionPage  = 'Connectez-vous à votre compte VoyageVista.';
require_once '../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-boite">

        <h1>Bon retour ! 👋</h1>
        <p class="auth-sous-titre">Connectez-vous pour accéder à votre espace voyage.</p>

        <?php if ($succes): ?>
            <div class="alerte alerte-succes"><?= securiser($succes) ?></div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= securiser($erreur) ?></div>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form action="connexion.php" method="POST" novalidate data-no-lock>

            <div class="champ-groupe">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="champ"
                    value="<?= securiser($emailSaisie) ?>"
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
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" name="se_connecter" class="btn btn-primaire btn-bloc btn-grand">
                Se connecter
            </button>

        </form>

        <div class="auth-separateur">ou continuer avec</div>

        <!-- Boutons sociaux (visuels — pas d'intégration OAuth) -->
        <div style="display:flex; gap:0.75rem; justify-content:center;">
            <button type="button" class="btn btn-secondaire" style="flex:1;" disabled title="Connexion Google (non disponible)">
                <img src="https://www.google.com/favicon.ico" width="16" alt=""> Google
            </button>
            <button type="button" class="btn btn-secondaire" style="flex:1;" disabled title="Connexion Facebook (non disponible)">
                <span style="color:#1877F2;">f</span> Facebook
            </button>
        </div>

        <p class="auth-lien">
            Pas encore de compte ?
            <a href="inscription.php"><strong>S'inscrire gratuitement</strong></a>
        </p>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
