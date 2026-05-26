<?php
class NotificationController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, ?int $id, array $body): array {
        return match($method) {
            'GET'    => $id ? $this->show($id) : $this->index(),
            'POST'   => $this->store($body),
            'PUT'    => $this->markRead($id),
            'DELETE' => $this->destroy($id),
            default  => $this->notAllowed(),
        };
    }

    private function index(): array {
        $rows = $this->db->query(
            "SELECT * FROM notifications ORDER BY created_at DESC"
        )->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        foreach (['user_id', 'title', 'message'] as $f) {
            if (empty($data[$f])) { http_response_code(422); return ['error' => "$f is required"]; }
        }
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$data['user_id'], $data['title'], $data['message'], $data['type'] ?? 'info']);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function markRead(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM notifications WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function notFound(): array { http_response_code(404); return ['error' => 'Not found']; }
    private function notAllowed(): array { http_response_code(405); return ['error' => 'Method not allowed']; }
}
