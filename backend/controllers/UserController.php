<?php
class UserController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, ?int $id, array $body): array {
        return match($method) {
            'GET'    => $id ? $this->show($id) : $this->index(),
            'POST'   => $this->store($body),
            'PUT'    => $this->update($id, $body),
            'DELETE' => $this->destroy($id),
            default  => $this->notAllowed(),
        };
    }

    public function handleAuth(?string $action, array $body): array {
        return match($action) {
            'login'    => $this->login($body),
            'register' => $this->register($body),
            'me'       => $this->me(),
            'logout'   => $this->logout(),
            default    => ['error' => 'Action inconnue'],
        };
    }

    // --- CRUD utilisateurs ---

    private function index(): array {
        $rows = $this->db->query(
            "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC"
        )->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        // Validation des champs obligatoires
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(422);
            return ['error' => 'name, email et password sont requis'];
        }
        // Validation format email (filtre natif PHP)
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            return ['error' => 'Format email invalide'];
        }
        // Validation force du mot de passe (minimum 8 caractères)
        if (strlen($data['password']) < 8) {
            http_response_code(422);
            return ['error' => 'Le mot de passe doit contenir au moins 8 caractères'];
        }
        // Vérification unicité de l'email (requête préparée = protection injection SQL)
        $check = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([trim($data['email'])]);
        if ($check->fetch()) {
            http_response_code(409);
            return ['error' => 'Cet email est déjà utilisé'];
        }
        // Sanitisation XSS : htmlspecialchars convertit < > " ' & en entités HTML
        $name  = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars(trim($data['email']), ENT_QUOTES, 'UTF-8');
        // Validation du rôle par liste blanche (empêche l'injection de rôles arbitraires)
        $allowedRoles = ['admin', 'prestataire', 'etudiant'];
        $role = in_array($data['role'] ?? '', $allowedRoles, true) ? $data['role'] : 'etudiant';
        // Hashage bcrypt : coût 10, irréversible, résistant aux attaques par dictionnaire
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $role]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $fields = [];
        $values = [];
        foreach (['name', 'email', 'avatar_url'] as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                // Sanitisation XSS sur chaque champ modifié
                $values[] = htmlspecialchars(trim($data[$f]), ENT_QUOTES, 'UTF-8');
            }
        }
        if (empty($fields)) return ['error' => 'Aucun champ à mettre à jour'];
        $values[] = $id;
        $this->db->prepare(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?"
        )->execute($values);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    // --- Authentification ---

    private function login(array $body): array {
        if (empty($body['email']) || empty($body['password'])) {
            http_response_code(422);
            return ['error' => 'Email et mot de passe requis'];
        }
        // Requête préparée : empêche toute injection SQL via l'email
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([trim($body['email'])]);
        $user = $stmt->fetch();
        // password_verify compare de manière sécurisée (temps constant, anti timing-attack)
        if (!$user || !password_verify($body['password'], $user['password'])) {
            http_response_code(401);
            return ['error' => 'Email ou mot de passe incorrect'];
        }
        // Session PHP côté serveur : stocke l'identifiant utilisateur
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_at'] = time();
        // Token signé HMAC-SHA256 : permet l'authentification sans état (stateless)
        $token = $this->generateToken((int)$user['id'], $user['role']);
        return [
            'token' => $token,
            'user'  => [
                'id'   => (int)$user['id'],
                // Sanitisation XSS sur le nom renvoyé au client
                'name' => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
                'role' => $user['role'],
            ],
        ];
    }

    private function register(array $body): array {
        // Crée le compte, puis authentifie immédiatement (meilleure UX)
        $result = $this->store($body);
        if (isset($result['error'])) return $result;
        $newId = $result['data']['id'];
        $stmt  = $this->db->prepare("SELECT id, name, role FROM users WHERE id = ?");
        $stmt->execute([$newId]);
        $user = $stmt->fetch();
        // Session PHP pour le nouvel utilisateur
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_at'] = time();
        http_response_code(201);
        return [
            'token' => $this->generateToken((int)$user['id'], $user['role']),
            'user'  => [
                'id'   => (int)$user['id'],
                'name' => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
                'role' => $user['role'],
            ],
        ];
    }

    private function me(): array {
        // Retourne l'utilisateur courant à partir du Bearer token
        $token = $this->extractToken();
        if (!$token) {
            http_response_code(401);
            return ['error' => 'Non authentifié'];
        }
        $payload = $this->verifyToken($token);
        if (!$payload) {
            http_response_code(401);
            return ['error' => 'Token invalide ou expiré'];
        }
        $stmt = $this->db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$payload['id']]);
        $user = $stmt->fetch();
        if (!$user) return $this->notFound();
        return [
            'user' => [
                'id'    => (int)$user['id'],
                'name'  => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
                'role'  => $user['role'],
            ],
        ];
    }

    private function logout(): array {
        // Détruit la session PHP côté serveur
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        return ['success' => true];
    }

    // --- Helpers privés ---

    private function generateToken(int $userId, string $role): string {
        // Payload encodé en base64 avec expiration à 24h
        $payload = base64_encode(json_encode([
            'id'   => $userId,
            'role' => $role,
            'exp'  => time() + 86400,
        ]));
        $secret = $_ENV['JWT_SECRET'] ?? 'secret';
        // Signature HMAC-SHA256 : garantit l'intégrité du token (non falsifiable sans la clé)
        $sig = hash_hmac('sha256', $payload, $secret);
        return "$payload.$sig";
    }

    private function verifyToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return null;
        [$payload, $sig] = $parts;
        $secret      = $_ENV['JWT_SECRET'] ?? 'secret';
        $expectedSig = hash_hmac('sha256', $payload, $secret);
        // hash_equals : comparaison en temps constant pour éviter les timing attacks
        if (!hash_equals($expectedSig, $sig)) return null;
        $data = json_decode(base64_decode($payload), true);
        if (!$data || $data['exp'] < time()) return null;
        return $data;
    }

    private function extractToken(): ?string {
        // Extrait le token depuis l'en-tête "Authorization: Bearer <token>"
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function notFound(): array {
        http_response_code(404);
        return ['error' => 'Introuvable'];
    }

    private function notAllowed(): array {
        http_response_code(405);
        return ['error' => 'Méthode non autorisée'];
    }
}
