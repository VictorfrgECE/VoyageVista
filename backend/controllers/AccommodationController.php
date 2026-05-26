<?php
class AccommodationController {
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
            "SELECT a.*, d.name AS destination_name FROM accommodations a
             LEFT JOIN destinations d ON d.id = a.destination_id ORDER BY a.name ASC"
        )->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare(
            "SELECT a.*, d.name AS destination_name FROM accommodations a
             LEFT JOIN destinations d ON d.id = a.destination_id WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        foreach (['name', 'type', 'destination_id', 'price_per_night'] as $f) {
            if (empty($data[$f])) { http_response_code(422); return ['error' => "$f is required"]; }
        }
        $stmt = $this->db->prepare(
            "INSERT INTO accommodations (name, type, destination_id, address, price_per_night, stars, capacity, description, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$data['name'], $data['type'], $data['destination_id'], $data['address'] ?? null, $data['price_per_night'], $data['stars'] ?? null, $data['capacity'] ?? 1, $data['description'] ?? null, $data['image_url'] ?? null]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $stmt = $this->db->prepare(
            "UPDATE accommodations SET name=?, type=?, destination_id=?, address=?, price_per_night=?, stars=?, capacity=?, description=?, image_url=? WHERE id=?"
        );
        $stmt->execute([$data['name'], $data['type'], $data['destination_id'], $data['address'] ?? null, $data['price_per_night'], $data['stars'] ?? null, $data['capacity'] ?? 1, $data['description'] ?? null, $data['image_url'] ?? null, $id]);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM accommodations WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function notFound(): array { http_response_code(404); return ['error' => 'Not found']; }
    private function notAllowed(): array { http_response_code(405); return ['error' => 'Method not allowed']; }
}
