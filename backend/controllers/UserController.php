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

    public function handleAuth(string $action, array $body): array {
        return match($action) {
            'login'    => $this->login($body),
            'register' => $this->register($body),
            default    => ['error' => 'Unknown auth action'],
        };
    }

    private function index(): array {
        $rows = $this->db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(422);
            return ['error' => 'name, email and password are required'];
        }
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['email'], $hash, $data['role'] ?? 'user']);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $fields = [];
        $values = [];
        foreach (['name', 'email', 'avatar_url'] as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $values[] = $data[$f]; }
        }
        if (empty($fields)) return ['error' => 'Nothing to update'];
        $values[] = $id;
        $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function login(array $body): array {
        if (empty($body['email']) || empty($body['password'])) {
            http_response_code(422);
            return ['error' => 'email and password required'];
        }
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$body['email']]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($body['password'], $user['password'])) {
            http_response_code(401);
            return ['error' => 'Invalid credentials'];
        }
        // Minimal JWT-like token (base64 payload — replace with a real JWT library in production)
        $payload = base64_encode(json_encode(['id' => $user['id'], 'role' => $user['role'], 'exp' => time() + 86400]));
        $secret  = $_ENV['JWT_SECRET'] ?? 'secret';
        $sig     = hash_hmac('sha256', $payload, $secret);
        return ['token' => "$payload.$sig", 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]];
    }

    private function register(array $body): array {
        return $this->store($body);
    }

    private function notFound(): array {
        http_response_code(404);
        return ['error' => 'Not found'];
    }

    private function notAllowed(): array {
        http_response_code(405);
        return ['error' => 'Method not allowed'];
    }
}
