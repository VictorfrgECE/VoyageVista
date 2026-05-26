<?php
class ActivityController {
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
            "SELECT a.*, d.name AS destination_name FROM activities a
             LEFT JOIN destinations d ON d.id = a.destination_id ORDER BY a.name ASC"
        )->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, d.name AS destination_name FROM activities a
             LEFT JOIN destinations d ON d.id = a.destination_id WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        foreach (['name', 'destination_id'] as $f) {
            if (empty($data[$f])) { http_response_code(422); return ['error' => "$f is required"]; }
        }
        $stmt = $this->db->prepare(
            "INSERT INTO activities (name, description, destination_id, duration_hours, price, category, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$data['name'], $data['description'] ?? null, $data['destination_id'], $data['duration_hours'] ?? null, $data['price'] ?? 0.00, $data['category'] ?? null, $data['image_url'] ?? null]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $stmt = $this->db->prepare(
            "UPDATE activities SET name=?, description=?, destination_id=?, duration_hours=?, price=?, category=?, image_url=? WHERE id=?"
        );
        $stmt->execute([$data['name'], $data['description'] ?? null, $data['destination_id'], $data['duration_hours'] ?? null, $data['price'] ?? 0.00, $data['category'] ?? null, $data['image_url'] ?? null, $id]);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM activities WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function notFound(): array { http_response_code(404); return ['error' => 'Not found']; }
    private function notAllowed(): array { http_response_code(405); return ['error' => 'Method not allowed']; }
}
