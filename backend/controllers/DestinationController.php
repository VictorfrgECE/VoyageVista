<?php
class DestinationController {
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
        $rows = $this->db->query("SELECT * FROM destinations ORDER BY name ASC")->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        if (empty($data['name']) || empty($data['country'])) {
            http_response_code(422);
            return ['error' => 'name and country are required'];
        }
        $stmt = $this->db->prepare(
            "INSERT INTO destinations (name, country, description, image_url, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$data['name'], $data['country'], $data['description'] ?? null, $data['image_url'] ?? null, $data['latitude'] ?? null, $data['longitude'] ?? null]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $stmt = $this->db->prepare(
            "UPDATE destinations SET name=?, country=?, description=?, image_url=?, latitude=?, longitude=? WHERE id=?"
        );
        $stmt->execute([$data['name'], $data['country'], $data['description'] ?? null, $data['image_url'] ?? null, $data['latitude'] ?? null, $data['longitude'] ?? null, $id]);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM destinations WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function notFound(): array { http_response_code(404); return ['error' => 'Not found']; }
    private function notAllowed(): array { http_response_code(405); return ['error' => 'Method not allowed']; }
}
