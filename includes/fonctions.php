<?php
// ----------------------------------------------------------------
//  Authentification / session
// ----------------------------------------------------------------

function estConnecte(): bool {
    return isset($_SESSION['utilisateur_id']);
}

function estAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function estEtudiant(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant';
}

function estPrestataire(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'prestataire';
}

// ----------------------------------------------------------------
//  Navigation
// ----------------------------------------------------------------

function rediriger(string $url): void {
    header("Location: $url");
    exit;
}

// ----------------------------------------------------------------
//  Sécurité / nettoyage des données
// ----------------------------------------------------------------

// Échappe pour l'affichage HTML (anti-XSS)
function securiser(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// ----------------------------------------------------------------
//  Messages utilisateur
// ----------------------------------------------------------------

function afficherErreur(string $message): string {
    return "<div class='alerte alerte-erreur'>" . securiser($message) . "</div>";
}

function afficherSucces(string $message): string {
    return "<div class='alerte alerte-succes'>" . securiser($message) . "</div>";
}

// ----------------------------------------------------------------
//  Formatage
// ----------------------------------------------------------------

function formatPrix(float $prix): string {
    return number_format($prix, 2, ',', ' ') . ' €';
}

function formatDate(string $date): string {
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

// Génère des étoiles HTML à partir d'un nombre
function afficherEtoiles(int $nb, int $max = 5): string {
    $html = '<span class="etoiles">';
    for ($i = 1; $i <= $max; $i++) {
        $html .= $i <= $nb ? '★' : '☆';
    }
    $html .= '</span>';
    return $html;
}

// ----------------------------------------------------------------
//  Notifications
// ----------------------------------------------------------------

function creerNotification(PDO $pdo, int $userId, string $titre, string $message, string $type = 'info'): void {
    $stmt = $pdo->prepare(
        "INSERT INTO NOTIFICATIONS (titre, message, type, utilisateur_id)
         VALUES (:titre, :message, :type, :uid)"
    );
    $stmt->execute([
        ':titre'   => $titre,
        ':message' => $message,
        ':type'    => $type,
        ':uid'     => $userId,
    ]);
}

function compterNotificationsNonLues(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM NOTIFICATIONS WHERE utilisateur_id = :uid AND est_lu = 0"
    );
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

// ----------------------------------------------------------------
//  Images (local ou URL externe)
// ----------------------------------------------------------------

function imageExiste(string $chemin): bool {
    if (empty($chemin)) return false;
    if (strpos($chemin, 'http') === 0) return true;
    return file_exists('../' . $chemin);
}

function srcImage(string $chemin): string {
    if (strpos($chemin, 'http') === 0) return htmlspecialchars($chemin, ENT_QUOTES, 'UTF-8');
    return (defined('URL_BASE') ? URL_BASE . '/' : '../') . htmlspecialchars($chemin, ENT_QUOTES, 'UTF-8');
}

// ----------------------------------------------------------------
//  Panier (session)
// ----------------------------------------------------------------

function getPanier(): array {
    return $_SESSION['panier'] ?? [];
}

function ajouterAuPanier(string $type, int $referenceId, float $prix, string $nom, array $extra = []): void {
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    $cle = $type . '_' . $referenceId;
    $_SESSION['panier'][$cle] = array_merge([
        'type'         => $type,
        'reference_id' => $referenceId,
        'prix'         => $prix,
        'nom'          => $nom,
    ], $extra);
}

function supprimerDuPanier(string $cle): void {
    unset($_SESSION['panier'][$cle]);
}

function viderPanier(): void {
    $_SESSION['panier'] = [];
}

function totalPanier(): float {
    $total = 0.0;
    foreach (getPanier() as $item) {
        $total += $item['prix'];
    }
    return $total;
}
