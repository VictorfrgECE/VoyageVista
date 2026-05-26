<?php
class ItineraryController {
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

    private function index(): array {
        $rows = $this->db->query(
            "SELECT i.*, u.name AS user_name FROM itineraries i
             LEFT JOIN users u ON u.id = i.user_id ORDER BY i.start_date ASC"
        )->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare(
            "SELECT i.*, u.name AS user_name FROM itineraries i
             LEFT JOIN users u ON u.id = i.user_id WHERE i.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        foreach (['user_id', 'title', 'start_date', 'end_date'] as $f) {
            if (empty($data[$f])) { http_response_code(422); return ['error' => "$f is required"]; }
        }
        $stmt = $this->db->prepare(
            "INSERT INTO itineraries (user_id, title, description, start_date, end_date, status, total_budget)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$data['user_id'], $data['title'], $data['description'] ?? null, $data['start_date'], $data['end_date'], $data['status'] ?? 'draft', $data['total_budget'] ?? 0.00]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $stmt = $this->db->prepare(
            "UPDATE itineraries SET title=?, description=?, start_date=?, end_date=?, status=?, total_budget=? WHERE id=?"
        );
        $stmt->execute([$data['title'], $data['description'] ?? null, $data['start_date'], $data['end_date'], $data['status'] ?? 'draft', $data['total_budget'] ?? 0.00, $id]);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM itineraries WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function notFound(): array { http_response_code(404); return ['error' => 'Not found']; }
    private function notAllowed(): array { http_response_code(405); return ['error' => 'Method not allowed']; }
}
