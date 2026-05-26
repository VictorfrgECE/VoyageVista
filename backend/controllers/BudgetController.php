<?php
class BudgetController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, ?int $id, array $body): array {
        return match($method) {
            'GET'    => $id ? $this->show($id) : $this->index(),
            'POST'   => $this->store($body),
            'DELETE' => $this->destroy($id),
            default  => $this->notAllowed(),
        };
    }

    // GET /budget_estimations — liste les estimations de l'utilisateur connecté
    private function index(): array {
        $payload = $this->requireAuth();
        if (isset($payload['error'])) return $payload;

        $stmt = $this->db->prepare(
            "SELECT id, destination, transport, logement, activites,
                    vie_quotidienne_par_jour, nb_jours, total_calcule, created_at
             FROM budget_estimations
             WHERE user_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$payload['id']]);
        return ['data' => $stmt->fetchAll()];
    }

    // GET /budget_estimations/{id} — détail d'une estimation (propriétaire uniquement)
    private function show(int $id): array {
        $payload = $this->requireAuth();
        if (isset($payload['error'])) return $payload;

        $stmt = $this->db->prepare(
            "SELECT * FROM budget_estimations WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$id, $payload['id']]);
        $row = $stmt->fetch();
        if (!$row) return $this->notFound();
        return ['data' => $row];
    }

    // POST /budget_estimations — sauvegarde une nouvelle estimation (connecté uniquement)
    private function store(array $body): array {
        $payload = $this->requireAuth();
        if (isset($payload['error'])) return $payload;

        // Validation : la destination est obligatoire
        if (empty($body['destination'])) {
            http_response_code(422);
            return ['error' => 'La destination est requise'];
        }

        // Durée minimale 1 jour
        $nb_jours = isset($body['nb_jours']) && is_numeric($body['nb_jours'])
            ? max(1, (int)$body['nb_jours'])
            : 30;

        // Validation et cast des montants (positifs uniquement)
        $transport  = max(0.0, (float)($body['transport']   ?? 0));
        $logement   = max(0.0, (float)($body['logement']    ?? 0));
        $activites  = max(0.0, (float)($body['activites']   ?? 0));
        $vieParJour = max(0.0, (float)($body['vie_quotidienne_par_jour'] ?? 0));

        // Sanitisation XSS sur le seul champ texte libre
        $destination = htmlspecialchars(trim($body['destination']), ENT_QUOTES, 'UTF-8');

        $stmt = $this->db->prepare(
            "INSERT INTO budget_estimations
             (user_id, destination, transport, logement, activites, vie_quotidienne_par_jour, nb_jours)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $payload['id'],
            $destination,
            $transport,
            $logement,
            $activites,
            $vieParJour,
            $nb_jours,
        ]);

        // Relit la ligne pour obtenir le total_calcule généré par MySQL
        $newId = (int)$this->db->lastInsertId();
        $fetch = $this->db->prepare("SELECT * FROM budget_estimations WHERE id = ?");
        $fetch->execute([$newId]);

        http_response_code(201);
        return ['data' => $fetch->fetch()];
    }

    // DELETE /budget_estimations/{id} — supprime une estimation (propriétaire uniquement)
    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();

        $payload = $this->requireAuth();
        if (isset($payload['error'])) return $payload;

        // Vérifie que l'estimation appartient à l'utilisateur avant suppression
        $check = $this->db->prepare(
            "SELECT id FROM budget_estimations WHERE id = ? AND user_id = ?"
        );
        $check->execute([$id, $payload['id']]);
        if (!$check->fetch()) return $this->notFound();

        $this->db->prepare("DELETE FROM budget_estimations WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    // --- Helpers authentification ---

    /**
     * Extrait et vérifie le Bearer token HMAC-SHA256.
     * Retourne le payload (id, role, exp) ou une réponse d'erreur 401.
     * Même algorithme que UserController::generateToken/verifyToken.
     */
    private function requireAuth(): array {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.*)$/i', $header, $m)) {
            http_response_code(401);
            return ['error' => 'Authentification requise'];
        }
        $parts = explode('.', $m[1]);
        if (count($parts) !== 2) {
            http_response_code(401);
            return ['error' => 'Token malformé'];
        }
        [$payload64, $sig] = $parts;
        $secret = $_ENV['JWT_SECRET'] ?? 'secret';
        // hash_equals : comparaison en temps constant (anti timing-attack)
        if (!hash_equals(hash_hmac('sha256', $payload64, $secret), $sig)) {
            http_response_code(401);
            return ['error' => 'Token invalide'];
        }
        $data = json_decode(base64_decode($payload64), true);
        if (!$data || $data['exp'] < time()) {
            http_response_code(401);
            return ['error' => 'Token expiré'];
        }
        return $data;
    }

    private function notFound(): array {
        http_response_code(404);
        return ['error' => 'Estimation introuvable'];
    }

    private function notAllowed(): array {
        http_response_code(405);
        return ['error' => 'Méthode non autorisée'];
    }
}
